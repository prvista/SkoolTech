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

// Get all quiz results and average scores
$sql_results = "SELECT 
                    s.student_number, 
                    s.username, 
                    q.title AS quiz_title, 
                    COUNT(q.id) AS total_questions,
                    SUM(CASE WHEN qq.correct_answer = 'A' THEN 1 ELSE 0 END) AS correct_answers, 
                    CONCAT(SUM(CASE WHEN qq.correct_answer = 'A' THEN 1 ELSE 0 END), '/', COUNT(q.id)) AS score_fraction,
                    ROUND((SUM(CASE WHEN qq.correct_answer = 'A' THEN 1 ELSE 0 END) / COUNT(q.id)) * 100, 2) AS percentage
                FROM quiz_results qr
                JOIN students s ON qr.student_username = s.username
                JOIN quizzes q ON qr.quiz_id = q.id
                JOIN quiz_questions qq ON qq.quiz_id = q.id
                GROUP BY s.username, q.id";
$result_results = $conn->query($sql_results);

// Fetch status and message from query parameters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$message = isset($_GET['message']) ? $_GET['message'] : '';

// Fetch professor's details
$stmt = $conn->prepare("SELECT name FROM professors WHERE username = ?");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $professor = $result->fetch_assoc(); // Fetch professor data
}
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
            transition: opacity 1s ease;
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
                        <li>
                            <a href="#" class="dropdown-toggle">
                                <span class="material-icons-outlined">app_registration</span> Task Creator
                                <div class="arrow-down">
                                <span class="material-icons-outlined chevron-icon">keyboard_arrow_down</span>
                                </div>
                            </a>
                            <ul class="dropdown-content">
                                <li><a href="#">Assignment</a></li>
                                <li><a href="./task_creator.php">Quiz</a></li>
                                <li><a href="./task_creator_exam.php">Exam</a></li>
                            </ul>
                        </li>
                        <li><a href=""><span class="material-icons-outlined">sort</span>Results</a></li>
                        <li><a href=""><span class="material-icons-outlined">group</span>Students</a></li>
                        <li><a href="logout.php"><span class="material-icons-outlined">logout</span>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- main container -->
        <main class="main-container">
        
            <!-- Notification -->
            <div class="notification <?php echo htmlspecialchars($status); ?> <?php echo $status ? 'show' : ''; ?>" id="notification">
                <?php echo htmlspecialchars($message); ?>
            </div>

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
            <h2>Welcome, <?php echo $professor['name']; ?>!</h2>
            <h2>Class List</h2>

            <table border="1">
                <tr>
                    <th>Student Number</th>
                    <th>Username</th>
                    <th>Name</th>
                </tr>
                <?php
                if ($result_students->num_rows > 0) {
                    while ($row = $result_students->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['student_number']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='3'>No students found</td></tr>";
                }
                ?>
            </table>

            <h2>Quiz Results</h2>
            <table border="1">
                <tr>
                    <th>Student Number</th>
                    <th>Username</th>
                    <th>Quiz Title</th>
                    <th>Raw Score</th>
                    <th>Average Score</th>
                </tr>
                <?php
                if ($result_results->num_rows > 0) {
                    while ($row = $result_results->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['student_number']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['quiz_title']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['score_fraction']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['percentage']) . "%</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>No quiz results found</td></tr>";
                }
                ?>
            </table>

        </main>
    </div>

    <script src="./dist/js/dropdown.js"></script>
    <script>
        // Function to hide the notification after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const notification = document.getElementById('notification');
            if (notification.classList.contains('show')) {
                setTimeout(() => {
                    notification.classList.remove('show');
                }, 5000);
            }
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>
