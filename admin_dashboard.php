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

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get all students
$sql_students = "SELECT * FROM students";
$result_students = $conn->query($sql_students);

// Get the number of quizzes/tasks
$sql_quizzes = "SELECT COUNT(*) AS total_quizzes FROM quizzes";
$result_quizzes = $conn->query($sql_quizzes);
$total_quizzes = $result_quizzes->fetch_assoc()['total_quizzes'];

// Get all quiz results including raw scores and subjects
$sql_results = "SELECT s.student_number, s.username, s.name, q.title AS quiz_title, q.subject, qr.score, qr.raw_score 
                FROM quiz_results qr
                JOIN students s ON qr.student_id = s.id
                JOIN quizzes q ON qr.quiz_id = q.id";

$result_results = $conn->query($sql_results);

if (!$result_results) {
    die("Error fetching quiz results: " . $conn->error);
}

// Fetch professor's details
$stmt = $conn->prepare("SELECT name FROM professors WHERE username = ?");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
$professor = $result->num_rows > 0 ? $result->fetch_assoc() : null;

// Fetch status and message from query parameters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$message = isset($_GET['message']) ? $_GET['message'] : '';
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
                        <li><a href="#"><span class="material-icons-outlined">dashboard</span>Dashboard</a></li>
                        <li>
                            <a href="#" class="dropdown-toggle">
                                <span class="material-icons-outlined">app_registration</span> Task Creator
                                <div class="arrow-down">
                                    <span class="material-icons-outlined chevron-icon">keyboard_arrow_down</span>
                                </div>
                            </a>
                            <ul class="dropdown-content">
                                <li><a href="./task_creator_assignment.php">Assignment</a></li>
                                <li><a href="./task_creator.php">Quiz</a></li>
                                <li><a href="./task_creator_exam.php">Exam</a></li>
                            </ul>
                        </li>
                        
                        <li>
                            <a href="#" class="dropdown-toggle">
                            <span class="material-icons-outlined">sort</span> Results
                                <div class="arrow-down">
                                    <span class="material-icons-outlined chevron-icon">keyboard_arrow_down</span>
                                </div>
                            </a>
                            <ul class="dropdown-content">
                                <li><a href="./admin_analysis.php">Analysis</a></li>
                                <li><a href="./admin_assignments.php">Ass Results</a></li>
                            </ul>
                        </li>

                        <li><a href="./admin_students.php"><span class="material-icons-outlined">group</span>Students</a></li>
                        <li><a href="./admin_reportcard.php"><span class="material-icons-outlined">credit_card</span>Report Card</a></li>
                        <li><a href="logout.php"><span class="material-icons-outlined">logout</span>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <main class="main-container">
            <div class="notification <?php echo htmlspecialchars($status); ?> <?php echo $status ? 'show' : ''; ?>" id="notification">
                <?php echo htmlspecialchars($message); ?>
            </div>

            <!-- <div class="dashboard__banner">
                <div class="container">
                    <div class="dashboard__banner__wrapper">
                        <div class="dashboard__banner__text">
                            <h2>Welcome, <?php echo htmlspecialchars($professor['name']); ?>!</h2>
                            <p>Welcome to SkoolTech, your all-in-one platform for learning and academic success. Manage your courses, track your progress, take quizzes and exams, and stay connected with instructors—all designed to help you achieve your educational goals.</p>
                        </div>
                    </div>
                </div>
            </div> -->

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
                    <h3><?php echo $total_quizzes; ?></h3>
                </div>

                <div class="card">
                    <div class="dashboard-card__wrapper">
                        <h2>RESULTS</h2>
                        <span class="material-icons-outlined">picture_in_picture</span>
                    </div>
                    <h3><?php echo $result_results->num_rows; ?></h3>
                </div>
            </div>

            


            <h2>Quiz Results</h2>
            <table border="1">
                <tr>
                    <th>Student Number</th>
                    <th>Username</th>
                    <th>Name</th>
                    <th>Quiz Title</th>
                    <th>Subject</th>
                    <th>Score</th>
                </tr>
                <?php
                // Initialize arrays for names, scores, quiz titles, and subjects
                $studentNames = [];
                $quizScores = [];
                $quizTitles = [];
                $subjects = []; // New array for subjects

                if ($result_results->num_rows > 0) {
                    while ($row = $result_results->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['student_number']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['quiz_title']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['subject']) . "</td>"; // Displaying subject
                        echo "<td>" . htmlspecialchars($row['score']) . "%</td>";
                        echo "</tr>";

                        // Push names, scores, quiz titles, and subjects to arrays
                        $studentNames[] = htmlspecialchars($row['name']);
                        $quizScores[] = htmlspecialchars($row['score']);
                        $quizTitles[] = htmlspecialchars($row['quiz_title']);
                        $subjects[] = htmlspecialchars($row['subject']); // Add subject to the array
                    }
                } else {
                    echo "<tr><td colspan='6'>No quiz results found</td></tr>";
                }

                // Convert arrays to JSON for use in JavaScript
                $studentNamesJson = json_encode($studentNames);
                $quizScoresJson = json_encode($quizScores);
                $quizTitlesJson = json_encode($quizTitles);
                $subjectsJson = json_encode($subjects); // Convert subjects array to JSON
                ?>
            </table>

            <br>
            <br>


            <!-- chart -->
            <canvas id="quizBarChart" width="40" height="10"></canvas>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>
            <!-- <canvas id="barChart" width="200" height="200"></canvas> -->

        </main>
    </div>

    <!-- charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="./dist/js/PieChart.js"></script>
    <script src="./dist/js/quizBarChart.js"></script>

    <script src="./dist/js/dropdown.js"></script>

    <script>
        // Show notification if it exists
        window.onload = function() {
            const notification = document.getElementById('notification');
            if (notification.classList.contains('show')) {
                setTimeout(function() {
                    notification.classList.remove('show');
                    notification.classList.add('hide');
                }, 3000);
            }
        }
    </script>

    <!-- quiz bar chart -->
    <script>
        var studentNames = <?php echo $studentNamesJson; ?>;
        var quizScores = <?php echo $quizScoresJson; ?>;
        var quizTitles = <?php echo $quizTitlesJson; ?>;
        var subjects = <?php echo $subjectsJson; ?>;
        createQuizBarChart(studentNames, quizScores, quizTitles, subjects);
    </script>

</body>
</html>

<?php
$conn->close();
?>
