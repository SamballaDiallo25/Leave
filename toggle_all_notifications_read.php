<?php
session_start();
header('Content-Type: application/json');

// Don't output errors to avoid breaking JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    // Validate HTTP method
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

    // Check current status - if majority are read, mark all as unread, otherwise mark all as read
    $checkSql = "SELECT COUNT(*) as total, SUM(is_read) as read_count FROM notifications WHERE user_id = ?";
    $stmt = $conn->prepare($checkSql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $statusData = $result->fetch_assoc();

    $total = intval($statusData['total']);
    $readCount = intval($statusData['read_count']);
    $unreadCount = $total - $readCount;

    if ($total == 0) {
        throw new Exception('No notifications found');
    }

    // If more than half are read, mark all as unread. Otherwise, mark all as read.
    $newStatus = ($readCount > $unreadCount) ? 0 : 1;

    $updateSql = "UPDATE notifications SET is_read = ? WHERE user_id = ?";
    $stmt = $conn->prepare($updateSql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("ii", $newStatus, $user_id);

    if ($stmt->execute()) {
        $action = $newStatus ? 'read' : 'unread';
        echo json_encode([
            'success' => true,
            'new_status' => $newStatus,
            'message' => "All notifications marked as $action",
            'affected_rows' => $stmt->affected_rows,
            'total' => $total
        ]);
    } else {
        throw new Exception('Failed to update notifications: ' . $stmt->error);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>