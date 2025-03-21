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

// Retrieve student details
$loggedInUsername = $_SESSION['username'];
$sql = "SELECT * FROM students WHERE username='$loggedInUsername'";
$result = $conn->query($sql);

if ($result->num_rows == 1) {
    $student = $result->fetch_assoc();
} else {
    echo "Student details not found.";
    exit();
}

// Extract initials from the user's name
$nameParts = explode(' ', $student['name']);
$initials = strtoupper($nameParts[0][0]); // First character of the first name

if (isset($nameParts[1])) {
    $initials .= strtoupper($nameParts[1][0]); // First character of the second name
}

// Query subject_scores table for the logged-in student
$studentId = $student['id'];  // Assuming 'id' is the primary key in the students table

// Separate queries for each score category (assignment, quiz, and exam)
$assignmentSql = "SELECT subject, 
                    SUM(assignment_score) AS assignment_total
             FROM subject_scores 
             WHERE student_id='$studentId' AND assignment_score IS NOT NULL
             GROUP BY subject";
$quizSql = "SELECT subject, 
                    SUM(quiz_score) AS quiz_total
             FROM subject_scores 
             WHERE student_id='$studentId' AND quiz_score IS NOT NULL
             GROUP BY subject";
$examSql = "SELECT subject, 
                    SUM(exam_score) AS exam_total
             FROM subject_scores 
             WHERE student_id='$studentId' AND exam_score IS NOT NULL
             GROUP BY subject";

// Execute the queries
$assignmentResult = $conn->query($assignmentSql);
$quizResult = $conn->query($quizSql);
$examResult = $conn->query($examSql);

$scores = [
    'assignment' => [],
    'quiz' => [],
    'exam' => []
];

// Fetch results for assignment, quiz, and exam
function generateFeedback($assignment_score, $quiz_score, $exam_score) {
    $totalScore = $assignment_score + $quiz_score + $exam_score;
    $feedback = "";

    // Example feedback based on score ranges
    if ($totalScore >= 90) {
        $feedback = "Excellent performance! Keep it up!";
    } elseif ($totalScore >= 75) {
        $feedback = "Good job, but there is room for improvement.";
    } elseif ($totalScore >= 50) {
        $feedback = "You need to work harder. Focus on the areas where you're struggling.";
    } else {
        $feedback = "Poor performance. Consider reviewing the materials and seek help if needed.";
    }

    return $feedback;
}

if ($assignmentResult->num_rows > 0) {
    while ($row = $assignmentResult->fetch_assoc()) {
        $feedback = generateFeedback($row['assignment_total'], 0, 0);
        $row['feedback_message'] = $feedback;
        $scores['assignment'][] = $row;
    }
}

if ($quizResult->num_rows > 0) {
    while ($row = $quizResult->fetch_assoc()) {
        $feedback = generateFeedback(0, $row['quiz_total'], 0);
        $row['feedback_message'] = $feedback;
        $scores['quiz'][] = $row;
    }
}

if ($examResult->num_rows > 0) {
    while ($row = $examResult->fetch_assoc()) {
        $feedback = generateFeedback(0, 0, $row['exam_total']);
        $row['feedback_message'] = $feedback;
        $scores['exam'][] = $row;
    }
}

