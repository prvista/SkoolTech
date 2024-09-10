<?php
session_start();

// Check if the user is logged in and is a professor
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'professor') {
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

// Get all students
$sql_students = "SELECT * FROM students";
$result_students = $conn->query($sql_students);

// Get all quiz results
$sql_results = "SELECT s.student_number, s.username, q.title AS quiz_title, qr.score 
                 FROM quiz_results qr
                 JOIN students s ON qr.student_username = s.username
                 JOIN quizzes q ON qr.quiz_id = q.id";
$result_results = $conn->query($sql_results);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SkoolTech</title>
    <link rel="stylesheet" href="./dist/scss/main.min.css">
    <link rel="icon" href="./dist/img/skooltech-icon.png">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
</head>
<body>
    <!-- grid -->
    <div class="grid-container">
        <!-- header -->
        <div class="header">
            <div class="container">
                <div class="header__wrapper"></div>
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
                        <li><a href="./task_creator.php"><span class="material-icons-outlined">app_registration</span>Tasks Creator</a></li>
                        <li><a href=""><span class="material-icons-outlined">sort</span>Results</a></li>
                        <li><a href=""><span class="material-icons-outlined">group</span>Students</a></li>
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
                    <h3><?php echo $result_students->num_rows; ?></h3>
                </div>

                <div class="card">
                    <div class="dashboard-card__wrapper">
                        <h2>TASKS</h2>
                        <span class="material-icons-outlined">category</span>
                    </div>
                    <h3><?php // Count the number of quizzes/tasks ?></h3>
                </div>

                <div class="card">
                    <div class="dashboard-card__wrapper">
                        <h2>RESULTS</h2>
                        <span class="material-icons-outlined">picture_in_picture</span>
                    </div>
                    <h3><?php echo $result_results->num_rows; ?></h3>
                </div>
            </div>

            <h2>Class List</h2>
            <table border="1">
                <tr>
                    <th>Student Number</th>
                    <th>Username</th>
                </tr>
                <?php
                if ($result_students->num_rows > 0) {
                    while ($row = $result_students->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['student_number']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='2'>No students found</td></tr>";
                }
                ?>
            </table>

            <h2>Quiz Results</h2>
            <table border="1">
                <tr>
                    <th>Student Number</th>
                    <th>Username</th>
                    <th>Quiz Title</th>
                    <th>Score</th>
                </tr>
                <?php
                if ($result_results->num_rows > 0) {
                    while ($row = $result_results->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['student_number']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['quiz_title']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['score']) . "%</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No quiz results found</td></tr>";
                }
                ?>
            </table>

        </main>
    </div>
</body>
</html>

<?php
$conn->close();
?>
