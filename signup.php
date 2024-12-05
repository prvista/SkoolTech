<?php
session_start();

$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "skooltech";

$conn = new mysqli($servername, $username, $password, $dbname);

// Checking connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$notificationMessage = "";  // Variable to hold the notification message

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $inputUsername = $_POST['username'];
    $inputIdentifier = $_POST['identifier']; 
    $inputPassword = md5($_POST['password']); 
    $inputName = $_POST['name']; 

    if (isset($_POST['role']) && $_POST['role'] == 'student') {
        $checkSql = "SELECT * FROM students WHERE student_number='$inputIdentifier'";
        $checkResult = $conn->query($checkSql);
        
        if ($checkResult->num_rows > 0) {
            $notificationMessage = "Student number already exists. Please choose a different number.";
        } else {
            // Insert into students table
            $sql = "INSERT INTO students (username, student_number, password, name) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $inputUsername, $inputIdentifier, $inputPassword, $inputName);

            if ($stmt->execute()) {
                $notificationMessage = "New student record created successfully";
            } else {
                $notificationMessage = "Error: " . $stmt->error;
            }

            $stmt->close();
        }
    } else {
        // Check if the identifier already exists for professors
        $checkSql = "SELECT * FROM professors WHERE professor_id='$inputIdentifier'";
        $checkResult = $conn->query($checkSql);
        
        if ($checkResult->num_rows > 0) {
            $notificationMessage = "Professor ID already exists. Please choose a different ID.";
        } else {
            // Insert into professors table
            $sql = "INSERT INTO professors (username, password, name, professor_id) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $inputUsername, $inputPassword, $inputName, $inputIdentifier);

            if ($stmt->execute()) {
                $notificationMessage = "New professor record created successfully";
            } else {
                $notificationMessage = "Error: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup - SkoolTech</title>
    <link rel="icon" href="./dist/img/skooltech-icon.png">
    <link rel="stylesheet" href="./dist/scss/main.min.css">
    <style>
        /* Add your CSS here for notifications */
        .notification {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: none;
        }
        @keyframes fadeIn {
            from { opacity: 0; top: 0; }
            to { opacity: 1; top: 20px; }
        }
        .notification.show {
            display: block;
            animation: fadeIn 0.5s ease;
        }
    </style>
</head>
<body>

    <!-- Notification Message -->
    <?php if ($notificationMessage): ?>
        <div class="notification show"><?php echo $notificationMessage; ?></div>
    <?php endif; ?>

    <section class="signup">
        <div class="signup__wrapper">
            <div class="signup__left">
                <div class="signup__left__wrapper">
                    <div class="signup__left--header">
                        <div class="signup__left__img">
                            <img src="./dist/img/skooltech-logo-black.png" alt="">
                        </div>
                        <div class="signup__left__title">
                            <h2>Sign In</h2>
                        </div>
                    </div>
                    <div class="signup__left__form">
                        <div class="signup__left__details">
                            <form method="post" action="">
                                <label for="username">Username:</label><br>
                                <input type="text" id="username" name="username" required><br><br>
                                <label for="identifier">Student Number / Professor ID:</label><br>
                                <input type="text" id="identifier" name="identifier" required><br><br>
                                <label for="password">Password:</label><br>
                                <input type="password" id="password" name="password" required><br><br>
                                <label for="name">Name:</label><br>
                                <input type="text" id="name" name="name" required><br><br>
                                <label for="role">Role:</label><br>
                                <select id="role" name="role" required>
                                    <option value="student">Student</option>
                                    <option value="professor">Professor</option>
                                </select><br><br>
                                <input type="submit" value="Signup" class="btn__signup">
                            </form>
                            <div class="signup__left__login">
                                <h3>Already have an account? <a href="./login.php">Log In</a></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="signup__desc">
                    <div class="signup__desc__wrapper">
                        <div class="signup__desc__info">
                            <div class="signup__desc__title">
                                <h2>Create your SkoolTech account and start learning </h2>
                            </div>
                            <div class="signup__desc__text">
                                <p>Sign up now and embark on your educational journey with SkoolTech, where innovation and learning come together.</p>
                            </div>
                        </div>
                        <div class="signup__desc__img">
                            <img src="./dist/img/bg-icon.png" alt="">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <script>
            // Optionally, hide the notification after a few seconds
            setTimeout(function() {
                const notification = document.querySelector('.notification');
                if (notification) {
                    notification.classList.remove('show');
                }
            }, 5000); // Hide after 5 seconds
        </script>
</body>
</html>
