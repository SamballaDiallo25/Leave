<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
} elseif (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en'; // Default language
}

// Add translation function
if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        // Get language from session instead of URL parameter
        $lang = $_SESSION['lang'] ?? 'en';
        
        // Load translations for Turkish
        if ($lang === 'tr') {
            static $translations = null;
            if ($translations === null) {
                // Include your translation file from Languages directory
                $translation_path = "../Languages/tr.php";
                if (file_exists($translation_path)) {
                    include_once($translation_path);
                }
                $translations = $translations ?? [];
            }
            
            // Return translation if exists, otherwise return original text
            if (isset($translations[$text])) {
                return $translations[$text];
            }
        }
        
        return $text; // Return original text for English or if no translation found
    }
}

include(__DIR__ . "/configuration/configuration.php");   // If configuration.php is in the same folder
$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$session_timeout = 1800; // 30 minutes
if (isset($_SESSION['last_activity'])) {
    if ((time() - $_SESSION['last_activity']) >= $session_timeout) {
        // Check if user is in switched mode before destroying session
        $is_switched = isset($_SESSION['is_switched_user']) && $_SESSION['is_switched_user'];
        $original_admin = isset($_SESSION['original_session_data']['admin_name']) ? $_SESSION['original_session_data']['admin_name'] : '';
        
        // Log timeout event
        error_log("Session timeout - User: " . ($original_admin ?: ($_SESSION['Admin_name'] ?? $_SESSION['user_name'] ?? 'unknown')) . ", Switched: " . ($is_switched ? 'yes' : 'no'));
        
        session_unset();
        session_destroy();
        
        // Redirect with appropriate message
        $redirect_params = "timeout=1";
        if ($is_switched) {
            $redirect_params .= "&switched=1";
        }
        
        header("Location: ../index.php?$redirect_params");
        exit();
    }
}
$_SESSION['last_activity'] = time();
$current_lang = $_SESSION['lang'] ?? 'en';

// FIXED: Retrieve fullName and Role for current user
$fullName = '';
$role = '';
$currentUser = '';
$userDisplayRole = '';

// FIXED: Determine current user based on session variables and role switching
if (isset($_SESSION['is_switched_user']) && $_SESSION['is_switched_user']) {
    // User is in switched mode - use user session data
    $currentUser = $_SESSION['user_name'] ?? '';
    $userDisplayRole = $_SESSION['Role'] ?? '';
    $fullName = $_SESSION['UserFullName'] ?? '';
} else {
    // Normal mode - determine based on session variables
    if (isset($_SESSION['Admin_name'])) {
        $currentUser = $_SESSION['Admin_name'];
        $userDisplayRole = $_SESSION['AdminRole'] ?? $_SESSION['SRole'] ?? '';
    } elseif (isset($_SESSION['user_name'])) {
        $currentUser = $_SESSION['user_name'];
        $userDisplayRole = $_SESSION['Role'] ?? '';
    }
}

if (!empty($currentUser)) {
    $sql = "SELECT fullName, Role FROM users1 WHERE user_name = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $currentUser);
        $stmt->execute();
        $stmt->bind_result($dbFullName, $dbRole);
        
        if ($stmt->fetch()) {
            // Use database full name if session full name is empty
            if (empty($fullName)) {
                $_SESSION['UserFullName'] = $dbFullName;
                $fullName = $dbFullName;
            }
            
            // Update role information for consistency
            $role = $dbRole;
            
            // Handle role session assignments (only if not switched)
            if (!isset($_SESSION['is_switched_user']) || !$_SESSION['is_switched_user']) {
                $normalizedRole = strtolower($role);
                
                // Clear previous role sessions
                unset($_SESSION['AdminRole'], $_SESSION['SRole'], $_SESSION['Role']);
                
                // Assign role session based on role string
                if ($normalizedRole === 'superadmin') {
                    $_SESSION['SRole'] = $role;
                } elseif (in_array($normalizedRole, ['admin', 'hof', 'humanresource', 'rectorate'])) {
                    $_SESSION['AdminRole'] = $role;
                } else {
                    $_SESSION['Role'] = $role;
                }
            }
        }
        $stmt->close();
    }
}

