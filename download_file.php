<?php
session_start();

// Check if the user is logged in and is a professor
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'professor') {
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

// Get the submission ID from the request
if (isset($_GET['submission_id'])) {
    $submission_id = $_GET['submission_id'];

    // Prepare and execute the SQL statement
    $sql = "SELECT submitted_file FROM assignment_submissions WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $submission_id);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $filePath = $row['submitted_file'];

            // Check if the file exists
            if (file_exists($filePath)) {
                // Set headers for file download
                header('Content-Description: File Transfer');
                header('Content-Type: application/pdf'); // Change this to the correct content type
                header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($filePath));
                readfile($filePath);
                exit;
            } else {
                echo "File does not exist.";
            }
        } else {
            echo "No submission found.";
        }
    } else {
        echo "Error executing query: " . $conn->error;
    }
    $stmt->close();
} else {
    echo "No submission ID provided.";
}

// Close the database connection
$conn->close();
?>
