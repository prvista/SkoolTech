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

// Fetch all quizzes excluding those already taken by the student
$sql_quizzes = "SELECT DISTINCT q.* FROM quizzes q
                LEFT JOIN quiz_results qr ON q.id = qr.quiz_id AND qr.student_username = '$loggedInUsername'
                WHERE qr.quiz_id IS NULL";
$result_quizzes = $conn->query($sql_quizzes);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz List - SkoolTech</title>
    <link rel="icon" href="./dist/img/skooltech-icon.png">
    <link rel="stylesheet" href="./dist/scss/main.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <style>
        .notification {
            display: none;
            position: fixed;
            top: 10px;
            right: 10px;
            padding: 15px;
            border-radius: 5px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            z-index: 1000;
            transition: opacity 0.5s ease;
        }
        .notification.show {
            display: block;
            opacity: 1;
        }
        .notification.hide {
            opacity: 0;
        }
    </style>
</head>
<body>

<div class="grid-container">
    <div class="header">
        <div class="container">
            <div class="header__wrapper"></div>
        </div>
    </div>

    <div id="sidenav">
        <div class="sidenav__wrapper">
            <div class="sidenav__img">
                <img src="./dist/img/skooltech-logo.png" alt="">
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
                            <li><a href="#">Assignment</a></li>
                            <li><a href="./task_quiz.php">Quiz</a></li>
                            <li><a href="#">Exam</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="#" class="dropdown-toggle">
                            <span class="material-icons-outlined">library_books</span> Subjects
                            <span class="material-icons-outlined chevron-icon">keyboard_arrow_down</span>
                        </a>
                        <ul class="dropdown-content">
                            <li><a href="#">English</a></li>
                            <li><a href="#">Science</a></li>
                            <li><a href="#">Math</a></li>
                        </ul>
                    </li>
                    <li><a href="logout.php"><span class="material-icons-outlined">logout</span>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>

    <main class="main-container">
        <h2>Welcome, <?php echo htmlspecialchars($student['username']); ?>!</h2>
        <p><strong>Student Number:</strong> <?php echo htmlspecialchars($student['student_number']); ?></p>
        <br>
        <h2>Available Quizzes</h2>
        
        <?php
        if (!empty($alert_message)) {
            echo "<div id='notification' class='notification show'>$alert_message</div>";
        }

        if ($result_quizzes->num_rows > 0) {
            while ($row = $result_quizzes->fetch_assoc()) {
                // echo "<a href='take_quiz.php?quiz_id={$row['id']}'}</a><br>";
                echo "<a href='take_quiz.php?quiz_id={$row['id']}'>{$row['title']}</a><br>";
            }
        } else {
            echo "<p>No quizzes available</p>";
        }
        ?>
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

</body>
</html>

<?php
$conn->close();
?>