// Function to get notifications for the current user
if (!function_exists('getNotifications')) {
    function getNotifications($conn, $username, $limit = null) {
        $notifications = array();
        
        // Build the LIMIT clause if limit is specified, default to 3 for navbar
        $limitClause = $limit ? "LIMIT " . intval($limit) : "LIMIT 3";
        
        // Get notifications from the notifications table (including read ones)
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
}

// Add new function to get total notification count
if (!function_exists('getTotalNotificationCount')) {
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
}

// Function to format username (replace dots with spaces)
if (!function_exists('formatUsername')) {
    function formatUsername($username) {
        return str_replace('.', ' ', $username);
    }
}

// FIXED: Determine if we should show notifications - CORRECTED LOGIC
$showNotifications = false;
$notifications = array();
$totalNotificationCount = 0;
$notificationUser = '';

if (isset($_SESSION['is_switched_user']) && $_SESSION['is_switched_user']) {
    // In switched user mode - show user notifications
    $showNotifications = true;
    $notificationUser = $_SESSION['user_name'] ?? '';
} elseif (isset($_SESSION['Role']) && !isset($_SESSION['AdminRole']) && !isset($_SESSION['SRole'])) {
    // Regular user mode (not admin, not switched)
    $showNotifications = true;
    $notificationUser = $_SESSION['user_name'] ?? '';
} elseif ((isset($_SESSION['AdminRole']) || isset($_SESSION['SRole'])) && !isset($_SESSION['is_switched_user'])) {
    // Admin mode - show admin notifications
    $showNotifications = true;
    $notificationUser = $_SESSION['Admin_name'] ?? '';
}

// Fetch notifications if we should show them
if ($showNotifications && !empty($notificationUser)) {
    $notifications = getNotifications($conn, $notificationUser, 5);
    $totalNotificationCount = getTotalNotificationCount($conn, $notificationUser);
}

// Utility function to append lang query param
if (!function_exists('appendLangParameter')) {
    function appendLangParameter($url, $lang)
    {
        $parsedUrl = parse_url($url);
        parse_str($parsedUrl['query'] ?? '', $queryParams);
        $queryParams['lang'] = $lang;
        $newQueryString = http_build_query($queryParams);
        $scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '';
        $host = $parsedUrl['host'] ?? '';
        $path = $parsedUrl['path'] ?? '';
        return $scheme . $host . $path . '?' . $newQueryString;
    }
}

if (!function_exists('generateMainLink')) {
    function generateMainLink() {
        // Check if user is switched - if so, show user links
        if (isset($_SESSION['is_switched_user']) && $_SESSION['is_switched_user']) {
            return "../Form_1/applicationForm.php";
        }
        
        // Normal logic
        if (isset($_SESSION['AdminRole']) || isset($_SESSION['SRole'])) {
            return "../Form_1/absenceRecordsForm.php";
        } elseif (isset($_SESSION['Role'])) {
            return "../Form_1/applicationForm.php";
        } else {
            return "#";
        }
    }
}

if (!function_exists('generateMainLink1')) {
    function generateMainLink1() {
        // Check if user is switched - if so, show user dashboard
        if (isset($_SESSION['is_switched_user']) && $_SESSION['is_switched_user']) {
            return "../Dashboard/userDashboard.php";
        }
        
        // Normal logic
        if (isset($_SESSION['SRole'])) {
            return "../Dashboard/superAdminDashboard.php";
        } elseif (isset($_SESSION['AdminRole'])) {
            return "../Dashboard/adminDashboard.php";
        } elseif (isset($_SESSION['Role'])) {
            return "../Dashboard/userDashboard.php";
        } else {
            return "#";
        }
    }
}

if (!function_exists('getDashboardTitle')) {
    function getDashboardTitle() {
        // Check if user is switched
        if (isset($_SESSION['is_switched_user']) && $_SESSION['is_switched_user']) {
            return __("User Dashboard") . " (" . __("Switched Mode") . ")";
        }
        
        // Normal logic
        if (isset($_SESSION['SRole'])) {
            return __("Super Admin Dashboard");
        } elseif (isset($_SESSION['AdminRole'])) {
            return __("Admin Dashboard");
        } elseif (isset($_SESSION['Role'])) {
            return __("User Dashboard");
        }
        return __("Dashboard");
    }
}

if (!function_exists('getDisplayName')) {
    function getDisplayName() {
        global $fullName, $currentUser;
        
        if (!empty($fullName)) {
            // Add mode indicator to full name
            if (isset($_SESSION['is_switched_user']) && $_SESSION['is_switched_user']) {
                return $fullName . ' (' . __("User Mode") . ')';
            }
            return $fullName;
        } elseif (!empty($currentUser)) {
            $formatted = formatUsername($currentUser);
            
            // Add mode indicator
            if (isset($_SESSION['is_switched_user']) && $_SESSION['is_switched_user']) {
                return $formatted . ' (' . __("User Mode") . ')';
            }
            
            return $formatted;
        } else {
            $fallback = $_SESSION['AdminRole'] ?? $_SESSION['SRole'] ?? $_SESSION['Role'] ?? __('Unknown User');
            
            if (isset($_SESSION['is_switched_user']) && $_SESSION['is_switched_user']) {
                return $fallback . ' (' . __("User Mode") . ')';
            }
            
            return $fallback;
        }
    }
}

// Role switching functions
if (!function_exists('getCurrentUserRole')) {
    function getCurrentUserRole() {
        if (isset($_SESSION['is_switched_user']) && $_SESSION['is_switched_user']) {
            return 'switched_user';
        } elseif (isset($_SESSION['SRole'])) {
            return 'super_admin';
        } elseif (isset($_SESSION['AdminRole'])) {
            return 'admin';
        } elseif (isset($_SESSION['Role'])) {
            return 'user';
        }
        return 'unknown';
    }
}

if (!function_exists('canSwitchToUser')) {
    function canSwitchToUser() {
        // Only HOF and Rectorate can switch to user mode
        $allowed_roles = ['HOF', 'Rectorate'];
        
        if (isset($_SESSION['AdminRole']) && in_array($_SESSION['AdminRole'], $allowed_roles)) {
            return true;
        }
        
        return false;
    }
}

if (!function_exists('canSwitchToAdmin')) {
    function canSwitchToAdmin() {
        return isset($_SESSION['is_switched_user']) && $_SESSION['is_switched_user'] && isset($_SESSION['original_session_data']);
    }
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <script>
// Set current language for JavaScript use
window.currentLang = "<?php echo $current_lang; ?>";
</script>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet"> 
  <script src="https://kit.fontawesome.com/242d4b38d8.js" crossorigin="anonymous"></script> 
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
  <link rel="stylesheet" href="../Css/style.css"> 
  <link rel="icon" href="../logo/logo1.png" type="image/png" class="logo">
  <style>
    .Nav-link{
        width: 0px;
    }
     .profile-dropdown {
        min-width: 200px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-top: 8px;
    }

    .profile-dropdown .dropdown-item {
        padding: 10px 16px;
        transition: background-color 0.2s ease;
    }

    .profile-dropdown .dropdown-item:hover {
        background-color: #f8f9fa;
    }

    .profile-dropdown .dropdown-item i {
        width: 16px;
        text-align: center;
    }

    .nav-profile.dropdown-toggle::after {
        margin-left: 8px;
    }

    .nav-profile {
        cursor: pointer;
        transition: color 0.3s ease;
    }

    .nav-profile:hover {
        color: #007bff !important;
    }
    
    .notification-footer {
        background-color: #f8f9fa;
        border-top: 1px solid #dee2e6;
        padding: 10px 16px;
        border-radius: 0 0 8px 8px;
    }

    .view-all-link {
        display: block;
        text-align: center;
        color: #007bff;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: color 0.2s ease;
    }

    .view-all-link:hover {
        color: #0056b3;
        text-decoration: none;
    }

    .view-all-link i {
        margin-right: 5px;
    }

    /* Ensure proper spacing for badge in header */
    .notification-header .badge {
        font-size: 10px;
        padding: 2px 6px;
    }
    
    @media (min-width: 1203px) {
      .main-content {
        margin-left: 300px;
        width: calc(100% - 300px);
        margin-top: 80px;
      }
    }
    .card-inner {
      justify-content: space-between;
    }
    @media (max-width: 768px) {
      .logo {
        display: none !important;
      }
    }

    /* Notification Styles */
    .notification-dropdown {
        position: relative;
        display: inline-block;
    }

    .notification-btn {
        background: none;
        border: none;
        color: #333;
        font-size: 18px;
        position: relative;
        padding: 8px 12px;
        cursor: pointer;
        transition: color 0.3s ease;
    }

    .notification-btn:hover {
        color: #007bff;
    }

    .notification-badge {
        position: absolute;
        top: 5px;
        right: 5px;
        background-color: #dc3545;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 10px;
        min-width: 16px;
        text-align: center;
    }

    .notification-dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        background-color: white;
        min-width: 350px;
        max-width: 400px;
        box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
        z-index: 1000;
        border-radius: 8px;
        border: 1px solid #ddd;
        max-height: 450px;
        overflow-y: auto;
    }

    .notification-header {
        background-color: #f8f9fa;
        padding: 12px 16px;
        border-bottom: 1px solid #dee2e6;
        font-weight: bold;
        color: #495057;
        border-radius: 8px 8px 0 0;
    }

    .notification-item {
        padding: 12px 16px;
        border-bottom: 1px solid #eee;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }

    .notification-item:hover {
        background-color: #f8f9fa;
    }

    .notification-item:last-child {
        border-bottom: none;
        border-radius: 0 0 8px 8px;
    }

    .notification-title {
        font-weight: 600;
        color: #333;
        margin-bottom: 4px;
    }

    .notification-message {
        font-size: 14px;
        color: #666;
        margin-bottom: 4px;
    }

    .notification-time {
        font-size: 12px;
        color: #999;
    }

    .notification-status {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 500;
        margin-top: 4px;
    }

    .status-approved {
        background-color: #d4edda;
        color: #155724;
    }

    .status-rejected {
        background-color: #f8d7da;
        color: #721c24;
    }

    .status-pending {
        background-color: #fff3cd;
        color: #856404;
    }

    .status-info {
        background-color: #d1ecf1;
        color: #0c5460;
    }

    .status-success {
        background-color: #d4edda;
        color: #155724;
    }

    .status-warning {
        background-color: #fff3cd;
        color: #856404;
    }

    .status-danger {
        background-color: #f8d7da;
        color: #721c24;
    }

    .no-notifications {
        padding: 20px;
        text-align: center;
        color: #666;
        font-style: italic;
    }

    .show {
        display: block !important;
    }
    .select {
        width: 60px !important;
    }

    .languages .select {
    padding: 5px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: white;
    font-size: 14px;
    cursor: pointer;
    min-width: 80px;
}

.languages .select:hover {
    border-color: #007bff;
}

.languages .select:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 2px rgba(0,123,255,.25);
}

/* Support RTL pour l'arabe */
html[dir="rtl"] .languages {
    margin-left: 0;
    margin-right: 0.5rem;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .languages .select {
        font-size: 12px;
        padding: 3px 6px;
        min-width: 70px;
    }
}
  </style>
</head>
<body>
<header id="header" class="header fixed-top d-flex align-items-center">
  <div class="container-fluid container-xxl d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center">
      <a class="logo d-flex align-items-center">
        <span class="d-none d-lg-block" id="admin"><?php echo getDashboardTitle(); ?></span>
      </a>
      <i class="bi bi-list toggle-sidebar-btn"></i>
    </div>
    <div class="d-flex align-items-center">
        <!-- Notification Dropdown - FIXED CONDITION -->
        <?php if ($showNotifications): ?>
        <div class="notification-dropdown me-3">
            <button class="notification-btn" onclick="toggleNotifications()" type="button">
                <i class="bi bi-bell"></i>
                <?php if ($totalNotificationCount > 0): ?>
                    <span class="notification-badge"><?php echo $totalNotificationCount; ?></span>
                <?php endif; ?>
            </button>
            
<div class="notification-dropdown-content" id="notificationDropdown">
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
            <div class="notification-item <?php echo ($notification['is_read'] ?? false) ? 'read-notification' : 'unread-notification'; ?>" 
                 onclick="handleNotificationClick(<?php echo $notification['id']; ?>)">
                <?php if (($notification['source'] ?? '') == 'notifications'): ?>
                    <!-- Display notifications from notifications table -->
                    <div class="notification-title <?php echo !($notification['is_read'] ?? true) ? 'fw-bold' : ''; ?>">
                        <?php echo htmlspecialchars(__($notification['message']) ?? ''); ?>
                        <?php if (!($notification['is_read'] ?? true)): ?>
                            <span class="badge bg-primary ms-1" style="font-size: 9px;"><?php echo __("New"); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="notification-time">
                        <?php 
                        try {
                            $timestamp = $notification['created_at'] ?? 'now';
                            if (function_exists('formatTranslatedDate')) {
                                echo formatTranslatedDate($timestamp, 'M j, Y g:i A');
                            } else {
                                echo date('M j, Y g:i A', strtotime($timestamp));
                            }
                        } catch (Exception $e) {
                            echo date('M j, Y g:i A');
                        }
                        ?>
                    </div>
                    <span class="notification-status status-<?php echo $notification['type'] ?? 'info'; ?>">
                        <?php echo __(ucfirst($notification['type'] ?? 'info')); ?>
                    </span>
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
                        $leave_type = $notification['leave_type'] ?? 'general';
                        echo $leave_types[$leave_type] ?? ucfirst($leave_type) . ' ' . __('Leave');
                        ?>
                    </div>
                    <?php if (!empty($notification['response_message'] ?? '')): ?>
                        <div class="notification-message">
                            <?php echo htmlspecialchars($notification['response_message']); ?>
                        </div>
                    <?php endif; ?>
                    <div class="notification-time">
                        <?php 
                        try {
                            $timestamp = $notification['created_at'] ?? 'now';
                            if (function_exists('formatTranslatedDate')) {
                                echo formatTranslatedDate($timestamp, 'M j, Y g:i A');
                            } else {
                                echo date('M j, Y g:i A', strtotime($timestamp));
                            }
                        } catch (Exception $e) {
                            echo date('M j, Y g:i A');
                        }
                        ?>
                    </div>
                    <span class="notification-status status-<?php echo $notification['status'] ?? 'pending'; ?>">
                        <?php echo __(ucfirst($notification['status'] ?? 'pending')); ?>
                    </span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

                
                <!-- Always show "View All" link -->
                <div class="notification-footer">
                    <a href="../notifications/all_notifications.php" class="view-all-link">
                        <i class="bi bi-list"></i>
                        <?php echo __("View All Notifications"); ?>
                        <?php if ($totalNotificationCount > 0): ?>
                            (<?php echo $totalNotificationCount; ?> <?php echo __("unread"); ?>)
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        </div>

       <!-- User Profile Dropdown -->
        <?php
        $currentPageUrl = $_SERVER['REQUEST_URI'];
        $displayName = getDisplayName();
        ?>
        
        <div class="nav-item dropdown">
            <a class="Nav-link nav-profile d-flex align-items-center pe-0 dropdown-toggle" href="#" data-bs-toggle="dropdown">
                <i class="fas fa-user"></i>&nbsp;<?php echo $displayName; ?>
            </a>
            
            <ul class="dropdown-menu dropdown-menu-end profile-dropdown">
                <!-- Profile Information -->
                <li>
                    <div class="dropdown-item-text">
                        <strong><?php echo $displayName; ?></strong><br>
                        <small class="text-muted"><?php echo $userDisplayRole; ?></small>
                    </div>
                </li>
                <li><hr class="dropdown-divider"></li>
                
                <!-- Role Switching Options -->
                <?php if (canSwitchToUser() && !isset($_SESSION['is_switched_user'])): ?>
                    <!-- Switch to User Mode (for HOF and Rectorate admins) -->
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="../Admin/role_switch.php?switch_to=user">
                            <i class="bi bi-arrow-right-circle me-2"></i>
                            <span><?php echo __("Switch to User Mode"); ?></span>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php if (canSwitchToAdmin()): ?>
                    <!-- Back to Admin Mode (for switched users) -->
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="../Admin/role_switch.php?switch_to=admin">
                            <i class="bi bi-arrow-left-circle me-2"></i>
                            <span><?php echo __("Back to Admin Mode"); ?></span>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php if (canSwitchToUser() || canSwitchToAdmin()): ?>
                    <li><hr class="dropdown-divider"></li>
                <?php endif; ?>
                
                
            </ul>
        </div>

        <!-- Language Dropdown -->
     <div class="languages ml-2">
    <select class="select" onchange="changeLanguage(this)" id="languageSelect">
        <option value="">Lang</option>
        <option value="<?php echo appendLangParameter($_SERVER['REQUEST_URI'], 'en'); ?>" <?php echo ($current_lang === 'en') ? 'selected' : ''; ?>>En</option>
        <option value="<?php echo appendLangParameter($_SERVER['REQUEST_URI'], 'fr'); ?>" <?php echo ($current_lang === 'fr') ? 'selected' : ''; ?>>Fr</option>
        <option value="<?php echo appendLangParameter($_SERVER['REQUEST_URI'], 'ru'); ?>" <?php echo ($current_lang === 'ru') ? 'selected' : ''; ?>>Ru</option>
        <option value="<?php echo appendLangParameter($_SERVER['REQUEST_URI'], 'ar'); ?>" <?php echo ($current_lang === 'ar') ? 'selected' : ''; ?>>Ar</option>
        <option value="<?php echo appendLangParameter($_SERVER['REQUEST_URI'], 'tr'); ?>" <?php echo ($current_lang === 'tr') ? 'selected' : ''; ?>>Tr</option>
    </select>
</div>
    </div>
</header>

<aside id="sidebar" class="sidebar">
    <div class="sidebar-logo text-center mb-5">
        <a href="#">
            <img src="../logo/logo1.png" alt="Logo" style="max-width: 150px; height: auto;">
        </a>
    </div>
    
    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link" href="<?php echo generateMainLink1(); ?>">
                <i class="bi bi-grid"></i>
                <span><?php echo __("Dashboard"); ?></span>
            </a>
        </li>
        
        <?php if (!isset($_SESSION['SRole'])) : ?>
            <?php if (isset($_SESSION['AdminRole']) && !isset($_SESSION['is_switched_user'])): ?>
                <!-- Admins see Absence Records -->
                <li class="nav-item">
                    <a class="nav-link" href="../Form_1/absenceRecordsForm.php">
                        <i class="bi bi-menu-button-wide"></i>
                        <span><?php echo __("Absence Records"); ?></span>
                    </a>
                </li>
            <?php elseif (isset($_SESSION['Role']) || isset($_SESSION['is_switched_user'])): ?>
                <!-- Logged-in users (not admins) or switched users see Applications -->
                <li class="nav-item">
                    <a class="nav-link" href="../Form_1/applicationForm.php">
                        <i class="bi bi-menu-button-wide"></i>
                        <span><?php echo __("Applications"); ?></span>
                    </a>
                </li>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['SRole'])) : ?>
        <li class="nav-item">
            <a class="nav-link collapsed" href="#" id="usersButton">
                <i class="bi bi-people"></i>
                <span><?php echo __("Users"); ?></span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="forms-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                <li class="nav-link"><a href="../Form_1/addUser.php" class="ms-3"><i class="fa-solid fa-plus"></i><span><?php echo __("Add User"); ?></span></a></li>
                <li class="nav-link"><a href="../Form_1/addFaculty.php" class="ms-3"><i class="fa-solid fa-plus"></i><span><?php echo __("Add Faculty"); ?></span></a></li>
                <li class="nav-link"><a href="../Status/adminList.php" class="ms-3"><i class="bi bi-table"></i><span><?php echo __("Admin List"); ?></span></a></li>
                <li class="nav-link"><a href="../Status/userList.php" class="ms-3"><i class="bi bi-table"></i><span><?php echo __("User List"); ?></span></a></li>
            </ul>
        </li>
        <?php endif; ?>
        
        <li class="nav-item">
            <a class="nav-link collapsed logout-link" href="../Dashboard/logout.php">
                <i class="bi bi-box-arrow-in-right"></i>
                <span><?php echo __("Logout"); ?></span>
            </a>
        </li>
    </ul>
