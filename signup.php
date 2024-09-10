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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $inputUsername = $_POST['username'];
    $inputIdentifier = $_POST['identifier']; 
    $inputPassword = md5($_POST['password']); 
    $inputName = $_POST['name']; 

 
    if (isset($_POST['role']) && $_POST['role'] == 'student') {
        $checkSql = "SELECT * FROM students WHERE student_number='$inputIdentifier'";
        $checkResult = $conn->query($checkSql);
        
        if ($checkResult->num_rows > 0) {
            echo "Student number already exists. Please choose a different number.";
        } else {
            // Insert into students table
            $sql = "INSERT INTO students (username, student_number, password, name) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $inputUsername, $inputIdentifier, $inputPassword, $inputName);

            if ($stmt->execute()) {
                echo "New student record created successfully";
            } else {
                echo "Error: " . $stmt->error;
            }

            $stmt->close();
        }
    } else {
        // Check if the identifier already exists for professors
        $checkSql = "SELECT * FROM professors WHERE professor_id='$inputIdentifier'";
        $checkResult = $conn->query($checkSql);
        
        if ($checkResult->num_rows > 0) {
            echo "Professor ID already exists. Please choose a different ID.";
        } else {
            // Insert into professors table
            $sql = "INSERT INTO professors (username, password, name, professor_id) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $inputUsername, $inputPassword, $inputName, $inputIdentifier);

            if ($stmt->execute()) {
                echo "New professor record created successfully";
            } else {
                echo "Error: " . $stmt->error;
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
    <link rel="stylesheet" href="./dist/scss/main.min.css">

</head>
<body>
    <h2>Signup</h2>
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
        <input type="submit" value="Signup">
    </form>
</body>
</html>
