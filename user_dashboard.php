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
$sql = "SELECT * FROM students WHERE username='$loggedInUsername'";
$result = $conn->query($sql);

if ($result->num_rows == 1) {
    $student = $result->fetch_assoc();
} else {
    echo "Student details not found.";
    exit();
}

// Extract initials from the user's name
$nameParts = explode(' ', $student['name']);
$initials = strtoupper($nameParts[0][0]); // First character of the first name

if (isset($nameParts[1])) {
    $initials .= strtoupper($nameParts[1][0]); // First character of the second name
}

// Query subject_scores table for the logged-in student
$studentId = $student['id'];  // Assuming 'id' is the primary key in the students table
$scoreSql = "SELECT subject, 
                    SUM(assignment_score) AS assignment_total, 
                    SUM(quiz_score) AS quiz_total, 
                    SUM(exam_score) AS exam_total
             FROM subject_scores 
             WHERE student_id='$studentId'
             GROUP BY subject";
$scoreResult = $conn->query($scoreSql);

$scores = [];
if ($scoreResult->num_rows > 0) {
    while ($row = $scoreResult->fetch_assoc()) {
        $scores[] = $row;
    }
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
    <title>Student Dashboard - SkoolTech</title>
    <link rel="icon" href="./dist/img/skooltech-icon.png">
    <link rel="stylesheet" href="./dist/scss/main.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
</head>
<body>

<!-- grid -->
<div class="grid-container">
    <!-- header -->
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
                                        // Determine the target page based on notification activity type
                                        $targetPage = '#';
                                        if ($notification['activity_type'] === 'Assignment') {
                                            $targetPage = 'student_assignments.php';
                                        } elseif ($notification['activity_type'] === 'Quiz') {
                                            $targetPage = 'task_quiz.php';
                                        } elseif ($notification['activity_type'] === 'Exam') {
                                            $targetPage = 'task_exam.php';
                                        }
                                        ?>
                                        <p 
                                            id="notif-<?php echo $notification['id']; ?>" 
                                            class="<?php echo $notification['is_read'] ? 'read' : 'unread'; ?>"
                                            data-id="<?php echo $notification['id']; ?>">
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
    
    <!-- sidenav -->
    <div id="sidenav">
        <div class="sidenav__wrapper">
            <div class="sidenav__img">
                <img src="./dist/img/academix white logo.png" alt="SkoolTech Logo">
            </div>
            <div class="sidenav-list">
                <ul>
                    <li><a href="#"><span class="material-icons-outlined">dashboard</span>Dashboard</a></li>
                    <li>
                        <a href="#" class="dropdown-toggle">
                            <span class="material-icons-outlined">checklist</span> Tasks
                            <span class="material-icons-outlined chevron-icon">keyboard_arrow_down</span>
                        </a>
                        <ul class="dropdown-content">
                            <li><a href="./student_assignments.php">Assignment</a></li>
                            <li><a href="./task_quiz.php">Quiz</a></li>
                            <li><a href="task_exam.php">Exam</a></li>
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

    <!-- main container -->
    <main class="main-container">
        <!-- <h2>User Dashboard</h2>
        <p><strong>Student Number:</strong> <?php echo htmlspecialchars($student['student_number']); ?></p> -->

        <div class="dashboard__banner" data-aos="fade-down">
            <div class="dashboard__banner__wrapper">
                <div class="dashboard__banner__text">
                    <!-- <h2>Welcome, <?php echo htmlspecialchars($student['username']); ?>!</h2> -->
                    <h2>Welcome, <?php echo htmlspecialchars($student['name']); ?>!</h2>
                    <p>Welcome to SkoolTech, your all-in-one platform for learning and academic success. Manage your courses, track your progress, take quizzes and exams, and stay connected with instructors—all designed to help you achieve your educational goals.</p>
                </div>
            </div>
        </div>
        
        <h3>Total Scores and GWA</h3>
        <table border="1" cellpadding="10" cellspacing="0">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Assignment Score</th>
                    <th>Quiz Score</th>
                    <th>Exam Score</th>
                    <th>GWA</th> <!-- Add GWA column -->
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_gwa = 0; 
                $subject_count = 0;

                // Check if the scores array has data
                if (count($scores) > 0): ?>
                    <?php foreach ($scores as $score): 
                        // Check if totals are valid to avoid division by zero
                        $assignment_total = isset($score['assignment_total']) ? $score['assignment_total'] : 0;
                        $quiz_total = isset($score['quiz_total']) ? $score['quiz_total'] : 0;
                        $exam_total = isset($score['exam_total']) ? $score['exam_total'] : 0;

                        // // Debugging output
                        // echo "Subject: " . htmlspecialchars($score['subject']) . "<br>";
                        // echo "Assignment Total: " . htmlspecialchars($assignment_total) . "<br>";
                        // echo "Quiz Total: " . htmlspecialchars($quiz_total) . "<br>";
                        // echo "Exam Total: " . htmlspecialchars($exam_total) . "<br><br>";

                        // Calculate GWA for each subject
                        $total_score = $assignment_total + $quiz_total + $exam_total;
                        $gwa = ($assignment_total || $quiz_total || $exam_total) ? ($total_score / 3) : 0; // Avoid division by zero
                        $total_gwa += $gwa;
                        $subject_count++;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($score['subject']); ?></td>
                            <td><?php echo htmlspecialchars($assignment_total); ?></td>
                            <td><?php echo htmlspecialchars($quiz_total); ?></td>
                            <td><?php echo htmlspecialchars($exam_total); ?></td>
                            <td><?php echo number_format($gwa, 2); ?></td> <!-- Display GWA -->
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="4"><strong>Overall GWA:</strong></td>
                        <td><strong><?php echo $subject_count > 0 ? number_format($total_gwa / $subject_count, 2) : 'N/A'; ?></strong></td> <!-- Display overall GWA -->
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No scores found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>

        </table>
    </main>
</div>

<script src="./dist/js/dropdown.js"></script>
<script src="./dist/js/notif-dropdown.js"></script>
<script src="./dist/js/notif-click.js"></script>


<script src="./dist/js/dropdown.js"></script>   
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
AOS.init();
</script>


</body>
</html>

<?php
$conn->close();
?>