</aside>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Function to handle notification click
    function handleNotificationClick(notificationId) {
        // Mark notification as read
        markNotificationAsRead(notificationId);
    }

    // Function to update notification count in navbar
    function updateNotificationCount() {
        fetch('get_notification_count.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const badge = document.querySelector('.notification-badge');
                    const headerBadge = document.querySelector('.notification-header .badge');
                    
                    if (data.count > 0) {
                        // Show badge with count
                        if (badge) {
                            badge.textContent = data.count;
                            badge.style.display = 'block';
                        } else {
                            // Create badge if it doesn't exist
                            const notificationBtn = document.querySelector('.notification-btn');
                            if (notificationBtn) {
                                const newBadge = document.createElement('span');
                                newBadge.className = 'notification-badge';
                                newBadge.textContent = data.count;
                                notificationBtn.appendChild(newBadge);
                            }
                        }
                        
                        // Update header badge in dropdown
                        if (headerBadge) {
                            headerBadge.textContent = data.count + ' ' + (window.currentLang === 'tr' ? 'Okunmamış' : 'Unread');
                            headerBadge.style.display = 'inline';
                        }
                    } else {
                        // Hide badge when count is 0
                        if (badge) {
                            badge.style.display = 'none';
                        }
                        if (headerBadge) {
                            headerBadge.style.display = 'none';
                        }
                    }
                }
            })
            .catch(error => console.error('Error updating notification count:', error));
    }

    // Function to refresh notifications in dropdown
    function refreshNotifications() {
        fetch('../notifications/get_notifications_dropdown.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const dropdown = document.getElementById('notificationDropdown');
                    
                    if (dropdown) {
                        // Update the dropdown content
                        dropdown.innerHTML = data.html;
                    }
                    
                    // Update the count
                    updateNotificationCount();
                }
            })
            .catch(error => console.error('Error refreshing notifications:', error));
    }

    // Enhanced toggleNotifications function with refresh
    function toggleNotifications() {
        var dropdown = document.getElementById("notificationDropdown");
        dropdown.classList.toggle("show");
        
        // Refresh notifications when opening dropdown
        if (dropdown.classList.contains("show")) {
            refreshNotifications();
        }
    }

    // Function to mark notification as read from navbar dropdown
    function markNotificationAsRead(notificationId) {
        fetch('mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ notification_id: notificationId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Refresh the notifications and count
                refreshNotifications();
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // Function to delete notification from navbar dropdown
    function deleteNotificationFromNavbar(notificationId) {
        if (confirm(window.currentLang === 'tr' ? 'Bu bildirimi silin?' : 'Delete this notification?')) {
            fetch('delete_notification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ notification_id: notificationId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Refresh the notifications and count
                    refreshNotifications();
                }
            })
            .catch(error => console.error('Error:', error));
        }
    }

    // Auto-refresh notification count every 30 seconds
    setInterval(updateNotificationCount, 30000);

    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.getElementById('sidebar');
        const toggleButton = document.querySelector('.toggle-sidebar-btn');
        
        if (toggleButton) {
            toggleButton.addEventListener('click', function () {
                sidebar.classList.toggle('show-sidebar');
            });
        }

        const usersButton = document.getElementById("usersButton");
        if (usersButton) {
            usersButton.addEventListener("click", function (event) {
                event.preventDefault();
                const usersList = document.getElementById("forms-nav");
                usersList.classList.toggle("collapse");
            });
        }

        // Initialize notification count on page load
        updateNotificationCount();
    });

    function changeLanguage(selectElement) {
    if (selectElement.value) {
        window.location.href = selectElement.value;
    }
}

    // Close the dropdown if the user clicks outside of it
    window.onclick = function(event) {
        if (!event.target.matches('.notification-btn') && !event.target.closest('.notification-dropdown')) {
            var dropdowns = document.getElementsByClassName("notification-dropdown-content");
            for (var i = 0; i < dropdowns.length; i++) {
                var openDropdown = dropdowns[i];
                if (openDropdown.classList.contains('show')) {
                    openDropdown.classList.remove('show');
                }
            }
        }
    }
</script>
</body>
</html>