// Generate Personalized Feedback for Student
function generatePersonalizedFeedback($studentData, $scores) {
    if (!isset($studentData['name'], $studentData['id'])) {
        return "Missing data for personalized feedback.";
    }

    $name = $studentData['name'];
    $studentId = $studentData['id'];

    // Default values for scores
    $assignmentScore = isset($scores['assignment'][0]['assignment_total']) ? $scores['assignment'][0]['assignment_total'] : 0;
    $quizScore = isset($scores['quiz'][0]['quiz_total']) ? $scores['quiz'][0]['quiz_total'] : 0;
    $examScore = isset($scores['exam'][0]['exam_total']) ? $scores['exam'][0]['exam_total'] : 0;

    // Calculate total score and average
    $totalScore = $assignmentScore + $quizScore + $examScore;
    $averageScore = ($assignmentScore + $quizScore + $examScore) / 3;

    // Personalized message with actual data
    $feedback = "Hello, $name!\n";
    $feedback .= "Here are your scores for the semester so far:\n";
    $feedback .= "- Assignment score: " . round($assignmentScore, 2) . "\n";
    $feedback .= "- Quiz score: " . round($quizScore, 2) . "\n";
    $feedback .= "- Exam score: " . round($examScore, 2) . "\n";
    $feedback .= "- Total score: " . round($totalScore, 2) . "\n";
    $feedback .= "- Average score: " . round($averageScore, 2) . "\n";

    // Feedback based on the average score
    if ($averageScore >= 85) {
        $feedback .= "You’re doing excellent overall with an average of " . round($averageScore, 2) . "%. Keep up the great work!";
    } elseif ($averageScore >= 70) {
        $feedback .= "You’re doing well, but there’s room for improvement. Your current average is " . round($averageScore, 2) . "%. Keep pushing!";
    } else {
        $feedback .= "It looks like you’re facing some challenges, with a current average of " . round($averageScore, 2) . "%. Let's work on improving your scores!";
    }

    // Suggestions for improvement based on the scores
    if ($assignmentScore < 70) {
        $feedback .= "\nSuggestions for improving your assignments:\n";
        $feedback .= "- Focus on improving your assignment work, especially in the areas where you scored lower.\n";
    }

    if ($quizScore < 70) {
        $feedback .= "Suggestions for improving your quiz scores:\n";
        $feedback .= "- Spend more time reviewing the quiz topics, especially those you struggled with.\n";
    }

    if ($examScore < 70) {
        $feedback .= "Suggestions for improving your exam scores:\n";
        $feedback .= "- Consider reviewing previous exam papers and focusing on topics that you found challenging.\n";
    }

    $feedback .= "You’ve got this! Keep up the hard work and don’t hesitate to ask for help if you need it!";

    return $feedback;
}

$studentData = [
    'name' => $student['name'],
    'id' => $student['id'],
];

$personalizedFeedback = generatePersonalizedFeedback($studentData, $scores);

// Display the feedback for the student in the dashboard
// echo nl2br($personalizedFeedback); // Outputs feedback with newlines for better readability


// Retrieve only unread notifications
$notificationSql = "SELECT * FROM notifications WHERE student_id = ? AND is_read = 0 ORDER BY id DESC";
$notificationStmt = $conn->prepare($notificationSql);
$notificationStmt->bind_param("i", $student['id']);
$notificationStmt->execute();
$notificationResult = $notificationStmt->get_result();

