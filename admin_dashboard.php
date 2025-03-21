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

// Get all exam results
$sql_exam_results = "SELECT s.student_number, s.username, s.name, e.title AS exam_title, e.subject, er.score 
                     FROM exam_results er
                     JOIN students s ON er.student_id = s.id
                     JOIN exams e ON er.exam_id = e.id";
$result_exam_results = $conn->query($sql_exam_results);

// Get all assignment results
$sql_assignment_results = "SELECT s.student_number, s.username, s.name, a.title AS assignment_title, a.subject, asub.grade AS score 
                           FROM assignment_submissions asub
                           JOIN students s ON asub.student_id = s.id
                           JOIN assignments a ON asub.assignment_id = a.id";
$result_assignment_results = $conn->query($sql_assignment_results);

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
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
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

        .table-container {
    height: 25rem; /* Fixed height for the table container */
    overflow-y: auto; /* Enable vertical scrolling */
    overflow-x: hidden; /* Optional: disable horizontal scrolling */
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

            <div class="dashboard-card">
                <div class="card" data-aos="fade-down">
                    <div class="dashboard-card__wrapper">
                        <h2>STUDENTS</h2>
                        <span class="material-icons-outlined">groups</span>
                    </div>
                    <h3><?php echo $result_students->num_rows; ?></h3>
                </div>

                <div class="card" data-aos="fade-down">
                    <div class="dashboard-card__wrapper">
                        <h2>TASKS</h2>
                        <span class="material-icons-outlined">category</span>
                    </div>
                    <h3><?php echo $total_quizzes; ?></h3>
                </div>

                <div class="card" data-aos="fade-down">
                    <div class="dashboard-card__wrapper">
                        <h2>RESULTS</h2>
                        <span class="material-icons-outlined">picture_in_picture</span>
                    </div>
                    <h3><?php echo $result_results->num_rows; ?></h3>
                </div>
            </div>

            <!-- Quiz Results Table -->
            <h2>Quiz Results</h2>
            <div class="table-container">
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
        $studentNamesQuiz = [];
        $quizScores = [];
        if ($result_results->num_rows > 0) {
            while ($row = $result_results->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['student_number']) . "</td>";
                echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['quiz_title']) . "</td>";
                echo "<td>" . htmlspecialchars($row['subject']) . "</td>";
                echo "<td>" . htmlspecialchars($row['score']) . "%</td>";
                echo "</tr>";

                $studentNamesQuiz[] = htmlspecialchars($row['name']);
                $quizScores[] = htmlspecialchars($row['score']);
            }
        } else {
            echo "<tr><td colspan='6'>No quiz results found</td></tr>";
        }
        $studentNamesQuizJson = json_encode($studentNamesQuiz);
        $quizScoresJson = json_encode($quizScores);
        ?>
    </table>
</div>


            <br>
            <!-- Create a chart for Quiz -->
            <h3>Performance Chart (Quiz Scores)</h3>
            <canvas id="quizChart" width="40" height="10"></canvas>

            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                var studentNamesQuiz = <?php echo $studentNamesQuizJson; ?>;
                var quizScores = <?php echo $quizScoresJson; ?>;

                var ctx = document.getElementById('quizChart').getContext('2d');
                var chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: studentNamesQuiz,
                        datasets: [{
                            label: 'Quiz Scores',
                            data: quizScores,
                            backgroundColor: 'rgba(0, 123, 255, 0.5)',
                            borderColor: 'rgba(0, 123, 255, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            </script>

            <!-- Exam Results Table -->
            <h2>Exam Results</h2>
            <div class="table-container">
            <table border="1">
                <tr>
                    <th>Student Number</th>
                    <th>Username</th>
                    <th>Name</th>
                    <th>Exam Title</th>
                    <th>Subject</th>
                    <th>Score</th>
                </tr>
                <?php
                $studentNamesExam = [];
                $examScores = [];
                if ($result_exam_results->num_rows > 0) {
                    while ($row = $result_exam_results->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['student_number']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['exam_title']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['subject']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['score']) . "%</td>";
                        echo "</tr>";

                        $studentNamesExam[] = htmlspecialchars($row['name']);
                        $examScores[] = htmlspecialchars($row['score']);
                    }
                } else {
                    echo "<tr><td colspan='6'>No exam results found</td></tr>";
                }
                $studentNamesExamJson = json_encode($studentNamesExam);
                $examScoresJson = json_encode($examScores);
                ?>

            </table>
        </div>
            <br>
            <!-- Create a chart for Exam -->
            <h3>Performance Chart (Exam Scores)</h3>
            <canvas id="examChart" width="40" height="10"></canvas>

            <script>
                var studentNamesExam = <?php echo $studentNamesExamJson; ?>;
                var examScores = <?php echo $examScoresJson; ?>;

                var ctx = document.getElementById('examChart').getContext('2d');
                var chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: studentNamesExam,
                        datasets: [{
                            label: 'Exam Scores',
                            data: examScores,
                            backgroundColor: 'rgba(255, 99, 132, 0.5)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            </script>

            <!-- Assignment Results Table -->
            <h2>Assignment Results</h2>
        <div class="table-container">
            <table border="1">
                <tr>
                    <th>Student Number</th>
                    <th>Username</th>
                    <th>Name</th>
                    <th>Assignment Title</th>
                    <th>Subject</th>
                    <th>Score</th>
                </tr>
                <?php
                $studentNamesAssignment = [];
                $assignmentScores = [];
                if ($result_assignment_results->num_rows > 0) {
                    while ($row = $result_assignment_results->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['student_number']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['assignment_title']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['subject']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['score']) . "%</td>";
                        echo "</tr>";

                        $studentNamesAssignment[] = htmlspecialchars($row['name']);
                        $assignmentScores[] = htmlspecialchars($row['score']);
                    }
                } else {
                    echo "<tr><td colspan='6'>No assignment results found</td></tr>";
                }
                $studentNamesAssignmentJson = json_encode($studentNamesAssignment);
                $assignmentScoresJson = json_encode($assignmentScores);
                ?>

            </table>
            </div>

            <!-- Create a chart for Assignment -->
            <br>
            <h3>Performance Chart (Assignment Scores)</h3>
            <canvas id="assignmentChart" width="40" height="10"></canvas>
            
            <script src="./dist/js/dropdown.js"></script>   
            <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

            <script>
                var studentNamesAssignment = <?php echo $studentNamesAssignmentJson; ?>;
                var assignmentScores = <?php echo $assignmentScoresJson; ?>;

                var ctx = document.getElementById('assignmentChart').getContext('2d');
                var chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: studentNamesAssignment,
                        datasets: [{
                            label: 'Assignment Scores',
                            data: assignmentScores,
                            backgroundColor: 'rgba(75, 192, 192, 0.5)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            </script>

    <script>
    AOS.init();
    </script>

        </main>
    </div>
</body>
</html>

<?php
$conn->close();
?>
