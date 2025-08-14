<?php
// Create/Replace get_notification_count.php
session_start();
include(__DIR__ . "/../configuration/configuration.php");

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit();
}

// Get current user
$currentUser = '';
if (isset($_SESSION['Admin_name'])) {
    $currentUser = $_SESSION['Admin_name'];
} elseif (isset($_SESSION['user_name'])) {
    $currentUser = $_SESSION['user_name'];
}

if (empty($currentUser)) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Get user ID - Fixed to handle both possible column names
$userSql = "SELECT COALESCE(user_id) as user_id FROM users1 WHERE user_name = ?";
$stmt = $conn->prepare($userSql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}

$stmt->bind_param("s", $currentUser);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();

if (!$userData) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

$user_id = $userData['user_id'];

// Get both unread and total notification counts
$sql = "SELECT 
            COUNT(*) as total_count,
            SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count
        FROM notifications 
        WHERE user_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$countData = $result->fetch_assoc();

echo json_encode([
    'success' => true, 
    'count' => (int)$countData['unread_count'],
    'total_count' => (int)$countData['total_count'],
    'user_id' => $user_id
]);

$conn->close();
?>