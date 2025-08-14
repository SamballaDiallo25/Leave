<?php
session_start();
header('Content-Type: application/json');

// Don't output errors to avoid breaking JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    include(__DIR__ . "/../configuration/configuration.php");

    // Database connection
    $conn = new mysqli($servername, $username, $password, $database);
    if ($conn->connect_error) {
        throw new Exception('Connection failed: ' . $conn->connect_error);
    }

    // Get current user
    $currentUser = $_SESSION['Admin_name'] ?? $_SESSION['user_name'] ?? '';
    if (empty($currentUser)) {
        throw new Exception('Not authenticated');
    }

    // Get user ID - try multiple possible column names
    $userSql = "SELECT * FROM users1 WHERE user_name = ?";
    $stmt = $conn->prepare($userSql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("s", $currentUser);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();

    if (!$userData) {
        throw new Exception('User not found');
    }

    // Try different possible column names for user ID
    $user_id = null;
    $possibleColumns = ['user_id', 'id', 'User_ID', 'ID', 'userid', 'UserID'];
    foreach ($possibleColumns as $column) {
        if (isset($userData[$column])) {
            $user_id = $userData[$column];
            break;
        }
    }

    if ($user_id === null) {
        throw new Exception('Could not find user ID column');
    }

    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['notification_id'])) {
        throw new Exception('Invalid JSON input or missing notification_id');
    }

    $notification_id = intval($input['notification_id']);
    if ($notification_id <= 0) {
        throw new Exception('Invalid notification ID');
    }

    // Get current read status
    $getCurrentStatus = "SELECT is_read FROM notifications WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($getCurrentStatus);
    if (!$stmt) {
        throw new Exception('Get status prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("ii", $notification_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentNotification = $result->fetch_assoc();

    if (!$currentNotification) {
        throw new Exception('Notification not found or access denied');
    }

    // Toggle the read status
    $newStatus = $currentNotification['is_read'] ? 0 : 1;

    $updateSql = "UPDATE notifications SET is_read = ? WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($updateSql);
    if (!$stmt) {
        throw new Exception('Update prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("iii", $newStatus, $notification_id, $user_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'is_read' => $newStatus,
                'message' => $newStatus ? 'Marked as read' : 'Marked as unread'
            ]);
        } else {
            throw new Exception('No rows affected - notification may not exist');
        }
    } else {
        throw new Exception('Failed to update notification: ' . $stmt->error);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>