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

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Feedback message
$feedback_message = "";

// Function to update scores in assignment_submissions
function updateScore($conn, $submission_id, $raw_score, $total_score) {
    // Compute the percentage score
    $grade = ($raw_score / $total_score) * 100;

    // Update the score and grade in the assignment_submissions table
    $sql = "UPDATE assignment_submissions SET score = ?, grade = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ddi", $raw_score, $grade, $submission_id);

    if ($stmt->execute()) {
        updateSubjectScore($conn, $submission_id, $raw_score); // Call function to update subject_scores
        return "Score and grade updated successfully.";
    } else {
        return "Error updating score: " . $stmt->error;
    }
}

// Function to update the subject_scores table with student name
function updateSubjectScore($conn, $submission_id) {
    // Get the student ID, subject, grade, and student name based on submission ID
    $sql = "SELECT asub.student_id, asub.assignment_id, asub.grade, s.name AS student_name
            FROM assignment_submissions asub
            JOIN students s ON asub.student_id = s.id
            WHERE asub.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $submission_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row) {
        $student_id = $row['student_id'];
        $student_name = $row['student_name'];
        $grade = $row['grade'];
        $subject = getSubjectFromAssignment($conn, $row['assignment_id']);

        // Update or insert into subject_scores
        $sql = "INSERT INTO subject_scores (student_id, student_name, subject, assignment_score) 
                VALUES (?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE assignment_score = ?, student_name = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issdds", $student_id, $student_name, $subject, $grade, $grade, $student_name);

        if (!$stmt->execute()) {
            error_log("Error updating subject_scores: " . $stmt->error);
        }
    }
}



// Function to get the subject from the assignment
function getSubjectFromAssignment($conn, $assignment_id) {
    $sql = "SELECT subject FROM assignments WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['subject'] : null;
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission_id = $_POST['submission_id'];
    $raw_score = $_POST['score']; // Raw score input by the professor
    $total_score = $_POST['highest_score']; // Maximum score for the assignment set by the professor

    // Validate the scores
    if (is_numeric($raw_score) && is_numeric($total_score) && $raw_score >= 0 && $raw_score <= $total_score) {
        $feedback_message = updateScore($conn, $submission_id, $raw_score, $total_score);
    } else {
        $feedback_message = "Invalid score. Please ensure the score is numeric and within the allowed range.";
    }
}

// Fetch all assignment submissions along with the file path and total score
$sql = "SELECT a.id AS assignment_id, a.title, asub.id AS submission_id, asub.submission_date, 
               asub.score AS raw_score, asub.grade, asub.submitted_file, a.grade AS total_grade
        FROM assignments a 
        LEFT JOIN assignment_submissions asub ON a.id = asub.assignment_id
        GROUP BY a.id, asub.id";



$result = $conn->query($sql);


$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Assignments</title>
    <link rel="stylesheet" href="./dist/scss/main.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <style>
        /* Add necessary CSS styles here */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            height: 715px;
            position: relative;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        iframe {
            width: 100%;
            height: 95%;
        }

        .feedback-message {
            background-color: #e7f3fe;
            color: #31708f;
            border: 1px solid #bce8f1;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        table {
        width: 100%;
        border-collapse: collapse;
        font-family: 'Roboto', sans-serif;
        font-size: 16px;
        margin-bottom: 20px;
    }

    thead {
        background-color: #f5f5f5;
    }

    th, td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    th {
        font-weight: 700;
        color: #333;
    }

    tr:hover {
        background-color: #f1f1f1;
    }

    tr:nth-child(even) {
        background-color: #fafafa;
    }

    td a {
        color: #007bff;
        text-decoration: none;
        font-weight: 500;
    }

    td a:hover {
        text-decoration: underline;
    }

    .btn-edit, .btn-view {
        padding: 8px 12px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        transition: background-color 0.3s ease;
    }

    .btn-edit:hover, .btn-view:hover {
        background-color: #0056b3;
    }

    .btn-view {
        background-color: #28a745;
    }

    .btn-view:hover {
        background-color: #218838;
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
                    <img src="./dist/img/skooltech-logo.png" alt="SkoolTech Logo">
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
                        <li><a href="#"><span class="material-icons-outlined">sort</span>Results</a></li>
                        <li><a href="#"><span class="material-icons-outlined">group</span>Students</a></li>
                        <li><a href="logout.php"><span class="material-icons-outlined">logout</span>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <main class="main-container">
            <h1>Assignment Submissions</h1>
            <?php if ($feedback_message): ?>
                <div class="feedback-message">
                    <?php echo htmlspecialchars($feedback_message); ?>
                </div>
            <?php endif; ?>
            <table>
                <thead>
                    <tr>
                        <th>Assignment Title</th>
                        <th>Submission Date</th>
                        <th>Score</th>
                        <th>Grade (%)</th>
                        <th>Uploaded File</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
    <?php
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['title'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['submission_date'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['raw_score'] ?? 'N/A') . "</td>"; // Raw Score
            echo "<td>" . htmlspecialchars(number_format($row['grade'], 2)) . "%</td>"; // Correct Grade (Percentage)
            echo "<td>";

            // Link to view the assignment that opens a modal
            if (!empty($row['submitted_file'])) {
                $filePath = 'uploads/' . htmlspecialchars($row['submitted_file']);
                echo "<a href='#' class='view-file' data-file='" . $filePath . "'>View Assignment</a>";
            } else {
                echo "File not available";
            }

            echo "</td>";
            echo "<td>
                <form action='admin_assignments.php' method='POST'>
                    <input type='hidden' name='submission_id' value='" . htmlspecialchars($row['submission_id'] ?? '') . "'>
                    <input type='number' name='score' value='" . htmlspecialchars($row['raw_score'] ?? '0') . "' min='0' required>
                    <select name='highest_score' required>
                        <option value='10'" . ($row['total_grade'] == 10 ? ' selected' : '') . ">10</option>
                        <option value='20'" . ($row['total_grade'] == 20 ? ' selected' : '') . ">20</option>
                        <option value='30'" . ($row['total_grade'] == 30 ? ' selected' : '') . ">30</option>
                    </select>
                    <button type='submit'>Update Score</button>
                </form>
            </td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='6'>No submissions found.</td></tr>";
    }
    ?>
</tbody>


            </table>
        </main>
    </div>

    <!-- Modal for viewing uploaded files -->
    <div id="fileModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <iframe id="fileFrame" src="" frameborder="0"></iframe>
        </div>
    </div>

    <script>
        // Get the modal
        var modal = document.getElementById("fileModal");

        // Get the <span> element that closes the modal
        var span = document.getElementsByClassName("close")[0];

        // Get all view file links
        var viewFileLinks = document.querySelectorAll('.view-file');

        viewFileLinks.forEach(function(link) {
            link.addEventListener('click', function(event) {
                event.preventDefault();
                var file = this.getAttribute('data-file');
                document.getElementById('fileFrame').src = file;
                modal.style.display = "block";
            });
        });

        // When the user clicks on <span> (x), close the modal
        span.onclick = function() {
            modal.style.display = "none";
        }

        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>
