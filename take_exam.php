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

// Check if exam ID is provided
if (!isset($_GET['exam_id']) || empty($_GET['exam_id'])) {
    echo "Error: No exam ID provided.";
    exit();
}

$exam_id = intval($_GET['exam_id']);

// Fetch exam details with prepared statements
$sql_exam = "SELECT * FROM exams WHERE id = ?";
$stmt_exam = $conn->prepare($sql_exam);
$stmt_exam->bind_param("i", $exam_id);
$stmt_exam->execute();
$result_exam = $stmt_exam->get_result();

if ($result_exam->num_rows == 0) {
    echo "Error: No such exam found.";
    exit();
}

$exam = $result_exam->fetch_assoc();
$time_limit = $exam['time_limit'] * 60; // Time limit in seconds

// Fetch student details
$student_username = $_SESSION['username'];
$sql_student = "SELECT id FROM students WHERE username = ?";
$stmt_student = $conn->prepare($sql_student);
$stmt_student->bind_param("s", $student_username);
$stmt_student->execute();
$result_student = $stmt_student->get_result();

if ($result_student->num_rows == 0) {
    echo "Error: No such student found.";
    exit();
}

$student = $result_student->fetch_assoc();
$student_id = $student['id'];

// Fetch questions for the exam with prepared statements
$sql_questions = "SELECT * FROM exam_questions WHERE exam_id = ?";
$stmt_questions = $conn->prepare($sql_questions);
$stmt_questions->bind_param("i", $exam_id);
$stmt_questions->execute();
$result_questions = $stmt_questions->get_result();

