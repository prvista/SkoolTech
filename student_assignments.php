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

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the current student's username
$student_username = $_SESSION['username'];

// Get the student_id based on the logged-in username
$sql_student_id = "SELECT id FROM students WHERE username = ?";
$stmt_student = $conn->prepare($sql_student_id);
$stmt_student->bind_param("s", $student_username);
$stmt_student->execute();
$result_student = $stmt_student->get_result();

// Retrieve student details
$sql_student = "SELECT * FROM students WHERE username=?";
$stmt_student = $conn->prepare($sql_student);
$stmt_student->bind_param("s", $student_username);
$stmt_student->execute();
$result_student = $stmt_student->get_result();

if ($result_student->num_rows == 1) {
    $student = $result_student->fetch_assoc();
    $student_id = $student['id']; // Get student ID
} else {
    echo "Student details not found.";
    exit();
}


// Get all available assignments that have not been submitted by the student
$sql_assignments = "SELECT a.*, asub.id AS submission_id 
                    FROM assignments a 
                    LEFT JOIN assignment_submissions asub ON a.id = asub.assignment_id AND asub.student_id = ? 
                    WHERE asub.id IS NULL";

$stmt = $conn->prepare($sql_assignments);
$stmt->bind_param("i", $student_id);  // Use student_id instead of username
$stmt->execute();
$result_assignments = $stmt->get_result();

// Handle assignment submission (PDF upload)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['assignment_file'])) {
    $assignment_id = $_POST['assignment_id'];
    $file_name = $_FILES['assignment_file']['name'];
    $file_tmp = $_FILES['assignment_file']['tmp_name'];
    $upload_dir = __DIR__ . '/uploads/';

    // Check if the upload directory exists, if not, create it
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Validate file type (PDF only) and size (limit to 5MB)
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $file_size = $_FILES['assignment_file']['size']; // Get file size
    if ($file_ext != 'pdf' || $file_size > 5 * 1024 * 1024) {
        echo "<script>alert('Only PDF files under 5MB are allowed.');</script>";
    } else {
        // Generate a unique file name
        $new_file_name = uniqid('assignment_', true) . '.' . $file_ext;

        // Check if the upload directory is writable
        if (!is_writable($upload_dir)) {
            echo "<script>alert('Upload directory is not writable. Check permissions.');</script>";
        } else {
            // Save the uploaded file
            if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                // Insert submission record into the database
                $stmt = $conn->prepare("INSERT INTO assignment_submissions (assignment_id, student_id, submitted_file) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $assignment_id, $student_id, $new_file_name);

                if ($stmt->execute()) {
                    // Success feedback
                    echo "<script>alert('Assignment submitted successfully.');</script>";
                    // Redirect to user_dashboard.php after successful submission
                    header("Location: user_dashboard.php");
                    exit();
                } else {
                    echo "<script>alert('Database Error: " . $stmt->error . "');</script>";
                }
                $stmt->close();
            } else {
                echo "<script>alert('Failed to upload file.');</script>";
            }
        }
    }
}
// Extract initials
$nameParts = explode(' ', $student['name']);
$initials = strtoupper($nameParts[0][0]); // First letter of the first name
if (isset($nameParts[1])) {
    $initials .= strtoupper($nameParts[1][0]); // First letter of the second name
}

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
    <title>Assignments</title>
    <link rel="stylesheet" href="./dist/scss/main.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <link rel="icon" href="./dist/img/skooltech-icon.png">


    <style>
        

        input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            z-index: 2;
            cursor: pointer;
        }

        tr:hover td {
            background-color: #f2f2f2;
            color: black; 
        }
        tr:hover {
            background-color: #f2f2f2;
            color: black; 
        }

        td{
            width: 115rem;
        }
        

    </style>
</head>
<body>
    <div class="grid-container">
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
                                        $targetPage = '#';
                                        if ($notification['activity_type'] === 'Assignment') {
                                            $targetPage = 'student_assignments.php';
                                        } elseif ($notification['activity_type'] === 'Quiz') {
                                            $targetPage = 'task_quiz.php';
                                        } elseif ($notification['activity_type'] === 'Exam') {
                                            $targetPage = 'task_exam.php';
                                        }
                                        ?>
                                        <p data-id="<?php echo $notification['id']; ?>" class="<?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
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
            

        <div id="sidenav">
            <div class="sidenav__wrapper">
                <div class="sidenav__img">
                    <img src="./dist/img/academix white logo.png" alt="">
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
                                <li><a href="task_quiz.php">Quiz</a></li>
                                <li><a href="task_exam.php">Exam</a></li>
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
            <div class="dashboard__banner">
                <div class="dashboard__banner__wrapper">
                    <div class="dashboard__banner__text">
                        <h2>Assignments</h2>
                        <p>Welcome to SkoolTech's Assignment Portal! Easily access, complete, and submit your assignments, track your progress, and receive instant feedback—all in one place to enhance your learning experience.</p>
                    </div>
                </div>
            </div>
            <table>
                <tr>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Deadline</th>
                    <!-- <th>Criteria</th> -->
                    <th>Submit</th>
                </tr>
                <?php
// Fetch assignments and handle the due_date formatting
if ($result_assignments->num_rows > 0) {
    while ($row = $result_assignments->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td>" . htmlspecialchars($row['description']) . "</td>";
        
        // Format the due_date to a readable format
        $due_date = $row['due_date'];  // Assuming 'due_date' is a valid DATETIME or TIMESTAMP field
        echo "<td>" . htmlspecialchars(date('F j, Y, g:i a', strtotime($due_date))) . "</td>"; // Format the due_date
        
        // echo "<td>" . htmlspecialchars($row['criteria']) . "</td>";
        echo "<td>
            <form action='student_assignments.php' method='POST' enctype='multipart/form-data'>
                <input type='hidden' name='assignment_id' value='" . $row['id'] . "'>
                <div class='file-upload-wrapper'>
                    <input type='file' name='assignment_file' id='assignment_file' required>
                    <label for='assignment_file' class='upload_btn'>CHOOSE FILE</label>
                </div>
                <button class='upload_btn' type='submit'>UPLOAD</button>
            </form>
        </td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5'>No assignments available</td></tr>";
}
?>

            </table>
        </main>
    </div>

    <script src="./dist/js/dropdown.js"></script>
    <script src="./dist/js/notif-dropdown.js"></script>
    <script src="./dist/js/notif-click.js"></script>
</body>
</html>