$notifications = [];
if ($notificationResult->num_rows > 0) {
    while ($row = $notificationResult->fetch_assoc()) {
        $notifications[] = $row;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - SkoolTech</title>
    <link rel="icon" href="./dist/img/skooltech-icon.png">
    <link rel="stylesheet" href="./dist/scss/main.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
</head>
<body>

<!-- grid -->
<div class="grid-container">
    <!-- header -->
    <div class="header">
        <div class="container">
            <div class="header__wrapper">
                <div class="header__right">
                        <!-- Notifications Dropdown -->
                        <div class="notif-dropdown">
                            <a href="#" class="notif-toggle">
                                <span class="material-icons-outlined">notifications</span>
                                <!-- Notification count badge -->
                                <?php if (count($notifications) > 0): ?>
                                    <span class="notif-badge"><?php echo count($notifications); ?></span>
                                <?php endif; ?>
                            </a>
                            <div class="notif-dropdown-content">
                                <?php if (count($notifications) > 0): ?>
                                    <?php foreach ($notifications as $notification): ?>
                                        <?php
                                        // Determine the target page based on notification activity type
                                        $targetPage = '#';
                                        if ($notification['activity_type'] === 'Assignment') {
                                            $targetPage = 'student_assignments.php';
                                        } elseif ($notification['activity_type'] === 'Quiz') {
                                            $targetPage = 'task_quiz.php';
                                        } elseif ($notification['activity_type'] === 'Exam') {
                                            $targetPage = 'task_exam.php';
                                        }
                                        ?>
                                        <p 
                                            id="notif-<?php echo $notification['id']; ?>" 
                                            class="<?php echo $notification['is_read'] ? 'read' : 'unread'; ?>"
                                            data-id="<?php echo $notification['id']; ?>">
                                            <a class="notif_btn" 
                                                href="<?php echo htmlspecialchars($targetPage); ?>" 
                                                data-id="<?php echo $notification['id']; ?>" 
                                                onclick="markNotificationAsRead(event, <?php echo $notification['id']; ?>, '<?php echo $targetPage; ?>')">
                                                <strong><?php echo htmlspecialchars($notification['activity_type']); ?>:</strong>
                                                <?php echo htmlspecialchars($notification['activity_title']); ?>
                                            </a>
                                        </p>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p>No new notifications</p>
                                <?php endif; ?>
                            </div>

                        </div>
                        <div class="initials-bg">
                            <p><?php echo $initials; ?></p>
                        </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- sidenav -->
    <div id="sidenav">
        <div class="sidenav__wrapper">
            <div class="sidenav__img">
                <img src="./dist/img/academix white logo.png" alt="SkoolTech Logo">
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
                            <li><a href="task_exam.php">Exam</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="#"><span class="material-icons-outlined">library_books</span> Subjects</a>
                    </li>
                    <li><a href="user_virtualroom.php"><span class="material-icons-outlined">video_call</span>Virtual Room</a></li>
                    <li><a href="logout.php"><span class="material-icons-outlined">logout</span>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- main container -->
    <main class="main-container">
        <div class="dashboard__banner">
            <div class="dashboard__banner__wrapper">
                <div class="dashboard__banner__text">
                    <!-- <h2>Welcome, <?php echo htmlspecialchars($student['username']); ?>!</h2> -->
                    <h2>Subject Results</h2>
                    <p>Welcome to SkoolTech, your all-in-one platform for tracking academic performance. View and manage your subject results, monitor progress across assignments, quizzes, and exams, and stay connected with your instructors to achieve your educational goals.</p>
                </div>
            </div>
        </div>

    <div class="tabs">
    <ul class="tab-titles">
        <li class="active" data-tab="assignment">Assignments</li>
        <li data-tab="quiz">Quizzes</li>
        <li data-tab="exam">Exams</li>
    </ul>
</div>

<div class="tab-content">
    <!-- Assignments Tab -->
    <div class="tab assignment active">
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Assignment Score</th>
                    <th>Feedback</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($scores['assignment'] as $score): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($score['subject']); ?></td>
                        <td><?php echo htmlspecialchars($score['assignment_total']); ?></td>
                        <td><?php echo htmlspecialchars($score['feedback_message']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Quizzes Tab -->
    <div class="tab quiz">
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Quiz Score</th>
                    <th>Feedback</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($scores['quiz'] as $score): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($score['subject']); ?></td>
                        <td><?php echo htmlspecialchars($score['quiz_total']); ?></td>
                        <td><?php echo htmlspecialchars($score['feedback_message']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Exams Tab -->
    <div class="tab exam">
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Exam Score</th>
                    <th>Feedback</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($scores['exam'] as $score): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($score['subject']); ?></td>
                        <td><?php echo htmlspecialchars($score['exam_total']); ?></td>
                        <td><?php echo htmlspecialchars($score['feedback_message']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>


<div class="feedback-wrapper">
  <button class="message-icon" onclick="toggleFeedback()">
  <span class="material-icons-outlined">
chat
</span>
  </button>
  <div class="feedback-section" id="feedbackSection">
    <p><?php echo nl2br($personalizedFeedback); ?></p>
  </div>
</div>



    </main>
</div>

<script src="./dist/js/dropdown.js"></script>
<script src="./dist/js/notif-dropdown.js"></script>
<script src="./dist/js/notif-click.js"></script>


<script>
    document.addEventListener("DOMContentLoaded", function() {
        const tabs = document.querySelectorAll('.tab-titles li');
        const tabContents = document.querySelectorAll('.tab');

        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove 'active' class from all tabs and content
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));

                // Add 'active' class to the clicked tab and its corresponding content
                const targetTab = tab.getAttribute('data-tab');
                document.querySelector('.tab.' + targetTab).classList.add('active');
                tab.classList.add('active');
            });
        });
    });
</script>


    <script>
        var domain = 'meet.jit.si';
        var options = {
            roomName: '<?php echo $roomName; ?>',
            width: '100%',
            height: '100%',
            configOverwrite: {
                prejoinPageEnabled: false,     // Disable pre-join screen
                startWithAudioMuted: false,    // Start with audio unmuted
                startWithVideoMuted: false     // Start with video unmuted
            },
            interfaceConfigOverwrite: {
                filmStripOnly: false,          // Show full interface, not just filmstrip
                SHOW_JITSI_WATERMARK: false,   // Disable Jitsi watermark
                SHOW_WATERMARK_FOR_GUESTS: false, // Remove watermark for guests
                SHOW_BRAND_HEADER: false,      // Remove branding header
                BRAND_WATERMARK_LINK: ''       // Disable watermark link
            }
        };

        var api = new JitsiMeetExternalAPI(domain, options);

    </script>

    <script>
       function toggleFeedback() {
        var feedbackSection = document.getElementById('feedbackSection');
        feedbackSection.classList.toggle('visible'); // Toggle the visibility class
    }
    </script>



</body>
</html>

<?php
$conn->close();
?>
