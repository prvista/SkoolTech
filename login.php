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
    <div class="try">
        <h2>Login</h2>
    </div>
    <form method="post" action="">
        <label for="username">Username:</label><br>
        <input type="text" id="username" name="username" required><br><br>
        <label for="password">Password:</label><br>
        <input type="password" id="password" name="password" required><br><br>
        <input type="submit" value="Login">
    </form>
</body>
</html>
