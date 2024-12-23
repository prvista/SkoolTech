<?php
session_start();

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

// Query to get student results along with GWA for each subject and total GWA
$sql_results = "
SELECT 
    s.student_number, 
    s.name, 

    -- Quiz, Exam, and Assignment Averages
    ROUND(COALESCE(SUM(qr.score) / GREATEST(COUNT(qr.id), 1), 0), 2) AS quiz_average,
    ROUND(COALESCE(SUM(er.score) / GREATEST(COUNT(er.id), 1), 0), 2) AS exam_average,
    ROUND(COALESCE(SUM(ss.assignment_score) / GREATEST(COUNT(ss.id), 1), 0), 2) AS assignment_average,

    -- General Weighted Average
    ROUND(
        (
            COALESCE(SUM(qr.score) / GREATEST(COUNT(qr.id), 1), 0) + 
            COALESCE(SUM(ss.assignment_score) / GREATEST(COUNT(ss.id), 1), 0) + 
            COALESCE(SUM(er.score) / GREATEST(COUNT(er.id), 1), 0)
        ) / 3, 2
    ) AS gwa,

    -- Subject-wise GWA
    ROUND(AVG(CASE WHEN ss.subject = 'English' THEN (ss.assignment_score + ss.quiz_score + ss.exam_score) / 3 END), 2) AS english_gwa,
    ROUND(AVG(CASE WHEN ss.subject = 'Math' THEN (ss.assignment_score + ss.quiz_score + ss.exam_score) / 3 END), 2) AS math_gwa,
    ROUND(AVG(CASE WHEN ss.subject = 'Science' THEN (ss.assignment_score + ss.quiz_score + ss.exam_score) / 3 END), 2) AS science_gwa,

    -- Total GWA
    ROUND(
        (
            COALESCE(AVG(CASE WHEN ss.subject = 'English' THEN (ss.assignment_score + ss.quiz_score + ss.exam_score) / 3 END), 0) +
            COALESCE(AVG(CASE WHEN ss.subject = 'Math' THEN (ss.assignment_score + ss.quiz_score + ss.exam_score) / 3 END), 0) +
            COALESCE(AVG(CASE WHEN ss.subject = 'Science' THEN (ss.assignment_score + ss.quiz_score + ss.exam_score) / 3 END), 0)
        ) / 3, 2
    ) AS total_gwa

FROM 
    students s
LEFT JOIN 
    quiz_results qr ON s.id = qr.student_id
LEFT JOIN 
    exam_results er ON s.id = er.student_id
LEFT JOIN 
    subject_scores ss ON s.id = ss.student_id
GROUP BY 
    s.student_number, s.name;
";

$result_results = $conn->query($sql_results);

// Check if results were fetched
if (!$result_results) {
    die("Error fetching quiz and exam results: " . $conn->error);
}

// Initialize an array to store student results
$student_results = [];
$ranked_results = [];

// Fetch the student results and store them in the array
if ($result_results->num_rows > 0) {
    while ($row = $result_results->fetch_assoc()) {
        $student_results[] = [
            'student_number' => $row['student_number'],
            'name' => $row['name'],
            'quiz_average' => $row['quiz_average'],
            'exam_average' => $row['exam_average'],
            'assignment_average' => $row['assignment_average'],
            'total_gwa' => $row['total_gwa'],
            'english_gwa' => $row['english_gwa'],
            'math_gwa' => $row['math_gwa'],
            'science_gwa' => $row['science_gwa'],
        ];
        
        // For ranking (GWA)
        $ranked_results[] = [
            'name' => $row['name'],
            'gwa' => $row['total_gwa']
        ];
    }
} else {
    echo "<tr><td colspan='6'>No results found</td></tr>";
}

// Rank the results by GWA
usort($ranked_results, fn($a, $b) => $b['gwa'] <=> $a['gwa']);

// Initialize grade distribution counts
$gradeDistribution = [
    'A' => 0,
    'B' => 0,
    'C' => 0,
    'D' => 0,
    'F' => 0
];

