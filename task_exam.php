<?php
session_start();

// Check if the user is logged in and is a student
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "skooltech";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve student details
$loggedInUsername = $_SESSION['username'];
$sql_student = "SELECT * FROM students WHERE username='$loggedInUsername'";
$result_student = $conn->query($sql_student);

if ($result_student->num_rows == 1) {
    $student = $result_student->fetch_assoc();
} else {
    echo "Student details not found.";
    exit();
}

// Initialize alert message
$alert_message = "";

// Check for notification message in session
if (isset($_SESSION['notification_message'])) {
    $alert_message = $_SESSION['notification_message'];
    unset($_SESSION['notification_message']); // Clear message after use
}

// Fetch all exams excluding those already taken by the student
$sql_exams = "
    SELECT DISTINCT e.*, DATE_FORMAT(e.deadline, '%b %d - %I:%i %p') AS formatted_deadline
    FROM exams e
    LEFT JOIN student_exams se ON e.id = se.exam_id AND se.student_id = (SELECT id FROM students WHERE username = '$loggedInUsername')
    WHERE se.exam_id IS NULL
";
$result_exams = $conn->query($sql_exams);

// Extract initials from the user's name
$nameParts = explode(' ', $student['name']);
$initials = strtoupper($nameParts[0][0]); // First character of the first name

if (isset($nameParts[1])) {
    $initials .= strtoupper($nameParts[1][0]); // First character of the second name
}
// Retrieve only unread notifications
$notificationSql = "SELECT * FROM notifications WHERE student_id = ? AND is_read = 0 ORDER BY id DESC";
$notificationStmt = $conn->prepare($notificationSql);
$notificationStmt->bind_param("i", $student['id']);
$notificationStmt->execute();
$notificationResult = $notificationStmt->get_result();

$notifications = [];
if ($notificationResult->num_rows > 0) {
    while ($row = $notificationResult->fetch_assoc()) {
        $notifications[] = $row;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam List - SkoolTech</title>
    <link rel="icon" href="./dist/img/skooltech-icon.png">
    <link rel="stylesheet" href="./dist/scss/main.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
</head>
<body>

<div class="grid-container">
<div class="header">
        <div class="container">
            <div class="header__wrapper">
                <div class="header__right">
                        <!-- Notifications Dropdown -->
                        <div class="notif-dropdown">
                            <a href="#" class="notif-toggle">
                                <span class="material-icons-outlined">notifications</span>
                                <!-- Notification count badge -->
                                <?php if (count($notifications) > 0): ?>
                                    <span class="notif-badge"><?php echo count($notifications); ?></span>
                                <?php endif; ?>
                            </a>
                            <div class="notif-dropdown-content">
                                <?php if (count($notifications) > 0): ?>
                                    <?php foreach ($notifications as $notification): ?>
                                        <?php
                                        $targetPage = '#';
                                        if ($notification['activity_type'] === 'Assignment') {
                                            $targetPage = 'student_assignments.php';
                                        } elseif ($notification['activity_type'] === 'Quiz') {
                                            $targetPage = 'task_quiz.php';
                                        } elseif ($notification['activity_type'] === 'Exam') {
                                            $targetPage = 'task_exam.php';
                                        }
                                        ?>
                                        <p data-id="<?php echo $notification['id']; ?>" class="<?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                                            <a class="notif_btn" 
                                            href="<?php echo htmlspecialchars($targetPage); ?>" 
                                            data-id="<?php echo $notification['id']; ?>" 
                                            onclick="markNotificationAsRead(event, <?php echo $notification['id']; ?>, '<?php echo $targetPage; ?>')">
                                                <strong><?php echo htmlspecialchars($notification['activity_type']); ?>:</strong>
                                                <?php echo htmlspecialchars($notification['activity_title']); ?>
                                            </a>
                                        </p>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p>No new notifications</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="initials-bg">
                            <p><?php echo $initials; ?></p>
                        </div>
                    </div>
                </div>
        </div>
    </div>

    <div id="sidenav">
        <div class="sidenav__wrapper">
            <div class="sidenav__img">
                <img src="./dist/img/academix white logo.png" alt="">
            </div>
            <div class="sidenav-list">
                <ul>
                    <li><a href="./user_dashboard.php"><span class="material-icons-outlined">dashboard</span>Dashboard</a></li>
                    <li>
                        <a href="#" class="dropdown-toggle">
                            <span class="material-icons-outlined">checklist</span> Tasks
                            <span class="material-icons-outlined chevron-icon">keyboard_arrow_down</span>
                        </a>
                        <ul class="dropdown-content">
                            <li><a href="./student_assignments.php">Assignment</a></li>
                            <li><a href="./task_quiz.php">Quiz</a></li>
                            <li><a href="#">Exam</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="./user_subjects.php"><span class="material-icons-outlined">library_books</span> Subjects</a>
                    </li>
                    <li><a href="user_virtualroom.php"><span class="material-icons-outlined">video_call</span>Virtual Room</a></li>
                    <li><a href="logout.php"><span class="material-icons-outlined">logout</span>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>

    <main class="main-container">
        <!-- Dashboard Banner -->
        <div class="dashboard__banner">
            <div class="dashboard__banner__wrapper">
                <div class="dashboard__banner__text">
                    <h2>Exam</h2>
                    <p>Welcome to SkoolTech's Exam Portal! Prepare for your exams, take them under timed conditions, view your progress, and get detailed results to assess your performance with ease.</p>
                </div>
            </div>
        </div>
        <br>
        <!-- Available Exams Section -->
        <h2>Available Exams</h2>

        <!-- Notification (if any) -->
        <?php
        if (!empty($alert_message)) {
            echo "<div id='notification' class='notification show'>$alert_message</div>";
        }
        ?>

        <!-- Exam List -->
        <div class="exam-list">
            <?php
            if ($result_exams->num_rows > 0) {
                while ($row = $result_exams->fetch_assoc()) {
                    echo "<div class='exam-item'>";
                    echo "  <div class='exam-details'>";
                    echo "      <p class='exam-title'>{$row['title']}</p>";
                    echo "      <p class='exam-deadline'>Deadline: {$row['formatted_deadline']}</p>";
                    echo "  </div>";
                    echo "  <a href='take_exam.php?exam_id={$row['id']}' class='exam-start-btn'>Start Exam</a>";
                    echo "</div>";
                }
            } else {
                echo "<p class='no-exams'>No exams available</p>";
            }
            ?>
        </div>
    </main>

</div>

<script src="./dist/js/dropdown.js"></script>
<script>
    window.onload = function() {
        var notification = document.getElementById('notification');
        if (notification) {
            setTimeout(function() {
                notification.classList.add('hide');
                setTimeout(function() {
                    notification.style.display = 'none';
                }, 500); // Additional delay to ensure opacity transition
            }, 7000); // Show for 7 seconds
        }
    };
</script>
<script src="./dist/js/notif-dropdown.js"></script>
<script src="./dist/js/notif-click.js"></script>



</body>
</html>

<?php
$conn->close();
?>
