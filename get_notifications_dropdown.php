<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['Admin_name']) && !isset($_SESSION['user_name'])) {
    echo json_encode(['success' => false, 'html' => '']);
    exit();
}

include(__DIR__ . "/configuration/configuration.php");
include(__DIR__ . "/lang.php"); // Include language functions

// Database connection
$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'html' => '']);
    exit();
}

// Get current user
$currentUser = '';
if (isset($_SESSION['Admin_name'])) {
    $currentUser = $_SESSION['Admin_name'];
} elseif (isset($_SESSION['user_name'])) {
    $currentUser = $_SESSION['user_name'];
}

// Include the same functions from navbar.php
function getNotifications($conn, $username, $limit = null) {
    $notifications = array();
    
    // Build the LIMIT clause if limit is specified
    $limitClause = $limit ? "LIMIT " . intval($limit) : "LIMIT 10";
    
    // First, try to get notifications from the notifications table (including read ones)
    $sql = "SELECT id, message, type, url, is_read, created_at 
            FROM notifications 
            WHERE user_id = (SELECT user_id FROM users1 WHERE user_name = ?) 
            ORDER BY created_at DESC $limitClause";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $notifications[] = array(
                'id' => $row['id'],
                'message' => $row['message'],
                'type' => $row['type'],
                'url' => $row['url'],
                'is_read' => $row['is_read'],
                'created_at' => $row['created_at'],
                'source' => 'notifications'
            );
        }
        $stmt->close();
    }
    
    // If no notifications in notifications table, check form1 table for leave requests
    if (empty($notifications)) {
        // Check if form1 table has the required columns first
        $checkColumns = "SHOW COLUMNS FROM form1";
        $result = $conn->query($checkColumns);
        $columns = array();
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        // Build query based on available columns
        $hasStatus = in_array('status', $columns);
        $hasLeaveType = in_array('leave_type', $columns);
        $hasResponseMessage = in_array('response_message', $columns);
        $hasCreatedAt = in_array('created_at', $columns);
        $hasUserName = in_array('user_name', $columns) || in_array('lecturer_username', $columns);
        
        if ($hasUserName) {
            $userNameColumn = in_array('user_name', $columns) ? 'user_name' : 'lecturer_username';
            
            $selectFields = "id";
            if ($hasLeaveType) $selectFields .= ", leave_type";
            if ($hasStatus) $selectFields .= ", status";
            if ($hasResponseMessage) $selectFields .= ", response_message";
            if ($hasCreatedAt) $selectFields .= ", created_at";
            
            $whereClause = "$userNameColumn = ?";
            // Show all notifications, not just non-pending ones
            
            $orderClause = $hasCreatedAt ? "ORDER BY created_at DESC" : "ORDER BY id DESC";
            
            $sql = "SELECT $selectFields FROM form1 WHERE $whereClause $orderClause $limitClause";
            
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    $leaveType = $hasLeaveType ? $row['leave_type'] : 'general';
                    $status = $hasStatus ? $row['status'] : 'processed';
                    $responseMessage = $hasResponseMessage ? $row['response_message'] : '';
                    $createdAt = $hasCreatedAt ? $row['created_at'] : date('Y-m-d H:i:s');
                    
                    $notifications[] = array(
                        'id' => $row['id'],
                        'leave_type' => $leaveType,
                        'status' => $status,
                        'response_message' => $responseMessage,
                        'created_at' => $createdAt,
                        'source' => 'form1'
                    );
                }
                $stmt->close();
            }
        }
    }
    
    return $notifications;
}