foreach ($student_results as $row) {
    $overall_percentage = ($row['quiz_average'] + $row['exam_average'] + $row['assignment_average']) / 3;

    if ($overall_percentage >= 90) {
        $gradeDistribution['A']++;
    } elseif ($overall_percentage >= 80) {
        $gradeDistribution['B']++;
    } elseif ($overall_percentage >= 70) {
        $gradeDistribution['C']++;
    } elseif ($overall_percentage >= 60) {
        $gradeDistribution['D']++;
    } else {
        $gradeDistribution['F']++;
    }
}
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        <h2>Average Grade Analysis</h2>
<table border="1">
    <tr>
        <th>Student Number</th>
        <th>Student Name</th>
        <th>English GWA</th>
        <th>Math GWA</th>
        <th>Science GWA</th>
        <th>Total GWA</th>
    </tr>
    <?php
    // Sort by numeric part of student number
    usort($student_results, function ($a, $b) {
        // Extract numeric part from 'STU001', 'STU002', etc.
        $numA = (int) preg_replace('/[^0-9]/', '', $a['student_number']);
        $numB = (int) preg_replace('/[^0-9]/', '', $b['student_number']);
        return $numA - $numB;
    });

    // Display the sorted results
    if (count($student_results) > 0) {
        foreach ($student_results as $row) {
            echo "<tr>
                <td>{$row['student_number']}</td>
                <td>{$row['name']}</td>
                <td>{$row['english_gwa']}</td>
                <td>{$row['math_gwa']}</td>
                <td>{$row['science_gwa']}</td>
                <td>{$row['total_gwa']}</td>
            </tr>";
        }
    }
    ?>
</table>


            <!-- Graph for Average Grade Analysis -->
            <canvas id="averageGradeChart" width="40" height="10"></canvas>
            <script>
                const ctxAvg = document.getElementById('averageGradeChart').getContext('2d');
                const averageGradeChart = new Chart(ctxAvg, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode(array_column($student_results, 'name')); ?>,
                        datasets: [{
                            label: 'Total GWA',
                            data: <?php echo json_encode(array_column($student_results, 'total_gwa')); ?>,
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100
                            }
                        }
                    }
                });
            </script>

            <br>
            <h2>Grade Distribution</h2>
            <table border="1">
                <tr>
                    <th>A</th>
                    <th>B</th>
                    <th>C</th>
                    <th>D</th>
                    <th>F</th>
                </tr>
                <tr>
                    <td><?php echo $gradeDistribution['A']; ?></td>
                    <td><?php echo $gradeDistribution['B']; ?></td>
                    <td><?php echo $gradeDistribution['C']; ?></td>
                    <td><?php echo $gradeDistribution['D']; ?></td>
                    <td><?php echo $gradeDistribution['F']; ?></td>
                </tr>
            </table>

            <!-- Graph for Grade Distribution -->
            <canvas id="gradeDistributionChart" width="40" height="10"></canvas>
            <script>
                const ctxGradeDist = document.getElementById('gradeDistributionChart').getContext('2d');
                const gradeDistributionChart = new Chart(ctxGradeDist, {
                    type: 'bar',
                    data: {
                        labels: ['A', 'B', 'C', 'D', 'F'],
                        datasets: [{
                            label: 'Number of Students',
                            data: <?php echo json_encode(array_values($gradeDistribution)); ?>,
                            backgroundColor: ['rgba(75, 192, 192, 0.2)', 'rgba(153, 102, 255, 0.2)', 'rgba(255, 159, 64, 0.2)', 'rgba(54, 162, 235, 0.2)', 'rgba(255, 99, 132, 0.2)'],
                            borderColor: ['rgba(75, 192, 192, 1)', 'rgba(153, 102, 255, 1)', 'rgba(255, 159, 64, 1)', 'rgba(54, 162, 235, 1)', 'rgba(255, 99, 132, 1)'],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            </script>

    <br>
    <h2>Gamification</h2>
            <table border="1">
                <tr>
                    <th>Rank</th>
                    <th>Student Name</th>
                    <th>Total GWA</th>
                </tr>

                <?php
                $rank = 1;
                foreach ($ranked_results as $result) {
                    echo "<tr>
                        <td>{$rank}</td>
                        <td>{$result['name']}</td>
                        <td>{$result['gwa']}</td>
                    </tr>";
                    $rank++;
                }
                ?>
            </table>

            <!-- Graph for Ranking -->
            <div style="display: flex; justify-content: center; align-items: center; width: 100%;">
                <canvas id="rankingChart" width="400" height="200" style="display: block; margin: 0 auto;"></canvas>
            </div>
            
            <script src="./dist/js/dropdown.js"></script>
            <script>
                const ctxRank = document.getElementById('rankingChart').getContext('2d');
                const rankingChart = new Chart(ctxRank, {
                    type: 'pie',
                    data: {
                        labels: <?php echo json_encode(array_column($ranked_results, 'name')); ?>,
                        datasets: [{
                            data: <?php echo json_encode(array_column($ranked_results, 'gwa')); ?>,
                            backgroundColor: ['rgba(255, 99, 132, 0.2)', 'rgba(75, 192, 192, 0.2)', 'rgba(153, 102, 255, 0.2)', 'rgba(255, 159, 64, 0.2)'],
                            borderColor: ['rgba(255, 99, 132, 1)', 'rgba(75, 192, 192, 1)', 'rgba(153, 102, 255, 1)', 'rgba(255, 159, 64, 1)'],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true
                    }
                });
            </script>
        </main>
    </div>
</body>
</html>

<?php
$conn->close();
?>
