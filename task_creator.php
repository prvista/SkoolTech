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
    
    // Insert quiz
    $stmt_quiz = $conn->prepare("INSERT INTO quizzes (title, time_limit, created_by, deadline) VALUES (?, ?, ?, ?)");
    $stmt_quiz->bind_param("siis", $title, $time_limit, $professor_id, $deadline);
    
    if (!$stmt_quiz->execute()) {
        echo "<script>window.location.href = 'admin_dashboard.php?status=error&message=" . urlencode($stmt_quiz->error) . "';</script>";
        exit();
    }
    
    $quiz_id = $stmt_quiz->insert_id; // Get the ID of the newly created quiz

    // Insert quiz questions
    foreach ($_POST['questions'] as $index => $question) {
        $question_text = $question['question_text'];
        $choice_a = $question['choice_a'];
        $choice_b = $question['choice_b'];
        $choice_c = $question['choice_c'];
        $choice_d = $question['choice_d'];
        $correct_answer = $question['correct_answer'];

        $stmt_question = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question_text, choice_a, choice_b, choice_c, choice_d, correct_answer) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_question->bind_param("issssss", $quiz_id, $question_text, $choice_a, $choice_b, $choice_c, $choice_d, $correct_answer);
        
        if (!$stmt_question->execute()) {
            echo "<script>window.location.href = 'admin_dashboard.php?status=error&message=" . urlencode($stmt_question->error) . "';</script>";
            exit();
        }
    }

    echo "<script>window.location.href = 'admin_dashboard.php?status=success&message=Quiz created successfully!';</script>";
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Quiz - SkoolTech</title>
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
                    <li><a href="#"><span class="material-icons-outlined">app_registration</span>Tasks Creator</a></li>
                    <li><a href="#"><span class="material-icons-outlined">sort</span>Results</a></li>
                    <li><a href="#"><span class="material-icons-outlined">group</span>Students</a></li>
                    <li><a href="logout.php"><span class="material-icons-outlined">logout</span>Logout</a></li>
                </ul>
            </div>
        </div>

        <main class="main-container">
            <h2>Create Quiz</h2>
            <form action="task_creator.php" method="POST" id="quiz-form">
                <!-- quiz details -->
                <div class="quiz_details">
                    <label for="title">Quiz Title:</label>
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
                </div>

                <div id="questions-container">
                    <div class="question-form" id="question-form-1">
                        <h4>Question 1</h4>
                        <label for="question_text_1">Question Text:</label>
                        <input type="text" name="questions[1][question_text]" id="question_text_1" required><br>
                        
                        <label for="choice_a_1">Choice A:</label>
                        <input type="text" name="questions[1][choice_a]" id="choice_a_1" required><br>

                        <label for="choice_b_1">Choice B:</label>
                        <input type="text" name="questions[1][choice_b]" id="choice_b_1" required><br>

                        <label for="choice_c_1">Choice C:</label>
                        <input type="text" name="questions[1][choice_c]" id="choice_c_1" required><br>

                        <label for="choice_d_1">Choice D:</label>
                        <input type="text" name="questions[1][choice_d]" id="choice_d_1" required><br>

                        <label for="correct_answer_1">Correct Answer:</label>
                        <select name="questions[1][correct_answer]" id="correct_answer_1" required>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select><br><br>
                    </div>
                </div>

                <div class="button__wrapper">
                <a href="#" id="add-question-btn">Add Another Question</a>
                <button type="submit" id="submit-quiz-btn">Create Quiz</button>
            </div>
            </form>
        </main> 
    </div>

    <div class="notification" id="notification"></div>
    <div class="loading" id="loading">
        <div class="spinner"></div>
    </div>

    <script src="./dist/js/dropdown.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
        const questionsContainer = document.getElementById('questions-container');
        const addQuestionButton = document.getElementById('add-question-btn');
        const submitQuizButton = document.getElementById('submit-quiz-btn');
        const notificationElement = document.getElementById('notification');
        const loadingElement = document.getElementById('loading');
        const quizForm = document.getElementById('quiz-form');
        let questionCount = 1;

        // Function to add a new question form
        function addQuestionForm() {
            questionCount++;
            const questionForm = document.createElement('div');
            questionForm.className = 'question-form';
            questionForm.id = `question-form-${questionCount}`;
            
            questionForm.innerHTML = `
                <h4>Question ${questionCount}</h4>
                <label for="question_text_${questionCount}">Question Text:</label>
                <input type="text" name="questions[${questionCount}][question_text]" id="question_text_${questionCount}" required><br>
                
                <label for="choice_a_${questionCount}">Choice A:</label>
                <input type="text" name="questions[${questionCount}][choice_a]" id="choice_a_${questionCount}" required><br>

                <label for="choice_b_${questionCount}">Choice B:</label>
                <input type="text" name="questions[${questionCount}][choice_b]" id="choice_b_${questionCount}" required><br>

                <label for="choice_c_${questionCount}">Choice C:</label>
                <input type="text" name="questions[${questionCount}][choice_c]" id="choice_c_${questionCount}" required><br>

                <label for="choice_d_${questionCount}">Choice D:</label>
                <input type="text" name="questions[${questionCount}][choice_d]" id="choice_d_${questionCount}" required><br>

                <label for="correct_answer_${questionCount}">Correct Answer:</label>
                <select name="questions[${questionCount}][correct_answer]" id="correct_answer_${questionCount}" required>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                </select><br><br>
            `;
            
            questionsContainer.appendChild(questionForm);
        }

        // Add question form on click
        addQuestionButton.addEventListener('click', function(e) {
            e.preventDefault();
            addQuestionForm();
        });

        // Function to show loading spinner
        function showLoading() {
            loadingElement.classList.add('show');
        }

        // Function to hide loading spinner
        function hideLoading() {
            loadingElement.classList.remove('show');
        }

        // Add event listener for form submission
        quizForm.addEventListener('submit', function(event) {
            showLoading();

            // Delay the redirection until after the loader has been visible for at least 1.5 seconds
            setTimeout(() => {
                hideLoading();
                quizForm.submit(); // Submit the form manually after the delay
            }, 1500); // 1500 milliseconds = 1.5 seconds
        });
    });
    </script>


</body>
</html>
<?php
$conn->close();
?>
