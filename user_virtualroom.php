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
$scoreSql = "SELECT subject, 
                    SUM(assignment_score) AS assignment_total, 
                    SUM(quiz_score) AS quiz_total, 
                    SUM(exam_score) AS exam_total
             FROM subject_scores 
             WHERE student_id='$studentId'
             GROUP BY subject";
$scoreResult = $conn->query($scoreSql);

$scores = [];
if ($scoreResult->num_rows > 0) {
    while ($row = $scoreResult->fetch_assoc()) {
        $scores[] = $row;
    }
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
    <title>Student Dashboard - SkoolTech</title>
    <link rel="icon" href="./dist/img/skooltech-icon.png">
    <link rel="stylesheet" href="./dist/scss/main.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">

    <style>
        /* Add styling for the video call section */
        /* .hidden {
            display: none;
        } */

        .video-call-section {
            /* margin: 20px 0; */
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
            /* display: none; Initially hidden */
        }

        #jitsi-container {
            border: 1px solid #ccc;
            border-radius: 8px;
            margin-bottom: 15px;
            background-color: #e9ecef;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .video-call-buttons{
            margin-top: 1rem;
        }

        .video-call-buttons button {
            list-style: none;
            margin: 0 10px;
            padding: 10px 20px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            color: #fff;
            border: 2px solid transparent;
            transition: background-color 0.3s, border-color 0.3s;
            background-color: #f79320;
            border-radius: 5px;
            font-family:"Poppins","sans-serif";
        }

        .video-call-buttons button:hover {
            color: #f79320;
            border-color: #ef8e20;
            background-color: rgba(8, 102, 255, 0.1);
        }

        #toggleCallSection {
            /* margin: 10px 0; */
            margin-bottom: 1rem;
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
            font-family:"Poppins","sans-serif";
            font-weight:500;
        }

        #toggleCallSection:hover {
            background-color: #218838;
        }


        #local-video, #remote-video {
            width: 300px;
            height: 200px;
            margin: 10px;
            border: 1px solid #ddd;
            background: #000;
        }

        #callStatus {
            margin-top: 10px;
            font-size: 16px;
            font-weight: bold;
        }


        /* Style for the 'Enter Room Name' label and input */
        .video-call-section label {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }

        .video-call-section input[type="text"] {
            padding: 10px;
            font-size: 16px;
            width: 100%;
            max-width: 400px; /* You can adjust this to fit your design */
            border-radius: 5px;
            border: 1px solid #ccc;
            margin-bottom: 20px; /* Add some space below the input */
            box-sizing: border-box;
            transition: border 0.3s ease, box-shadow 0.3s ease;
        }

        #loadingIndicator {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);  /* Moves the spinner back by 50% of its width/height */
            display: none;  /* Hidden by default */
            z-index: 9999;  /* Ensure it appears on top */
            background-color: rgba(0, 0, 0, 0.5);  /* Semi-transparent background */
            width: 100%;  /* Full width */
            height: 100%;  /* Full height */
            display: flex;  /* Flexbox to center the spinner */
            justify-content: center;  /* Align horizontally */
            align-items: center;  /* Align vertically */
        }

        #loadingIndicator .spinner {
            border: 8px solid #f3f3f3;  /* Light grey background */
            border-top: 8px solid #3498db;  /* Blue top border */
            border-radius: 50%;
            width: 100px;  /* Larger spinner */
            height: 100px;  /* Larger spinner */
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .video-call-section input[type="text"] {
            padding: 12px;
            font-size: 16px;
            width: 80%;
            max-width: 400px; /* Adjust the width of the input box */
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }

        .video-call-section input[type="text"]:focus {
            outline: none;
            border-color: #f79320;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
        }

        #jitsi-meet-watermark {
            display: none;
        }


    </style>
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
                        <a href="./user_subjects.php"><span class="material-icons-outlined">library_books</span> Subjects</a>
                    </li>
                    <li><a href="#"><span class="material-icons-outlined">video_call</span>Virtual Room</a></li>
                    <li><a href="logout.php"><span class="material-icons-outlined">logout</span>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>

    <main class="main-container">
    <!-- <button id="toggleCallSection">Join Virtual Room</button> -->

        <div class="video-call-section" id="videoCallSection">
            <div id="jitsi-container" style="width: 100%; height: 595px;">
                <div id="loadingIndicator" style="display: none;">
                    <div class="spinner"></div>
                </div>
            </div>

            <div id="callStatus">Not in a call</div>
            <div class="virtual_input">
    <label for="roomName">Enter Room Name:</label>
    <input type="text" id="roomName" name="roomName" placeholder="Enter room name">
</div>
<div class="video-call-buttons">
    <button id="startCallBtn">Start Call</button>
    <button id="joinCallBtn">Join Call</button>
