<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$servername = "localhost";
$username = "root"; // Replace with your MySQL username
$password = ""; // Replace with your MySQL password
$dbname = "skooltech";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$loginError = ""; // Initialize variable for login error message

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $inputUsername = $conn->real_escape_string($_POST['username']);
    $inputPassword = md5($conn->real_escape_string($_POST['password'])); // Ensure this matches the hashing method used

    // Check in professors table (admin)
    $sqlProf = "SELECT * FROM professors WHERE username='$inputUsername' AND password='$inputPassword'";
    $resultProf = $conn->query($sqlProf);

    if ($resultProf->num_rows == 1) {
        $row = $resultProf->fetch_assoc();
        $_SESSION['username'] = $row['username'];
        $_SESSION['role'] = 'professor'; // Set role as professor

        // Redirect to admin_dashboard.php
        header("Location: admin_dashboard.php");
        exit();
    }

    // Check in students table
    $sqlStud = "SELECT * FROM students WHERE username='$inputUsername' AND password='$inputPassword'";
    $resultStud = $conn->query($sqlStud);

    if ($resultStud->num_rows == 1) {
        $row = $resultStud->fetch_assoc();
        $_SESSION['username'] = $row['username'];
        $_SESSION['role'] = 'student'; // Set role as student
        $_SESSION['id'] = $row['id'];

       // Debugging output
       echo "Logged in as student. Redirecting to user_dashboard.php...";
       header("Location: user_dashboard.php");
       exit();
    }

    // If no match found, set error message
    $loginError = "Invalid username or password";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SkoolTech</title>
    <link rel="stylesheet" href="./dist/scss/main.min.css">
    <link rel="icon" href="./dist/img/skooltech-icon.png">

    <style>
        /* Add custom styles for the notification */
        .notification {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: red;
            border: 2px solid red;
            color: white;
            padding: 10px;
            border-radius: 5px;
            display: none;
            z-index: 1000;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Notification for invalid login -->
    <?php if ($loginError): ?>
        <div class="notification" id="loginError"><?php echo $loginError; ?></div>
    <?php endif; ?>

    <section class="login">
      <div class="login__wrapper">
          <div class="login__left">
            <div class="login__left__wrapper">
              <div class="login__left--header">
                <div class="login__left__img">
                  <img src="./dist/img/skooltech-logo.png" alt="">
                </div>
                <div class="login__left__title">
                  <h2>LOG IN</h2>
                </div>
              </div>
                <div class="login__left__form">
                 <form method="post" action="">
                    <label for="username">Username:</label><br>
                    <input type="text" id="username" name="username" required><br>
                    <label for="password">Password:</label><br>
                    <input type="password" id="password" name="password" required><br><br>
                    <input type="submit" value="Login" class="btn__login">
                </form>
                  <div class="login__left__signup">
                      <h3>Don't have an account?</h3>
                      <a href="./signup.php">Sign Up</a>
                  </div>
                </div>
            </div>
          </div>
          <div class="login__desc">
            <div class="login__desc__wrapper">
              <div class="login__desc__info"> 
                <div class="login__desc__title">
                  <h2>Unlock your potential with SkoolTech.                  </h2>
                </div>
                <div class="login__desc__text">
                  <p>Join a community of learners and innovators. SkoolTech provides the tools, resources, and support you need to excel in your studies and beyond.</p>
                </div>
              </div>
              <div class="login__desc__img">
                <img src="./dist/img/login-pic.png" alt="">
              </div>
            </div>
          </div>
      </div>
  </section>

  <script>
      // JavaScript to show the notification for 5 seconds
      <?php if ($loginError): ?>
          setTimeout(function() {
              document.getElementById('loginError').style.display = 'block';
              setTimeout(function() {
                  document.getElementById('loginError').style.display = 'none';
              }, 5000); // Hide after 5 seconds
          }, 100); // Delay to ensure it loads correctly
      <?php endif; ?>
  </script>
</body>
</html>