if ($result_questions->num_rows == 0) {
    echo "Error: No questions found for this exam.";
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['answers']) || empty($_POST['answers'])) {
        $_SESSION['notification_message'] = "No answers submitted!";
        header("Location: task_exam.php?exam_id=" . $exam_id);
        exit();
    }

    $answers = $_POST['answers']; // This will be an array of answers
    $total_questions = count($answers);
    $correct_answers = 0;

    // Calculate score
    foreach ($answers as $question_id => $answer) {
        $sql_correct_answer = "SELECT correct_answer, question_type FROM exam_questions WHERE id = ?";
        $stmt_correct_answer = $conn->prepare($sql_correct_answer);
        $stmt_correct_answer->bind_param("i", $question_id);
        $stmt_correct_answer->execute();
        $result_correct_answer = $stmt_correct_answer->get_result();

        if ($result_correct_answer->num_rows == 0) {
            continue; // Skip if no such question exists
        }

        $correct_answer_row = $result_correct_answer->fetch_assoc();
        $correct_answer = $correct_answer_row['correct_answer'];
        $question_type = $correct_answer_row['question_type'];

        // Handle true/false questions specifically
        if ($question_type === 'true_false') {
            $correct_answer = strtolower($correct_answer) === 'true' ? 'true' : 'false';
            if ($correct_answer === strtolower($answer)) {
                $correct_answers++;
            }
        } elseif (($question_type === 'multiple_choice') && $correct_answer == $answer) {
            $correct_answers++;
        } elseif ($question_type === 'identification' && strtolower(trim($correct_answer)) == strtolower(trim($answer))) {
            $correct_answers++;
        }
    }

    $score = ($correct_answers / $total_questions) * 100;

    // Save score to the database
    $stmt_insert_result = $conn->prepare("INSERT INTO exam_results (student_username, exam_id, score) VALUES (?, ?, ?)");
    $stmt_insert_result->bind_param("sid", $student_username, $exam_id, $score);

    if ($stmt_insert_result->execute()) {
        // Record that the student has completed the exam
        $stmt_insert_student_exam = $conn->prepare("INSERT INTO student_exams (student_id, exam_id) VALUES (?, ?)");
        $stmt_insert_student_exam->bind_param("ii", $student_id, $exam_id);

        if ($stmt_insert_student_exam->execute()) {
            $_SESSION['notification_message'] = "You scored $score%! Your result has been saved!";
        } else {
            $_SESSION['notification_message'] = "Error marking the exam as completed: " . $stmt_insert_student_exam->error;
        }
    } else {
        $_SESSION['notification_message'] = "Error storing exam result: " . $stmt_insert_result->error;
    }

    // Redirect to the exam page to display the notification
    header("Location: task_exam.php?exam_id=" . $exam_id);
    exit();
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Exam - SkoolTech</title>
    <link rel="icon" href="./dist/img/skooltech-icon.png">
    <link rel="stylesheet" href="./dist/scss/main.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <style>
        .question {
            margin-bottom: 20px;
        }
        .question label {
            display: block;
            margin-bottom: 5px;
        }
        .question input {
            margin-right: 10px;
        }
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
        .loading {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
        }
        .loading.show {
            display: block;
        }
        .loading .spinner {
            border: 16px solid #f3f3f3;
            border-radius: 50%;
            border-top: 16px solid #3498db;
            width: 120px;
            height: 120px;
            animation: spin 2s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .timer {
            font-size: 1.2em;
            margin-bottom: 20px;
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
                            <li><a href="task_quiz.php">Quiz</a></li>
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

    <main class="main-container">
        <h2>Exam</h2>

        <div class="timer" id="timer"></div>

            <form id="examForm" action="take_exam.php?exam_id=<?php echo htmlspecialchars($exam_id); ?>" method="POST">
                <?php while ($row = $result_questions->fetch_assoc()): ?>
                    <div class="question">
                        <p><?php echo htmlspecialchars($row['question_text']); ?></p>
                        <?php if ($row['question_type'] == 'multiple_choice'): ?>
                            <label>
                                <input type="radio" name="answers[<?php echo htmlspecialchars($row['id']); ?>]" value="A" required>
                                <?php echo htmlspecialchars($row['choice_a']); ?>
                            </label>
                            <label>
                                <input type="radio" name="answers[<?php echo htmlspecialchars($row['id']); ?>]" value="B" required>
                                <?php echo htmlspecialchars($row['choice_b']); ?>
                            </label>
                            <label>
                                <input type="radio" name="answers[<?php echo htmlspecialchars($row['id']); ?>]" value="C" required>
                                <?php echo htmlspecialchars($row['choice_c']); ?>
                            </label>
                            <label>
                                <input type="radio" name="answers[<?php echo htmlspecialchars($row['id']); ?>]" value="D" required>
                                <?php echo htmlspecialchars($row['choice_d']); ?>
                            </label>
                        <?php elseif ($row['question_type'] == 'true_false'): ?>
                            <label>
                                <input type="radio" name="answers[<?php echo htmlspecialchars($row['id']); ?>]" value="true" required>True
                            </label>
                            <label>
                                <input type="radio" name="answers[<?php echo htmlspecialchars($row['id']); ?>]" value="false" required>False
                            </label>
                        <?php elseif ($row['question_type'] == 'identification'): ?>
                            <input type="text" name="answers[<?php echo htmlspecialchars($row['id']); ?>]" required>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
                <button type="submit">Submit</button>
            </form>


        <div class="notification" id="notification">
            <p>Time is up! Your exam is automatically submitted.</p>
        </div>

        <div class="loading" id="loading">
            <div class="spinner"></div>
        </div>
    </main>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
    var timeLimit = <?php echo $time_limit; ?>; // Time limit in seconds
    var timerElement = document.getElementById("timer");
    var notificationElement = document.getElementById("notification");
    var loadingElement = document.getElementById("loading");
    var examForm = document.getElementById("examForm");

    var endTime = new Date().getTime() + timeLimit * 1000;

    function updateTimer() {
        var now = new Date().getTime();
        var distance = endTime - now;

        if (distance <= 0) {
            clearInterval(timerInterval);
            examForm.submit(); // Submit the form when time is up
            notificationElement.classList.add("show");
            setTimeout(function() {
                notificationElement.classList.remove("show");
            }, 3000);
        } else {
            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((distance % (1000 * 60)) / 1000);
            timerElement.innerHTML = minutes + "m " + seconds + "s ";
        }
    }

    var timerInterval = setInterval(updateTimer, 1000);

    <?php if (isset($_SESSION['notification_message'])): ?>
        var notificationMessage = "<?php echo $_SESSION['notification_message']; ?>";
        notificationElement.innerHTML = "<p>" + notificationMessage + "</p>";
        notificationElement.classList.add("show");
        setTimeout(function() {
            notificationElement.classList.remove("show");
        }, 3000);
        <?php unset($_SESSION['notification_message']); ?>
    <?php endif; ?>

    // Show loading screen when submitting
    examForm.addEventListener("submit", function() {
        loadingElement.classList.add("show");
    });
});

</script>

</body>
</html>