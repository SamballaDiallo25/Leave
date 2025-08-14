<?php
session_start();
header('Content-Type: application/json');

// Don't output errors to avoid breaking JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    // Check if user is logged in
    if (!isset($_SESSION['Admin_name']) && !isset($_SESSION['user_name'])) {
        throw new Exception('Not authenticated');
    }

include(__DIR__ . "/../configuration/configuration.php");
    // Database connection
    $conn = new mysqli($servername, $username, $password, $database);
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    // Get current user
    $currentUser = $_SESSION['Admin_name'] ?? $_SESSION['user_name'] ?? '';

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

    // Delete notifications older than 30 days
    $deleteSql = "DELETE FROM notifications WHERE user_id = ? AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $stmt = $conn->prepare($deleteSql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        echo json_encode(['success' => true, 'message' => "Deleted $affected_rows old notifications"]);
    } else {
        throw new Exception('Failed to delete old notifications: ' . $stmt->error);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>