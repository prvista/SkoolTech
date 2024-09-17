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

// Fetch quiz details including time limit
$sql_quiz = "SELECT * FROM quizzes WHERE id = $quiz_id";
$result_quiz = $conn->query($sql_quiz);

if ($result_quiz->num_rows == 0) {
    echo "Error: No such quiz found.";
    exit();
}

$quiz = $result_quiz->fetch_assoc();
$time_limit = $quiz['time_limit'] * 60; // Time limit in seconds

// Fetch questions for the quiz
$sql_questions = "SELECT * FROM quiz_questions WHERE quiz_id = $quiz_id";
$result_questions = $conn->query($sql_questions);

if (!$result_questions) {
    echo "SQL Error: " . $conn->error;
    exit();
}

if ($result_questions->num_rows == 0) {
    echo "Error: No questions found for this quiz.";
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $answers = $_POST['answers']; // This will be an array of answers
    $total_questions = count($answers);
    $correct_answers = 0;

    // Calculate score
    foreach ($answers as $question_id => $answer) {
        $sql_correct_answer = "SELECT correct_answer FROM quiz_questions WHERE id = $question_id";
        $result_correct_answer = $conn->query($sql_correct_answer);

        if (!$result_correct_answer) {
            $_SESSION['notification_message'] = "SQL Error: " . $conn->error;
            header("Location: take_quiz.php?quiz_id=" . $quiz_id);
            exit();
        }

        if ($result_correct_answer->num_rows == 0) {
            continue; // Skip if no such question exists
        }

        $correct_answer_row = $result_correct_answer->fetch_assoc();
        if ($correct_answer_row['correct_answer'] == $answer) {
            $correct_answers++;
        }
    }

    $score = ($correct_answers / $total_questions) * 100;

    // Save score to the database
    $student_username = $_SESSION['username'];
    $stmt_insert_result = $conn->prepare("INSERT INTO quiz_results (student_username, quiz_id, score) VALUES (?, ?, ?)");
    $stmt_insert_result->bind_param("sid", $student_username, $quiz_id, $score);

    if ($stmt_insert_result->execute()) {
        $_SESSION['notification_message'] = "You scored $score%! Your result has been saved.";
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

            <button type="submit">Submit</button>
        </form>

        <div class="notification" id="notification">
            <p>Time is up! Your quiz is automatically submitted.</p>
        </div>

        <div class="loading" id="loading">
            <div class="spinner"></div>
        </div>
    </main>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var timeLimit = <?php echo $time_limit; ?>; // Time limit in seconds
    var timerElement = document.getElementById('timer');
    var formElement = document.getElementById('quizForm');
    var notificationElement = document.getElementById('notification');

    function updateTimer(seconds) {
        var minutes = Math.floor(seconds / 60);
        var remainingSeconds = seconds % 60;
        timerElement.textContent = minutes + "m " + remainingSeconds + "s";
    }

    function countdown() {
        var remainingTime = timeLimit;
        updateTimer(remainingTime);

        var interval = setInterval(function() {
            remainingTime--;
            updateTimer(remainingTime);

            if (remainingTime <= 0) {
                clearInterval(interval);
                notificationElement.classList.add('show');
                setTimeout(function() {
                    formElement.submit(); // Automatically submit the form after notification
                }, 2000); // Delay to show the notification before redirecting
            }
        }, 1000);
    }

    countdown(); // Start the timer

    // Handle form submission with redirection
    formElement.addEventListener('submit', function() {
        // Disable the timer when the form is submitted manually
        clearInterval(interval);
    });
});
</script>

</body>
</html>

