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

// Check if quiz ID is provided
if (!isset($_GET['quiz_id']) || empty($_GET['quiz_id'])) {
    echo "Error: No quiz ID provided.";
    exit();
}

$quiz_id = intval($_GET['quiz_id']);

// Fetch quiz details
$sql_quiz = "SELECT * FROM quizzes WHERE id = ?";
$stmt_quiz = $conn->prepare($sql_quiz);
$stmt_quiz->bind_param("i", $quiz_id);
$stmt_quiz->execute();
$result_quiz = $stmt_quiz->get_result();

if ($result_quiz->num_rows == 0) {
    echo "Error: No such quiz found.";
    exit();
}

$quiz = $result_quiz->fetch_assoc();
$time_limit = $quiz['time_limit'] * 60; // Time limit in seconds

// Fetch student details
$student_username = $_SESSION['username'];
$sql_student = "SELECT id, name FROM students WHERE username = ?";
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
$student_name = $student['name'];

// Fetch questions for the quiz
$sql_questions = "SELECT * FROM quiz_questions WHERE quiz_id = ?";
$stmt_questions = $conn->prepare($sql_questions);
$stmt_questions->bind_param("i", $quiz_id);
$stmt_questions->execute();
$result_questions = $stmt_questions->get_result();

if ($result_questions->num_rows == 0) {
    echo "Error: No questions found for this quiz.";
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['answers']) || empty($_POST['answers'])) {
        $_SESSION['notification_message'] = "No answers submitted!";
        header("Location: task_quiz.php?quiz_id=" . $quiz_id);
        exit();
    }

    $answers = $_POST['answers'];
    $total_questions = count($answers);
    $correct_answers = 0;

    // Calculate score
    foreach ($answers as $question_id => $answer) {
        $sql_correct_answer = "SELECT correct_answer, question_type FROM quiz_questions WHERE id = ?";
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

        // Handle answer checking
        if (($question_type === 'true_false' && strtolower($correct_answer) === strtolower($answer)) ||
            ($question_type === 'multiple_choice' && $correct_answer == $answer) ||
            ($question_type === 'identification' && strtolower(trim($correct_answer)) == strtolower(trim($answer)))) {
            $correct_answers++;
        }
    }

    $score = ($correct_answers / $total_questions) * 100;

    // Fetch the subject for this quiz
    $subject = $quiz['subject'];

    // Save score to the quiz_results table
    $stmt_insert_result = $conn->prepare("INSERT INTO quiz_results (student_id, quiz_id, score, subject) VALUES (?, ?, ?, ?)");
    $stmt_insert_result->bind_param("iiss", $student_id, $quiz_id, $score, $subject);

    if ($stmt_insert_result->execute()) {
        // Check if an entry already exists in subject_scores for this student and subject
        $stmt_check_subject_score = $conn->prepare("SELECT id FROM subject_scores WHERE student_id = ? AND subject = ?");
        $stmt_check_subject_score->bind_param("is", $student_id, $subject);
        $stmt_check_subject_score->execute();
        $result_check_subject_score = $stmt_check_subject_score->get_result();

        if ($result_check_subject_score->num_rows > 0) {
            // If exists, update the quiz_score
            $stmt_update_subject_score = $conn->prepare("UPDATE subject_scores SET quiz_score = ? WHERE student_id = ? AND subject = ?");
            $stmt_update_subject_score->bind_param("iis", $score, $student_id, $subject);
            $stmt_update_subject_score->execute();
        } else {
            // Otherwise, insert a new entry
            $stmt_insert_subject_score = $conn->prepare("INSERT INTO subject_scores (student_id, student_name, subject, quiz_score) VALUES (?, ?, ?, ?)");
            $stmt_insert_subject_score->bind_param("issi", $student_id, $student_name, $subject, $score);
            $stmt_insert_subject_score->execute();
        }

        $_SESSION['notification_message'] = "You scored $score%! Your result has been saved!";
    } else {
        $_SESSION['notification_message'] = "Error storing quiz result: " . $stmt_insert_result->error;
    }

    // Redirect to the quiz page to display the notification
    header("Location: task_quiz.php?quiz_id=" . $quiz_id);
    exit();
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Quiz - SkoolTech</title>
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
                            <li><a href="take_quiz.php">Quiz</a></li>
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
        <h2>Quiz</h2>
        <div class="timer" id="timer"></div>

        <form id="quizForm" action="take_quiz.php?quiz_id=<?php echo htmlspecialchars($quiz_id); ?>" method="POST">
            <?php while ($row = $result_questions->fetch_assoc()): ?>
                <div class="question">
                    <p><?php echo htmlspecialchars($row['question_text']); ?></p>
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
                </div>
            <?php endwhile; ?>
            <button type="submit">Submit Quiz</button>
        </form>

        <?php if (isset($_SESSION['notification_message'])): ?>
            <div class="notification show"><?php echo $_SESSION['notification_message']; unset($_SESSION['notification_message']); ?></div>
        <?php endif; ?>
    </main>
</div>

<script>
    // Timer functionality
    let timeLimit = <?php echo $time_limit; ?>;
    let timerDisplay = document.getElementById('timer');

    const timerInterval = setInterval(() => {
        if (timeLimit <= 0) {
            clearInterval(timerInterval);
            document.getElementById('quizForm').submit();
        } else {
            timerDisplay.textContent = `Time left: ${Math.floor(timeLimit / 60)}:${String(timeLimit % 60).padStart(2, '0')}`;
            timeLimit--;
        }
    }, 1000);
</script>
</body>
</html>
