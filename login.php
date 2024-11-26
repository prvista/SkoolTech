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

        // Debugging output
        echo "Logged in as professor. Redirecting to admin_dashboard.php...";
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

        // Debugging output
        echo "Logged in as student. Redirecting to user_dashboard.php...";
        header("Location: user_dashboard.php");
        exit();
    }

    // If no match found
    echo "Invalid username or password";
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
</head>
<body>
    <!-- <div class="try">
        <h2>Login</h2>
    </div>
    <form method="post" action="">
        <label for="username">Username:</label><br>
        <input type="text" id="username" name="username" required><br><br>
        <label for="password">Password:</label><br>
        <input type="password" id="password" name="password" required><br><br>
        <input type="submit" value="Login">
    </form> -->

    <section class="login">
    <!-- <div class="container"> -->
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
                      <h3>Don't have an account? <a href="./signup.php">Sign Up</a></h3>
                  </div>
                </div>
            </div>
          </div>
          <div class="login__desc">
            <div class="login__desc__wrapper">
              <div class="login__desc__info"> 
                <div class="login__desc__title">
                  <h2>Corem ipsum dolor sit amet, consectetur adipiscing elit.</h2>
                </div>
                <div class="login__desc__text">
                  <p>Dorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc vulputate libero et velit interdum, ac aliquet odio mattis. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos.</p>
                </div>
              </div>
              <div class="login__desc__img">
                <img src="./dist/img/20.png" alt="">
              </div>
            </div>
          </div>
      </div>
    <!-- </div> -->
  </section>
</body>
</html>