function getTotalNotificationCount($conn, $username) {
    $totalCount = 0;
    
    // Count notifications from the notifications table
    $sql = "SELECT COUNT(*) as count 
            FROM notifications 
            WHERE user_id = (SELECT user_id FROM users1 WHERE user_name = ?) 
            AND is_read = 0";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $totalCount = $row['count'];
        $stmt->close();
    }
    
    // If no notifications in notifications table, count from form1 table
    if ($totalCount == 0) {
        $checkColumns = "SHOW COLUMNS FROM form1";
        $result = $conn->query($checkColumns);
        $columns = array();
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        $hasStatus = in_array('status', $columns);
        $hasUserName = in_array('user_name', $columns) || in_array('lecturer_username', $columns);
        
        if ($hasUserName) {
            $userNameColumn = in_array('user_name', $columns) ? 'user_name' : 'lecturer_username';
            $whereClause = "$userNameColumn = ?";
            if ($hasStatus) $whereClause .= " AND status != 'pending'";
            
            $sql = "SELECT COUNT(*) as count FROM form1 WHERE $whereClause";
            
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $totalCount = $row['count'];
                $stmt->close();
            }
        }
    }
    
    return $totalCount;
}

// Get notifications and count
$notifications = array();
$totalNotificationCount = 0;
if (!empty($currentUser) && !isset($_SESSION['SRole'])) {
    $notifications = getNotifications($conn, $currentUser, 3); // Always limit to 3 for dropdown
    $totalNotificationCount = getTotalNotificationCount($conn, $currentUser);
}

// Generate HTML for dropdown
ob_start();
?>
        <div class="notification-header">
            <?php echo __("Recent Notifications"); ?>
            <?php if ($totalNotificationCount > 0): ?>
                <span class="badge badge-primary ms-2"><?php echo $totalNotificationCount; ?> <?php echo __("Unread"); ?></span>
            <?php endif; ?>
        </div>
        
        <?php if (empty($notifications)): ?>
            <div class="no-notifications">
                <?php echo __("No notifications available"); ?>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-item <?php echo $notification['is_read'] ? 'read-notification' : 'unread-notification'; ?>">
                    <?php if ($notification['source'] == 'notifications'): ?>
                        <!-- Display notifications from notifications table -->
                        <div class="notification-title <?php echo !$notification['is_read'] ? 'fw-bold' : ''; ?>">
                            <?php echo htmlspecialchars($notification['message']); ?>
                            <?php if (!$notification['is_read']): ?>
                                <span class="badge bg-primary ms-1" style="font-size: 9px;">New</span>
                            <?php endif; ?>
                        </div>
                        <div class="notification-time">
                            <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                        </div>
                        <span class="notification-status status-<?php echo $notification['type']; ?>">
                            <?php echo __(ucfirst($notification['type'])); ?>
                        </span>
                        <?php if (!$notification['is_read']): ?>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-outline-primary me-1" onclick="markNotificationAsRead(<?php echo $notification['id']; ?>)">
                                    <i class="bi bi-check"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteNotificationFromNavbar(<?php echo $notification['id']; ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Display notifications from form1 table -->
                        <div class="notification-title">
                            <?php 
                            $leave_types = [
                                'annual' => __('Annual Leave'),
                                'sick' => __('Sick Leave'),
                                'excuse' => __('Excuse Leave'),
                                'other' => __('Other Leave'),
                                'general' => __('Leave Request')
                            ];
                            echo $leave_types[$notification['leave_type']] ?? ucfirst($notification['leave_type']) . ' ' . __('Leave');
                            ?>
                        </div>
                        <?php if (!empty($notification['response_message'])): ?>
                            <div class="notification-message">
                                <?php echo htmlspecialchars($notification['response_message']); ?>
                            </div>
                        <?php endif; ?>
                        <div class="notification-time">
                            <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                        </div>
                        <span class="notification-status status-<?php echo $notification['status']; ?>">
                            <?php echo __(ucfirst($notification['status'])); ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <!-- Always show "View All" link -->
            <div class="notification-footer">
                <a href="all_notifications.php" class="view-all-link">
                    <i class="bi bi-list"></i>
                    <?php echo __("View All Notifications"); ?>
                    <?php if ($totalNotificationCount > 0): ?>
                        (<?php echo $totalNotificationCount; ?> <?php echo __("unread"); ?>)
                    <?php endif; ?>
                </a>
            </div>
        <?php endif; ?>
<?php
$html = ob_get_clean();

echo json_encode(['success' => true, 'html' => $html]);

$conn->close();
?>