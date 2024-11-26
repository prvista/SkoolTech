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

// Get all quiz results including raw scores
$sql_results = "SELECT s.student_number, s.username, s.name, q.title AS quiz_title, qr.score, qr.raw_score 
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
                        <li><a href="admin_dashboard.php"><span class="material-icons-outlined">dashboard</span>Dashboard</a></li>
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
                        <li><a href=""><span class="material-icons-outlined">group</span>Students</a></li>
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
            <h2>Class List</h2>
            <h2>Number of Students <?php echo $result_students->num_rows; ?></h2>
            <div class="search-bar">
                <input type="text" class="search-input" id="searchInput" placeholder="Search by Name or Username...">
            </div>
            <table id="studentTable">
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
        </main>

    </div>

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

    <script>
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const filter = this.value.toUpperCase();
            const table = document.getElementById('studentTable');
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                const username = rows[i].getElementsByTagName('td')[1];
                const name = rows[i].getElementsByTagName('td')[2];

                if (username || name) {
                    const usernameText = username.textContent || username.innerText;
                    const nameText = name.textContent || name.innerText;

                    rows[i].style.display =
                        usernameText.toUpperCase().includes(filter) || nameText.toUpperCase().includes(filter)
                            ? ''
                            : 'none';
                }
            }
        });
    </script>


</body>
</html>

<?php
$conn->close();
?>
