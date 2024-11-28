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

// Get all students
$sql_students = "SELECT * FROM students";
$result_students = $conn->query($sql_students);


$sql_results = "SELECT s.student_number, s.name, s.username,
                COALESCE(SUM(qr.score) / IFNULL(COUNT(qr.id), 1), 0) AS quiz_average,
                COALESCE(SUM(er.score) / IFNULL(COUNT(er.id), 1), 0) AS exam_average,
                COALESCE(SUM(ss.assignment_score) / IFNULL(COUNT(ss.id), 1), 0) AS assignment_average,  -- Get from subject_scores
                (COALESCE(SUM(qr.score) / IFNULL(COUNT(qr.id), 1), 0) + 
                COALESCE(SUM(ss.assignment_score) / IFNULL(COUNT(ss.id), 1), 0) + 
                COALESCE(SUM(er.score) / IFNULL(COUNT(er.id), 1), 0)) / 3 AS gwa
                FROM students s
                LEFT JOIN quiz_results qr ON s.id = qr.student_id
                LEFT JOIN exam_results er ON s.id = er.student_id
                LEFT JOIN subject_scores ss ON s.id = ss.student_id
                GROUP BY s.student_number, s.name, s.username";

$result_results = $conn->query($sql_results);

// Check if results were fetched
if (!$result_results) {
    die("Error fetching quiz and exam results: " . $conn->error);
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
                    <th>Name</th>
                    <th>Assignment Average</th>
                    <th>Quiz Average</th>
                    <th>Exam Average</th>
                    <th>Overall Average (GWA)</th>
                </tr>

                <?php
                $student_results = [];
                
                if ($result_results->num_rows > 0) {
                    while ($row = $result_results->fetch_assoc()) {
                        $student_results[] = $row;

                        // Output the data for each student
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['student_number']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                        echo "<td>" . htmlspecialchars(number_format($row['assignment_average'], 2)) . "</td>";
                        echo "<td>" . htmlspecialchars(number_format($row['quiz_average'], 2)) . "</td>";
                        echo "<td>" . htmlspecialchars(number_format($row['exam_average'], 2)) . "</td>";
                        echo "<td>" . htmlspecialchars(number_format($row['gwa'], 2)) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>No results found</td></tr>";
                }
                ?>
            </table>

            <br>
            <h2>Gamification</h2>
            <table border="1">
                <tr>
                    <th>Ranking</th>
                    <th>Name</th>
                    <th>GWA</th>
                </tr>
                <?php
                // Store the GWA and names in an array for ranking
                $ranked_results = [];
                if ($result_results->num_rows > 0) {
                    // Reset the result pointer to fetch data again
                    $result_results->data_seek(0);

                    // Store data into array for sorting
                    while ($row = $result_results->fetch_assoc()) {
                        $ranked_results[] = $row;
                    }

                    // Sort the array based on GWA in descending order
                    usort($ranked_results, function($a, $b) {
                        return $b['gwa'] <=> $a['gwa']; // Sort by GWA in descending order
                    });

                    // Display the sorted students with their rankings
                    $rank = 1;
                    foreach ($ranked_results as $row) {
                        echo "<tr>";
                        echo "<td>" . $rank . "</td>"; // Display rank
                        echo "<td>" . htmlspecialchars($row['name']) . "</td>"; // Display student name
                        echo "<td>" . htmlspecialchars(number_format($row['gwa'], 2)) . "</td>"; // Display GWA
                        echo "</tr>";
                        $rank++;
                    }
                } else {
                    echo "<tr><td colspan='6'>No results found</td></tr>";
                }
                ?>
            </table>

            <br>
            <h2>Grade Distribution Analysis</h2>
            <table border="1">
                <tr>
                    <th>90-100 (A)</th>
                    <th>80-89 (B)</th>
                    <th>70-79 (C)</th>
                    <th>60-69 (D)</th>
                    <th>0-59 (F)</th>
                </tr>

                <?php
                // Initialize the counts for each grade range
                $gradeA = 0;
                $gradeB = 0;
                $gradeC = 0;
                $gradeD = 0;
                $gradeF = 0;

                // Initialize the $gradeDistribution array
                $gradeDistribution = [
                    'A' => 0,
                    'B' => 0,
                    'C' => 0,
                    'D' => 0,
                    'F' => 0
                ];

                if (count($student_results) > 0) {
                    foreach ($student_results as $row) {
                        // Calculate the overall percentage as an average of quiz and exam scores
                        $overall_percentage = ($row['quiz_average'] + $row['exam_average'] + $row['assignment_average']) / 3;

                        // Categorize the overall percentage into grade ranges
                        if ($overall_percentage >= 90) {
                            $gradeA++;
                            $gradeDistribution['A']++;
                        } elseif ($overall_percentage >= 80) {
                            $gradeB++;
                            $gradeDistribution['B']++;
                        } elseif ($overall_percentage >= 70) {
                            $gradeC++;
                            $gradeDistribution['C']++;
                        } elseif ($overall_percentage >= 60) {
                            $gradeD++;
                            $gradeDistribution['D']++;
                        } else {
                            $gradeF++;
                            $gradeDistribution['F']++;
                        }
                    }

                    // Output the grade distribution
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($gradeA) . "</td>";
                    echo "<td>" . htmlspecialchars($gradeB) . "</td>";
                    echo "<td>" . htmlspecialchars($gradeC) . "</td>";
                    echo "<td>" . htmlspecialchars($gradeD) . "</td>";
                    echo "<td>" . htmlspecialchars($gradeF) . "</td>";
                    echo "</tr>";
                } else {
                    echo "<tr><td colspan='5'>No results found</td></tr>";
                }
                ?>
            </table>



            <br>
            <br>
           
            <canvas id="gradeDistributionChart" width="40" height="10"></canvas>
            <script src="./dist/js/dropdown.js"></script>




            
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                // Prepare data for the grade distribution chart
                const gradeDistribution = <?php echo json_encode($gradeDistribution); ?>;

                // Set up the chart data
                const data = {
                    labels: ['A', 'B', 'C', 'D', 'F'],
                    datasets: [{
                        label: 'Number of Students',
                        data: [gradeDistribution['A'], gradeDistribution['B'], gradeDistribution['C'], gradeDistribution['D'], gradeDistribution['F']],
                        backgroundColor: ['#4CAF50', '#FF9800', '#FFEB3B', '#FF5722', '#F44336'],
                        borderColor: ['#388E3C', '#F57C00', '#FBC02D', '#D32F2F', '#D32F2F'],
                        borderWidth: 1
                    }]
                };

                // Configure the chart options
                const config = {
                    type: 'bar',
                    data: data,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                };

                // Render the chart
                const ctx = document.getElementById('gradeDistributionChart').getContext('2d');
                const gradeDistributionChart = new Chart(ctx, config);
            </script>

        </main>
    </div>
</body>
</html>
