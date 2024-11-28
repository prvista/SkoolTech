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

// Retrieve notifications
$notificationSql = "SELECT * FROM notifications WHERE student_id = ? ORDER BY id DESC";
$notificationStmt = $conn->prepare($notificationSql);
$notificationStmt->bind_param("i", $student['id']);
$notificationStmt->execute();
$notificationResult = $notificationStmt->get_result();

$notifications = [];
if ($notificationResult->num_rows > 0) {
    while ($row = $notificationResult->fetch_assoc()) {
        $notifications[] = $row;
    }

    // Mark notifications as read only after retrieving them
    $markReadSql = "UPDATE notifications SET is_read = 1 WHERE student_id = ? AND is_read = 0";
    $markReadStmt = $conn->prepare($markReadSql);
    $markReadStmt->bind_param("i", $student['id']);
    $markReadStmt->execute();
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
        .video-call-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 20px;
        }

        .video-call-buttons {
            margin-bottom: 20px;
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

        /* Notification Dropdown */
        .notif-dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 250px;
            max-height: 260px;
            overflow-y: auto;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            z-index: 1;
            color: black;
        }

        .notif-dropdown-content p {
            padding: 20px;
            margin: 0;
            border-bottom: 1px solid #ddd;
            color: black;
        }

        .notif-dropdown-content p:last-child {
            border-bottom: none;
        }

        .notif-dropdown-content p:hover {
            background-color: #f1f1f1;
        }

        .notif-dropdown.open .notif-dropdown-content {
            display: block;
            right: 8rem;
        }

        .notif-dropdown.open .notif-dropdown-content a {
            color: black;
        }

        /* Notification Badge */
        .notif-badge {
            background-color: #007bff; /* Blue color */
            color: white;
            font-size: 12px;
            font-weight: bold;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: absolute;
            top: -5px;
            right: -5px;
            transform: translate(50%, -50%);
            z-index: 1;
            font-size: 14px; /* Adjust font size for count */
        }

        .notif-toggle {
            position: relative;
        }

        .video-call-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 30px;
        }

        .video-call-buttons {
            margin-bottom: 20px;
        }

        .video-call-buttons button {
            list-style: none;
            margin: 0 20px;
            padding: 10px 20px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            color: #fff;
            border: 2px solid transparent;
            transition: background-color 0.3s, border-color 0.3s;
            background-color: #0866ff;
            border-radius: 5px;
            font-family:"Poppins","sans-serif";


        }

        .video-call-buttons button:hover {
            color: #0866ff;
            border-color: #0866ff;
            background-color: rgba(8, 102, 255, 0.1);

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
            color: #28a745;
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

        /* Focus effect for input field */
        .video-call-section input[type="text"]:focus {
            border: 1px solid #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
            outline: none;
        }

        /* Style for the buttons under the input */
        .video-call-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        /* Style for the video call section for better alignment */
        .video-call-section {
            margin-top: 30px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        /* Style for the input area inside the video call section */
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
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
        }

        #jitsi-container {
            /* display: none; Initially hidden */
            width: 100%;
            height: 500px; /* Set a fixed height for the video container */
            margin-top: 20px;
        }

        #jitsi-meet .watermark {
            display: none !important;
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
                                    $targetPage = '#';
                                    if ($notification['activity_type'] === 'Assignment') {
                                        $targetPage = 'student_assignments.php';
                                    } elseif ($notification['activity_type'] === 'Quiz') {
                                        $targetPage = 'task_quiz.php';
                                    } elseif ($notification['activity_type'] === 'Exam') {
                                        $targetPage = 'task_exam.php';
                                    }
                                    ?>
                                    <p data-id="<?php echo $notification['id']; ?>">
                                        <a class="notif_btn" 
                                        href="<?php echo htmlspecialchars($targetPage); ?>" 
                                        data-id="<?php echo $notification['id']; ?>">
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
                <img src="./dist/img/skooltech-logo.png" alt="SkoolTech Logo">
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



        <div class="video-call-section">


            <div id="jitsi-container" style="width: 100%; height: 510px;">

                <div id="loadingIndicator" style="display: none;">
                    <div class="spinner"></div> 
                </div>

            </div>
            
            
            <div id="callStatus">Not in a call</div>
            <div>
                <label for="roomName">Enter Room Name:</label>
                <input type="text" id="roomName" name="roomName" placeholder="Enter room name">
            </div>
            <div class="video-call-buttons">
                <button id="startCallBtn">Start Call</button>
                <button id="joinCallBtn">Join Call</button>


                <!-- <button id="endCallBtn">End Call</button> -->
            </div>
        </div>
    </main>
</div>

<script src="./dist/js/dropdown.js"></script>
<!-- Jitsi Meet API -->
<script src="https://meet.jit.si/external_api.js"></script>

<script>
    const startCallBtn = document.getElementById('startCallBtn');
    const joinCallBtn = document.getElementById('joinCallBtn');
    const endCallBtn = document.getElementById('endCallBtn');
    const callStatus = document.getElementById('callStatus');
    const roomNameInput = document.getElementById('roomName');
    const loadingIndicator = document.getElementById("loadingIndicator"); // Loading spinner element
    let api = null;  // The Jitsi Meet API instance

    // Function to start the call
    function startCall(roomName) {
        if (!roomName) {
            alert('Please enter a room name.');
            return;
        }

        // Show the loading spinner
        loadingIndicator.style.display = "flex";  // Use flexbox to center

        const domain = "meet.jit.si"; // Jitsi Meet server
        const options = {
            roomName: roomName,
            width: '100%',
            height: 500,
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
            callStatus.innerHTML = 'You are in a call';
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
        if (api) {
            callStatus.innerHTML = 'You are already in a call';
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
            callStatus.innerHTML = 'Not in a call';
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






<script>
    // Assume the button has an id of "joinButton"
const joinButton = document.getElementById("joinCallBtn");

joinButton.addEventListener("click", function() {
    // Disable the button to prevent further clicks
    joinButton.disabled = true;
    
    // Perform your join action (e.g., AJAX request or function call)
    joinRoom().then(() => {
        // Re-enable the button once the join action is completed
        joinButton.disabled = false;
    }).catch((error) => {
        // If there's an error, also re-enable the button
        console.error("Error during join:", error);
        joinButton.disabled = false;
    });
});

async function joinRoom() {
    // Simulate your join room function (e.g., AJAX request)
    // Replace with your actual code for joining the room
    return new Promise((resolve, reject) => {
        setTimeout(() => {
            // Simulating success after 2 seconds (you can replace with your logic)
            resolve("Room joined successfully!");
        }, 2000);
    });
}

</script>

</body>
</html>
