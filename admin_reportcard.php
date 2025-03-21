<?php
session_start();

// Check if user is logged in and is a professor
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

// Initialize reportCard variable
$reportCard = [
    'name' => '',
    'subjects' => [],
    'gwa' => 0
];

// Fetch all students for selection
$sql_students = "SELECT id, name, student_number FROM students";
$result_students = $conn->query($sql_students);

// Process report card generation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['student_id'])) {
    $student_id = $_POST['student_id'];

    // Prepare statement to get scores from subject_scores
    $sql_student_report = "SELECT s.name, ss.subject, ss.assignment_score, ss.quiz_score, ss.exam_score 
    FROM students s 
    JOIN subject_scores ss ON s.id = ss.student_id 
    WHERE s.id = ?";
    
    $stmt = $conn->prepare($sql_student_report);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result_student_report = $stmt->get_result();

    // Check if results are returned
    if ($result_student_report->num_rows > 0) {
        while ($row = $result_student_report->fetch_assoc()) {
            if (empty($reportCard['name'])) {
                $reportCard['name'] = $row['name'];
            }

            // Store scores by subject
            $reportCard['subjects'][$row['subject']][] = [
                'assignment_score' => $row['assignment_score'],
                'quiz_score' => $row['quiz_score'],
                'exam_score' => $row['exam_score']
            ];
        }
    } else {
        $reportCard['name'] = 'No scores available for this student.';
    }

    // Calculate GWA
    if (!empty($reportCard['subjects'])) {
        $totalScore = 0;
        $totalSubjects = 0;

        foreach ($reportCard['subjects'] as $subject => $scores) {
            $assignmentAvg = !empty($scores) ? array_sum(array_column($scores, 'assignment_score')) / count($scores) : 0;
            $quizAvg = !empty($scores) ? array_sum(array_column($scores, 'quiz_score')) / count($scores) : 0;
            $examAvg = !empty($scores) ? array_sum(array_column($scores, 'exam_score')) / count($scores) : 0;

            // Calculate average score for the subject
            $averageScore = ($assignmentAvg + $quizAvg + $examAvg) / 3;
            $totalScore += $averageScore;
            $totalSubjects++;
        }

        $reportCard['gwa'] = $totalSubjects > 0 ? round($totalScore / $totalSubjects, 2) : 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Cards - SkoolTech</title>
    <link rel="stylesheet" href="./dist/scss/main.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <link rel="icon" href="./dist/img/skooltech-icon.png">
    <style>
        .report-card {
            border: 2px solid #000;
            padding: 20px;
            margin: 20px;
            text-align: center;
        }
        .report-card h3 {
            margin-bottom: 10px;
        }
        .report-card table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .report-card table, th, td {
            border: 1px solid #000;
        }
        th, td {
            padding: 10px;
        }
        .footer {
            margin-top: 20px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="grid-container">
        <!-- Header -->
        <header class="header">
            <div class="container">
                <div class="header__wrapper">

                </div>
            </div>
        </header>

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
            <!-- Student selection form and report card display -->
            <div class="task__banner">
                <div class="task__banner__wrapper">
                    <div class="task__banner__text">
                        <h2>Report Card</h2>
                        <p>Welcome to the Admin Report Card Dashboard! Efficiently manage and view student report cards, track academic performance, and generate detailed reports for each student to ensure smooth academic monitoring and evaluation.</p>
                    </div>
                </div>
            </div>

            <form class="reportcard-select" method="POST" action="">
                <label for="student">Select Student:</label>
                <select name="student_id" id="student" required>
                    <?php while ($student = $result_students->fetch_assoc()): ?>
                        <option value="<?php echo $student['id']; ?>">
                            <?php echo htmlspecialchars($student['name']); ?> (<?php echo htmlspecialchars($student['student_number']); ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
                <input class="report-card-btn" type="submit" value="Generate Report Card">
            </form>

            <?php if (!empty($reportCard['name'])): ?>
            <div class="report-card">
            <img src="./dist/img/Laguna_College_Logo.png" alt="SkoolTech Logo" style="max-width: 100px;">
            <h1>Laguna College</h1>
                <h3><?php echo htmlspecialchars($reportCard['name']); ?>'s Report Card</h3>                
                <table>
                    <tr>
                        <th>Subject</th>
                        <th>Assignment Score (%)</th>
                        <th>Quiz Score (%)</th>
                        <th>Exam Score (%)</th>
                    </tr>
                    <?php foreach ($reportCard['subjects'] as $subject => $scores): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($subject); ?></td>
                            <td><?php echo !empty($scores) ? round(array_sum(array_column($scores, 'assignment_score')) / count($scores), 2) : 'N/A'; ?></td>
                            <td><?php echo !empty($scores) ? round(array_sum(array_column($scores, 'quiz_score')) / count($scores), 2) : 'N/A'; ?></td>
                            <td><?php echo !empty($scores) ? round(array_sum(array_column($scores, 'exam_score')) / count($scores), 2) : 'N/A'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <p>GWA: <?php echo htmlspecialchars($reportCard['gwa']); ?></p>
                <div class="footer">
                    <p>Date Issued: <?php echo date('F j, Y'); ?></p>
                </div>

                <form method="POST" action="generate_pdf.php">
                    <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                    <input class="pdf-btn" type="submit" name="download_pdf" value="Download PDF">
                </form>


            </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="./dist/js/dropdown.js"></script>

    <script>
        window.onload = function() {
            const notification = document.getElementById('notification');
            if (notification && notification.classList.contains('show')) {
                setTimeout(function() {
                    notification.classList.remove('show');
                    notification.classList.add('hide');
                }, 3000);
            }
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>
