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
$scoreSql = "SELECT subject, assignment_score, quiz_score, exam_score 
             FROM subject_scores 
             WHERE student_id='$studentId'";
$scoreResult = $conn->query($scoreSql);

$scores = [];
if ($scoreResult->num_rows > 0) {
    while ($row = $scoreResult->fetch_assoc()) {
        $scores[] = $row;
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
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ccc;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
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
                            <li><a href="./student_assignments.php">Assignment</a></li>
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
        <h2>User Dashboard</h2>
        <h2>Welcome, <?php echo htmlspecialchars($student['username']); ?>!</h2>
        <h2>Welcome, <?php echo htmlspecialchars($student['name']); ?>!</h2>
        <p><strong>Student Number:</strong> <?php echo htmlspecialchars($student['student_number']); ?></p>
        
        <h3>Total Scores</h3>
        <table border="1" cellpadding="10" cellspacing="0">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Assignment Score</th>
                    <th>Quiz Score</th>
                    <th>Exam Score</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($scores) > 0): ?>
                    <?php foreach ($scores as $score): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($score['subject']); ?></td>
                            <td><?php echo htmlspecialchars($score['assignment_score']); ?></td>
                            <td><?php echo htmlspecialchars($score['quiz_score']); ?></td>
                            <td><?php echo htmlspecialchars($score['exam_score']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">No scores found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</div>

<script src="./dist/js/dropdown.js"></script>

</body>
</html>

<?php
$conn->close();
?>
