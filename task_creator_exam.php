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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $time_limit = intval($_POST['time_limit']);
    $date = $_POST['date'];
    $time = $_POST['time'];
    $deadline = $date . ' ' . $time;
    $subject = $_POST['subject']; // New subject selection
    $professor_username = $_SESSION['username'];

    // Get professor ID
    $stmt_professor = $conn->prepare("SELECT id FROM professors WHERE username = ?");
    $stmt_professor->bind_param("s", $professor_username);
    $stmt_professor->execute();
    $result_professor = $stmt_professor->get_result();
    
    if ($result_professor->num_rows == 0) {
        echo "<script>window.location.href = 'admin_dashboard.php?status=error&message=Professor not found';</script>";
        exit();
    }
    
    $professor_id = $result_professor->fetch_assoc()['id'];
    
    // Insert exam
    $stmt_exam = $conn->prepare("INSERT INTO exams (title, time_limit, created_by, deadline, subject) VALUES (?, ?, ?, ?, ?)");
    $stmt_exam->bind_param("siiss", $title, $time_limit, $professor_id, $deadline, $subject);
    
    if (!$stmt_exam->execute()) {
        echo "<script>window.location.href = 'admin_dashboard.php?status=error&message=" . urlencode($stmt_exam->error) . "';</script>";
        exit();
    }
    
    $exam_id = $stmt_exam->insert_id; // Get the ID of the newly created exam

    // Process and Insert Exam Questions
    foreach ($_POST['questions'] as $index => $question) {
        $question_type = $question['question_type'];
        $question_text = $question['question_text'];
        $choice_a = $question['choice_a'] ?? null;
        $choice_b = $question['choice_b'] ?? null;
        $choice_c = $question['choice_c'] ?? null;
        $choice_d = $question['choice_d'] ?? null;
        $correct_answer = null;

        // Handle true/false questions specifically
        if ($question_type == 'true_false') {
            $correct_answer = $question['correct_answer'] ?? null;
        } elseif ($question_type == 'identification') {
            $correct_answer = $question['identification_answer'] ?? null; // Identification questions
        } else {
            // For other question types
            $correct_answer = $question['correct_answer'] ?? null;
        }
        
        $stmt_question = $conn->prepare("INSERT INTO exam_questions (exam_id, question_type, question_text, choice_a, choice_b, choice_c, choice_d, correct_answer) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_question->bind_param("isssssss", $exam_id, $question_type, $question_text, $choice_a, $choice_b, $choice_c, $choice_d, $correct_answer);
        
        if (!$stmt_question->execute()) {
            echo "<script>window.location.href = 'admin_dashboard.php?status=error&message=" . urlencode($stmt_question->error) . "';</script>";
            exit();
        }
    }

    // Create notifications for all students
    $stmt_students = $conn->prepare("SELECT id FROM students");
    $stmt_students->execute();
    $result_students = $stmt_students->get_result();
    
    if ($result_students->num_rows > 0) {
        $stmt_notification = $conn->prepare("
            INSERT INTO notifications (student_id, is_read, activity_type, activity_title) 
            VALUES (?, 0, 'Exam', ?)
        ");
        while ($student = $result_students->fetch_assoc()) {
            $student_id = $student['id'];
            $stmt_notification->bind_param("is", $student_id, $title);
            $stmt_notification->execute();
        }
    }

    echo "<script>window.location.href = 'admin_dashboard.php?status=success&message=Exam created successfully!';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Exam - SkoolTech</title>
    <link rel="stylesheet" href="./dist/scss/main.min.css">
    <link rel="icon" href="./dist/img/skooltech-icon.png">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
</head>
<body>
    <div class="grid-container">
        <div class="header">
            <div class="container">
                <div class="header__wrapper">
                    <!-- Header Content -->
                </div>
            </div>
        </div>

        <div id="sidenav" class="sidenav">
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

        <main class="main-container">
            <!-- <div class="task__banner">
                <div class="container">
                    <div class="task__banner__wrapper">
                        <div class="task__banner__text">
                            <h2>Task Creator - Exam</h2>
                            <p>Welcome to the Task Creator for Assignments! Create, manage, and customize assignments with ease. Set due dates, grading criteria, and assign subjects, all while ensuring seamless tracking of student progress and performance.</p>
                        </div>
                    </div>
                </div>
            </div> -->
            <form action="task_creator_exam.php" method="POST" id="exam-form">
                <div class="exam_details">
                    <label for="title">Exam Title:</label>
                    <input type="text" name="title" id="title" required><br>

                    <label for="time_limit">Time Limit:</label>
                    <select name="time_limit" id="time_limit" required>
                        <option value="1">1 minute</option>
                        <option value="5">5 minutes</option>
                        <option value="10">10 minutes</option>
                        <option value="15">15 minutes</option>
                        <option value="20">20 minutes</option>
                    </select><br>

                    <label for="date">Deadline Date:</label>
                    <input type="date" name="date" id="date" required><br>

                    <label for="time">Deadline Time:</label>
                    <input type="time" name="time" id="time" required><br>

                    <label for="subject">Subject:</label>
                    <select name="subject" id="subject" required>
                        <option value="English">English</option>
                        <option value="Science">Science</option>
                        <option value="Math">Math</option>
                    </select><br>
                </div>

                <div id="questions-container">
                    <div class="question-form" id="question-form-1">
                        <h4>Question 1</h4>
                        <label for="question_type_1">Question Type:</label>
                        <select name="questions[1][question_type]" id="question_type_1" required>
                            <option value="multiple_choice">Multiple Choice</option>
                            <option value="identification">Identification</option>
                            <option value="true_false">True/False</option>
                        </select><br>

                        <label for="question_text_1">Question Text:</label>
                        <input type="text" name="questions[1][question_text]" id="question_text_1" required><br>
                        
                        <div id="choices_1">
                            <label for="choice_a_1">Choice A:</label>
                            <input type="text" name="questions[1][choice_a]" id="choice_a_1"><br>

                            <label for="choice_b_1">Choice B:</label>
                            <input type="text" name="questions[1][choice_b]" id="choice_b_1"><br>

                            <label for="choice_c_1">Choice C:</label>
                            <input type="text" name="questions[1][choice_c]" id="choice_c_1"><br>

                            <label for="choice_d_1">Choice D:</label>
                            <input type="text" name="questions[1][choice_d]" id="choice_d_1"><br>

                            <label for="correct_answer_1">Correct Answer:</label>
                            <select name="questions[1][correct_answer]" id="correct_answer_1">
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                            </select><br><br>
                        </div>

                        <div id="truefalse_1" style="display:none;">
                            <label for="true_false_1">Correct Answer:</label>
                            <label><input type="radio" name="questions[1][correct_answer]" value="true"> True</label>
                            <label><input type="radio" name="questions[1][correct_answer]" value="false"> False</label>
                        </div>

                        <div id="identification_1" style="display:none;">
                            <label for="identification_answer_1">Identification Answer:</label>
                            <input type="text" name="questions[1][identification_answer]" id="identification_answer_1"><br>
                        </div>
                    </div>
                </div>

                <div class="quiz_buttons">
                    <a href="#" id="add-question">Add Another Question</a>
                    <button type="submit" id="quiz-create">Create Exam</button>
                </div>
                <!-- <button type="button" id="add-question">Add Another Question</button> -->
            </form>
        </main>
    </div>

    <script src="./dist/js/dropdown.js"></script>
    <script>
        document.getElementById('add-question').addEventListener('click', function() {
            const questionCount = document.querySelectorAll('.question-form').length + 1;
            const newQuestionForm = document.createElement('div');
            newQuestionForm.classList.add('question-form');
            newQuestionForm.id = 'question-form-' + questionCount;
            newQuestionForm.innerHTML = `
                <h4>Question ${questionCount}</h4>
                <label for="question_type_${questionCount}">Question Type:</label>
                <select name="questions[${questionCount}][question_type]" id="question_type_${questionCount}" required>
                    <option value="multiple_choice">Multiple Choice</option>
                    <option value="identification">Identification</option>
                    <option value="true_false">True/False</option>
                </select><br>
                
                <label for="question_text_${questionCount}">Question Text:</label>
                <input type="text" name="questions[${questionCount}][question_text]" id="question_text_${questionCount}" required><br>
                
                <div id="choices_${questionCount}">
                    <label for="choice_a_${questionCount}">Choice A:</label>
                    <input type="text" name="questions[${questionCount}][choice_a]" id="choice_a_${questionCount}"><br>

                    <label for="choice_b_${questionCount}">Choice B:</label>
                    <input type="text" name="questions[${questionCount}][choice_b]" id="choice_b_${questionCount}"><br>

                    <label for="choice_c_${questionCount}">Choice C:</label>
                    <input type="text" name="questions[${questionCount}][choice_c]" id="choice_c_${questionCount}"><br>

                    <label for="choice_d_${questionCount}">Choice D:</label>
                    <input type="text" name="questions[${questionCount}][choice_d]" id="choice_d_${questionCount}"><br>

                    <label for="correct_answer_${questionCount}">Correct Answer:</label>
                    <select name="questions[${questionCount}][correct_answer]" id="correct_answer_${questionCount}">
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                    </select><br><br>
                </div>

                <div id="truefalse_${questionCount}" style="display:none;">
                    <label for="true_false_${questionCount}">Correct Answer:</label>
                    <label><input type="radio" name="questions[${questionCount}][correct_answer]" value="true"> True</label>
                    <label><input type="radio" name="questions[${questionCount}][correct_answer]" value="false"> False</label>
                </div>

                <div id="identification_${questionCount}" style="display:none;">
                    <label for="identification_answer_${questionCount}">Identification Answer:</label>
                    <input type="text" name="questions[${questionCount}][identification_answer]" id="identification_answer_${questionCount}"><br>
                </div>
            `;
            document.getElementById('questions-container').appendChild(newQuestionForm);
        });

        document.getElementById('questions-container').addEventListener('change', function(e) {
            if (e.target.matches('[id^="question_type_"]')) {
                const questionId = e.target.id.split('_')[2];
                const questionType = e.target.value;

                document.getElementById(`choices_${questionId}`).style.display = questionType === 'multiple_choice' ? 'block' : 'none';
                document.getElementById(`truefalse_${questionId}`).style.display = questionType === 'true_false' ? 'block' : 'none';
                document.getElementById(`identification_${questionId}`).style.display = questionType === 'identification' ? 'block' : 'none';
            }
        });
    </script>
</body>
</html>
