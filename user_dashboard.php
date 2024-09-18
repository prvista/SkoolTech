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

</head>
<body>

<!-- grid -->
<div class="grid-container">
        <!-- header -->
        <div class="header">
            <div class="container">
                <div class="header__wrapper">
                    <div class="header__right">
                        <a href="#"><span class="material-icons-outlined chevron-icon">notifications</span></a>
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
                    <img src="./dist/img/skooltech-logo.png" alt="">
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
                                <li><a href="#">Assignment</a></li>
                                <li><a href="./task_quiz.php">Quiz</a></li>
                                <li><a href="task_exam.php">Exam</a></li>
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


        <!-- main container -->
        <main class="main-container">

            <div class="dashboard-card">


                <div class="card">
                    <div class="dashboard-card__wrapper">
                        <h2>STUDENTS</h2>
                        <span class="material-icons-outlined">groups</span>
                    </div>
                </div>

                <div class="card">
                    <div class="dashboard-card__wrapper">
                        <h2>TASKS</h2>
                        <span class="material-icons-outlined">category</span>
                    </div>
                </div>

                <div class="card">
                    <div class="dashboard-card__wrapper">
                        <h2>RESULTS</h2>
                        <span class="material-icons-outlined">picture_in_picture</span>
                    </div>
                </div>


            </div>

            <h2>User Dashboard</h2>
            <h2>Welcome, <?php echo htmlspecialchars($student['username']); ?>!</h2>
            <h2>Welcome, <?php echo htmlspecialchars($student['name']); ?>!</h2>
            <p><strong>Student Number:</strong> <?php echo htmlspecialchars($student['student_number']); ?></p>
        </main>
    </div>

    <script src="./dist/js/dropdown.js"></script>

</body>
</html>

<?php
$conn->close();
?>