</div>

        </div>
    </main>
</div>

<script src="./dist/js/dropdown.js"></script>
<script src="./dist/js/notif-dropdown.js"></script>
<script src="./dist/js/notif-click.js"></script>
<!-- Jitsi Meet API -->
<script src="https://meet.jit.si/external_api.js"></script>

    <!-- <script>
        // Toggle the visibility of the video call section when the button is clicked
        document.getElementById('toggleCallSection').addEventListener('click', function() {
            const videoCallSection = document.getElementById('videoCallSection');
            // Show the video call section if it's hidden, hide it if it's visible
            if (videoCallSection.style.display === "none" || videoCallSection.style.display === "") {
                videoCallSection.style.display = "block";  // Show section
            } else {
                videoCallSection.style.display = "none";  // Hide section
            }
        });
    </script> -->

    <script>
    const startCallBtn = document.getElementById('startCallBtn');
    const joinCallBtn = document.getElementById('joinCallBtn');
    const endCallBtn = document.getElementById('endCallBtn');
    const callStatusElement = document.getElementById("callStatus");
    const roomNameInput = document.getElementById('roomName');
    const loadingIndicator = document.getElementById("loadingIndicator"); // Loading spinner element
    let api = null;  // The Jitsi Meet API instance
    let isInCall = false;  // Track the call status

    // Function to update call status
    function updateCallStatus(status) {
        switch(status) {
            case 'starting':
                callStatusElement.innerHTML = "Starting the call...";  // Set the status when call is starting
                callStatusElement.style.color = "#ffa500"; // Optional: Set color for "starting" status
                break;
            case 'inCall':
                callStatusElement.innerHTML = "You are in a call";  // Set the status when the user is in a call
                callStatusElement.style.color = "#28a745"; // Optional: Green for "in call"
                break;
            case 'notInCall':
                callStatusElement.innerHTML = "You are not in a call";  // Set the status when the user is not in a call
                callStatusElement.style.color = "#dc3545"; // Optional: Red for "not in a call"
                break;
            default:
                callStatusElement.innerHTML = "Status unknown";
                callStatusElement.style.color = "#6c757d"; // Default color
                break;
        }
    }

    // Function to start the call
    function startCall(roomName) {
        if (!roomName) {
            alert('Please enter a room name.');
            return;
        }

        // Show the loading spinner
        loadingIndicator.style.display = "flex";  // Use flexbox to center
        updateCallStatus('starting');  // Show "starting" status

        const domain = "meet.jit.si"; // Jitsi Meet server
        const options = {
            roomName: roomName,
            width: '100%',
            height: 593,
            parentNode: document.getElementById('jitsi-container')
        };

        // Check if there's already an active call
        if (api) {
            api.dispose();  // Dispose of the existing call instance before starting a new one
        }

        // Initialize the Jitsi Meet API
        api = new JitsiMeetExternalAPI(domain, options);

        // Update status once the call is joined
        api.addEventListener('videoConferenceJoined', function() {
            if (!isInCall) {
                isInCall = true;
                updateCallStatus('inCall');  // Update to "in call"
            }
        });

        // Keep the spinner visible for 6 seconds
        setTimeout(function() {
            loadingIndicator.style.display = "none"; // Hide spinner after 6 seconds
        }, 3500);
    }

    // Event listener for the start call button
    startCallBtn.onclick = function() {
        const roomName = roomNameInput.value.trim();
        startCall(roomName);
    };

    // Event listener for the join call button
    joinCallBtn.onclick = function() {
        const roomName = roomNameInput.value.trim();

        // Prevent starting a new call if already in one
        if (isInCall) {
            updateCallStatus('inCall');
            return;
        }

        // Show loading spinner while processing the join action
        loadingIndicator.style.display = "flex";  // Use flexbox to center

        // Execute the startCall function after disabling the button
        setTimeout(function() {
            startCall(roomName);
        }, 500);  // Small delay to simulate joining process

        // Keep the spinner visible for 6 seconds after the "Join Call" button is clicked
        setTimeout(function() {
            loadingIndicator.style.display = "none"; // Hide spinner after 6 seconds
        }, 6000);
    };

    // Event listener for the end call button
    endCallBtn.onclick = function() {
        if (api) {
            api.executeCommand('hangup');
            isInCall = false;
            updateCallStatus('notInCall');  // Update to "not in call"
            loadingIndicator.style.display = "none";  // Hide spinner when ending the call
        }
    };

    // Loading spinner logic: hide when no call is active
    window.addEventListener('beforeunload', function () {
        if (api) {
            api.dispose();  // Ensure the Jitsi API is disposed of when the page is unloaded
        }
    });
</script>


</body>
</html>
