<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "skooltech";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $notificationId = $data['id'];

    if ($notificationId) {
        // Option 1: Delete the notification permanently
        $sql = "DELETE FROM notifications WHERE id = ?";
        
        // Option 2: Mark as read (add a `read` column in the `notifications` table if not present)
        // $sql = "UPDATE notifications SET is_read = 1 WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $notificationId);

        if ($stmt->execute()) {
            http_response_code(200); // Success
        } else {
            http_response_code(500); // Error
        }
    }
}
$conn->close();
?>
