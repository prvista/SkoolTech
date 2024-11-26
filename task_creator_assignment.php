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

// Fetch professor's details
$stmt = $conn->prepare("SELECT name FROM professors WHERE username = ?");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
$professor = $result->num_rows > 0 ? $result->fetch_assoc() : null;

// Check for form submission to create assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $assignment_title = $_POST['assignment_title'];
    $assignment_description = $_POST['assignment_description'];
    $due_date = $_POST['due_date'];
    $subject = $_POST['subject']; // New subject selection
    
    // Insert assignment into the database
    $stmt = $conn->prepare("INSERT INTO assignments (title, description, due_date, subject) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $assignment_title, $assignment_description, $due_date, $subject);
    
    if ($stmt->execute()) {
        // Redirect with success message
        header("Location: task_creator_assignment.php?status=success&message=Assignment created successfully.");
        exit();
    } else {
        // Handle error
        header("Location: task_creator_assignment.php?status=error&message=Error creating assignment: " . $conn->error);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Assignment</title>
    <link rel="stylesheet" href="./dist/scss/main.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">

    <style>
        
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
                        <li><a href="./admin_dashboard.php"><span class="material-icons-outlined">dashboard</span>Dashboard</a></li>
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

        <main class="assignment-creator">
            <div class="task__banner">
                <div class="container">
                    <div class="task__banner__wrapper">
                        <div class="task__banner__text">
                            <h2>Task Creator - Assignment</h2>
                            <p>Welcome to the Task Creator for Assignments! Create, manage, and customize assignments with ease. Set due dates, grading criteria, and assign subjects, all while ensuring seamless tracking of student progress and performance.</p>
                        </div>
                    </div>
                </div>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="assignment_title">Title:</label>
                    <input type="text" id="assignment_title" name="assignment_title" required>
                </div>
                <div class="form-group">
                    <label for="assignment_description">Description:</label>
                    <textarea id="assignment_description" name="assignment_description" required></textarea>
                </div>
                <div class="form-group">
                    <label for="due_date">Due Date:</label>
                    <input type="datetime-local" id="due_date" name="due_date" required>
                </div>
                <div class="form-group">
                    <label for="subject">Subject:</label>
                    <select id="subject" name="subject" required>
                        <option value="English">English</option>
                        <option value="Science">Science</option>
                        <option value="Math">Math</option>
                    </select>
                </div>
                <div class="button-wrapper">
                    <button type="submit" id="submit-assignment-btn">Create Assignment</button>
                </div>
            </form>

            <?php if (isset($_GET['status'])): ?>
                <div class="notification <?php echo htmlspecialchars($_GET['status']); ?>">
                    <?php echo htmlspecialchars($_GET['message']); ?>
                </div>
            <?php endif; ?>
        </main>

    </div>

    <script src="./dist/js/dropdown.js"></script>
    <script>
        window.onload = function() {
            const notification = document.getElementById('notification');
            if (notification && notification.classList.contains('success')) {
                setTimeout(function() {
                    notification.classList.remove('success');
                }, 3000);
            }
        }
    </script>
</body>
</html>
