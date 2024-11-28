<?php
session_start();

// Check if the user is logged in and is a professor or student
if (!isset($_SESSION['username'])) {
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

// Fetch user role
$user_role = $_SESSION['role'];

// Fetch professor's details (if the user is a professor)
if ($user_role == 'professor') {
    $stmt = $conn->prepare("SELECT name FROM professors WHERE username = ?");
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    $professor = $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

// Fetch student details (if the user is a student)
if ($user_role == 'student') {
    $stmt = $conn->prepare("SELECT name FROM students WHERE username = ?");
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

$roomName = uniqid('room_'); // Generate a unique room name
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Room - SkoolTech</title>
    <link rel="stylesheet" href="./dist/scss/main.min.css">
    <link rel="icon" href="./dist/img/skooltech-icon.png">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <style>
        .button-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        .join-button {
            padding: 15px 30px;
            background-color: #007bff;
            color: white;
            border: none;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
        }
        .join-button:hover {
            background-color: #0056b3;
        }
        #video-room {
            display: none;
        }
    </style>
</head>
<body>
    <div class="grid-container">
        <div class="header">
            <div class="container">
                <div class="header__wrapper">
                    <h2>Welcome to the Virtual Room</h2>
                </div>
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
                        <li><a href="./admin_reportcard.php"><span class="material-icons-outlined">credit_card</span>Report Card</a></li>
                        <li><a href="logout.php"><span class="material-icons-outlined">logout</span>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <main class="main-container">
            <div class="button-container">
                <!-- Button to join the video call -->
                <button class="join-button" onclick="startVideoCall()">Start Video Call</button>
            </div>

            <div id="video-room">
                <h3>Joining Room: <?php echo $roomName; ?></h3>
                <!-- Embed Jitsi iframe with Moderator Settings -->
                <iframe 
                    src="https://meet.jit.si/<?php echo $roomName; ?>#config.prejoinPageEnabled=false&config.defaultLanguage='en'&config.startWithVideoMuted=false&config.startWithAudioMuted=false&config.lockRoom=true&config.inviteEnabled=false&config.allowViewParticipants=false&config.disableDeepLinking=true&config.videoQuality=720&config.videoConstraints={&quot;height&quot;:360}&config.brandWatermarkLink=false&config.showBranding=false" 
                    width="100%" 
                    height="600px" 
                    frameborder="0">
                </iframe>

            </div>
        </main>
    </div>

    <script>
        function startVideoCall() {
            document.getElementById('video-room').style.display = 'block'; // Display the iframe
        }
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
</body>
</html>

<?php
$conn->close();
?>
