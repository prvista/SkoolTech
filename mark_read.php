<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 0); // Do not display errors to users
ini_set('log_errors', 1); // Log errors to a file
ini_set('error_log', 'error_log.txt'); // Log to error_log.txt file

header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "skooltech";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get the incoming JSON data
$data = json_decode(file_get_contents('php://input'), true);

// Debug: Log the incoming data to see what was received
error_log("Received Data: " . print_r($data, true));

// Check if 'id' is present in the data
if (!isset($data['id']) || empty($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Notification ID is missing or invalid']);
    exit();
}

$notifId = $data['id'];
$studentId = $_SESSION['id']; // Ensure the student ID is available in the session

// Update the notification as read
$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND student_id = ?");
$stmt->bind_param("ii", $notifId, $studentId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    error_log("Failed to update notification: " . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Failed to update notification']);
}

$stmt->close();
$conn->close();
?>