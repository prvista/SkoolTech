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

// Fetch exam details
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
$student_name = $student['name']; // Fetch the student's name

// Fetch questions for the exam
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

    $answers = $_POST['answers'];
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

        // Handle answer checking
        if ($question_type === 'true_false') {
            if (strtolower($correct_answer) === strtolower($answer)) {
                $correct_answers++;
            }
        } elseif ($question_type === 'multiple_choice' && $correct_answer == $answer) {
            $correct_answers++;
        } elseif ($question_type === 'identification' && strtolower(trim($correct_answer)) == strtolower(trim($answer))) {
            $correct_answers++;
        }
    }

    $score = ($correct_answers / $total_questions) * 100;

    // Fetch the subject for this exam
    $subject = $exam['subject'];

   // Save score to the exam_results table
$stmt_insert_result = $conn->prepare("INSERT INTO exam_results (student_id, exam_id, score, subject) VALUES (?, ?, ?, ?)");
$stmt_insert_result->bind_param("iiss", $student_id, $exam_id, $score, $subject);

if ($stmt_insert_result->execute()) {
    // Record that the student has completed the exam
    $stmt_insert_student_exam = $conn->prepare("INSERT INTO student_exams (student_id, exam_id) VALUES (?, ?)");
    $stmt_insert_student_exam->bind_param("ii", $student_id, $exam_id);

    if ($stmt_insert_student_exam->execute()) {
        // Check if a subject score already exists for the student
        $stmt_check_subject_score = $conn->prepare("SELECT * FROM subject_scores WHERE student_id = ? AND subject = ?");
        $stmt_check_subject_score->bind_param("is", $student_id, $subject);
        $stmt_check_subject_score->execute();
        $result_subject_score = $stmt_check_subject_score->get_result();

        if ($result_subject_score->num_rows > 0) {
            // Update existing subject score
            $stmt_update_subject_score = $conn->prepare("UPDATE subject_scores SET exam_score = ? WHERE student_id = ? AND subject = ?");
            $stmt_update_subject_score->bind_param("iis", $score, $student_id, $subject);

            if (!$stmt_update_subject_score->execute()) {
                $_SESSION['notification_message'] = "Error updating subject score: " . $stmt_update_subject_score->error;
            }
        } else {
            // Insert new subject score
            $stmt_insert_subject_score = $conn->prepare("INSERT INTO subject_scores (student_id, student_name, subject, exam_score) VALUES (?, ?, ?, ?)");
            $stmt_insert_subject_score->bind_param("issi", $student_id, $student_name, $subject, $score);

            if ($stmt_insert_subject_score->execute()) {
                $_SESSION['notification_message'] = "You scored $score%! Your result has been saved!";
            } else {
                $_SESSION['notification_message'] = "Error storing subject score: " . $stmt_insert_subject_score->error;
            }
        }
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
         /* Timer */
         .timer {
            font-size: 1.2em;
        }

        .timer-container {
            display: flex;
            background-color: #ffcc00;
            padding: 10px;
            border-radius: 5px;
            width: 160px;
            margin-bottom: 20px;
            align-items: center;
            gap: 1rem;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 5px solid #f3f3f3; /* Light gray background */
            border-top: 5px solid #28a745; /* Green spinner */
            border-radius: 50%;
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Form Styling */
        form {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            position: relative;
        }
        .question {
            margin-bottom: 20px;
        }
        .question p {
            font-size: 1.1rem;
            font-weight: 500;
            color: #333;
            margin-bottom: .5rem;
        }
        .question label {
            display: block;
            margin-bottom: 10px;
            font-size: 1rem;
            color: #555;
        }
        .question input[type="radio"],
        .question input[type="text"] {
            margin-right: 10px;
        }
        .question input[type="text"] {
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ddd;
            width: 100%;
        }

        button[type="submit"] {
            background-color: #28a745;
            color: white;
            font-size: 1.1rem;
            border: none;
            padding: 15px 30px;
            border-radius: 5px;
            cursor: pointer;
            width: 10%;
            align-self: flex-end; 
            transition: background-color 0.3s ease;
        }
        button[type="submit"]:hover {
            background-color: #218838;
        }

        /* Notification */
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

        /* Loading Spinner */
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

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            #sidenav {
                width: 200px;
            }
            .main-container {
                margin-left: 220px;
            }
        }

        @media screen and (max-width: 576px) {
            #sidenav {
                width: 100%;
                position: static;
            }
            .main-container {
                margin-left: 0;
            }
            .header__wrapper {
                flex-direction: column;
                align-items: flex-start;
            }
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
                            <li><a href="./student_assignments.php">Assignment</a></li>
                            <li><a href="./task_quiz.php">Quiz</a></li>
                            <li><a href="./task_exam.php">Exam</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="./user_subjects.php"><span class="material-icons-outlined">library_books</span> Subjects</a>
                    </li>
                    <li><a href="user_virtualroom.php"><span class="material-icons-outlined">video_call</span>Virtual Room</a></li>
                    <li><a href="logout.php"><span class="material-icons-outlined">logout</span>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>

    <main class="main-container">
        <h2>Exam</h2>


        <div class="timer-container">
            <div class="timer" id="timer"></div>
            <div class="spinner"></div>
        </div>

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
