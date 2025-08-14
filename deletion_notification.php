<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['Admin_name']) && !isset($_SESSION['user_name'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

include(__DIR__ . "/configuration/configuration.php");

// Database connection
$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get current user
$currentUser = '';
if (isset($_SESSION['Admin_name'])) {
    $currentUser = $_SESSION['Admin_name'];
} elseif (isset($_SESSION['user_name'])) {
    $currentUser = $_SESSION['user_name'];
}

// Get user ID
$userSql = "SELECT user_id FROM users1 WHERE user_name = ?";
$stmt = $conn->prepare($userSql);
$stmt->bind_param("s", $currentUser);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$user_id = $userData['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$notification_id = $input['notification_id'] ?? null;

if (!$notification_id) {
    echo json_encode(['success' => false, 'message' => 'Notification ID required']);
    exit();
}

// Delete notification
$deleteSql = "DELETE FROM notifications WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($deleteSql);
$stmt->bind_param("ii", $notification_id, $user_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Notification deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Notification not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete notification']);
}

$conn->close();
?>