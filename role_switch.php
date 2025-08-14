<?php
// Enhanced role_switch.php with better error handling and session management
session_start();

// Include your database configuration
include_once("../configuration/configuration.php");

// Enhanced session timeout check that preserves role switching data
$session_timeout = 1800; // 30 minutes
if (isset($_SESSION['last_activity'])) {
    if ((time() - $_SESSION['last_activity']) >= $session_timeout) {
        // Store important data before clearing session
        $was_switched_user = isset($_SESSION['is_switched_user']);
        $original_role = isset($_SESSION['original_admin_role']) ? $_SESSION['original_admin_role'] : '';
        
        // Preserve session data for recovery
        $recovery_data = array(
            'was_switched' => $was_switched_user,
            'original_role' => $original_role,
            'timestamp' => time()
        );
        
        session_unset();
        session_destroy();
        
        // Redirect with appropriate timeout parameter
        if ($was_switched_user) {
            header("Location: ../index.php?timeout=1&switched=1&recovery=" . base64_encode(json_encode($recovery_data)));
        } else {
            header("Location: ../index.php?timeout=1");
        }
        exit();
    }
}
$_SESSION['last_activity'] = time();

// Function to log role switch activities
function logRoleSwitch($action, $from_role, $to_role, $username) {
    global $conn;
    
    $log_sql = "INSERT INTO role_switch_log (username, action_type, from_role, to_role, switch_time, ip_address) 
                VALUES (?, ?, ?, ?, NOW(), ?)";
    $log_stmt = $conn->prepare($log_sql);
    if ($log_stmt) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $log_stmt->bind_param("sssss", $username, $action, $from_role, $to_role, $ip_address);
        $log_stmt->execute();
        $log_stmt->close();
    }
}

// Function to redirect with error message
function redirectWithError($message, $location = "../Dashboard/adminDashboard.php") {
    $_SESSION['error_message'] = $message;
    error_log("Role Switch Error: " . $message . " - User: " . ($_SESSION['Admin_name'] ?? $_SESSION['user_name'] ?? 'unknown'));
    header("Location: $location");
    exit();
}

// Function to redirect with success message
function redirectWithSuccess($message, $location) {
    $_SESSION['success_message'] = $message;
    header("Location: $location");
    exit();
}

// Enhanced database connection with error handling
try {
    $conn = new mysqli($servername, $username, $password, $database);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    redirectWithError("System temporarily unavailable. Please try again later.", "../index.php");
}

// Check if user is logged in (either as admin or switched user)
if (!isset($_SESSION['Admin_name']) && !isset($_SESSION['user_name'])) {
    redirectWithError("You must be logged in to perform this action.", "../index.php");
}

// Get the switch action
$switch_to = $_GET['switch_to'] ?? '';

// Validate switch action
if (!in_array($switch_to, ['user', 'admin'])) {
    redirectWithError("Invalid switch action.");
}

if ($switch_to === 'user') {
    // SWITCHING FROM ADMIN TO USER MODE
    
    // Enhanced permission check
    if (!isset($_SESSION['AdminRole'])) {
        redirectWithError("No admin role found in session.");
    }
    
    $allowed_roles = ['HOF', 'Rectorate']; // Only HOF and Rectorate can switch
    if (!in_array($_SESSION['AdminRole'], $allowed_roles)) {
        logRoleSwitch('unauthorized_attempt', $_SESSION['AdminRole'], 'user', $_SESSION['Admin_name']);
        redirectWithError("You don't have permission to switch to user mode. Required roles: " . implode(', ', $allowed_roles));
    }
    
    // Check if already switched
    if (isset($_SESSION['is_switched_user']) && $_SESSION['is_switched_user']) {
        redirectWithError("You are already in user mode.");
    }
    
    $admin_username = $_SESSION['Admin_name'];
    
    // Enhanced user data retrieval with additional checks
    $sql = "SELECT user_name, fullName, Role, user_id, facultyID, status FROM users1 WHERE user_name = ? AND status = 'active'";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        redirectWithError("Database query preparation failed.");
    }
    
    $stmt->bind_param("s", $admin_username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Store comprehensive original admin session data
        $_SESSION['original_session_data'] = array(
            'admin_name' => $_SESSION['Admin_name'],
            'admin_role' => $_SESSION['AdminRole'],
            'admin_id' => $_SESSION['Admin_id'] ?? null,
            'faculty_id' => $_SESSION['Admin_facultyID'] ?? null,
            'user_full_name' => $_SESSION['UserFullName'] ?? '',
            'switch_time' => time(),
            'switch_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        );
        session_start();
echo '<pre>';
print_r($_SESSION);
echo '</pre>';
        
        // Clear admin sessions
        unset($_SESSION['Admin_name']);
        unset($_SESSION['AdminRole']);
        unset($_SESSION['Admin_id']);
        unset($_SESSION['Admin_facultyID']);
        unset($_SESSION['SRole']); // Clear super admin role if set
        
        // Set user sessions - SIMULATING Lecturer
$_SESSION['user_name'] = $row['user_name'];
$_SESSION['Role'] = 'Lecturer'; // 👈 Force Lecturer role
$_SESSION['acting_as'] = 'Lecturer'; // 👈 Optional tag to track
$_SESSION['UserFullName'] = $row['fullName'];
$_SESSION['user_id'] = $row['user_id'];
$_SESSION['user_facultyID'] = $row['facultyID'];
$_SESSION['is_switched_user'] = true;
$_SESSION['switch_timestamp'] = time();

        
        // Log the successful switch
        logRoleSwitch('switch_to_user', $_SESSION['original_session_data']['admin_role'], $row['Role'], $admin_username);
        
        $stmt->close();
        $conn->close();
        
        redirectWithSuccess("Successfully switched to user mode. You can now access user features.", "../Dashboard/userDashboard.php");
        
    } else {
        $stmt->close();
        $conn->close();
        redirectWithError("User account not found or inactive. Please contact administrator.");
    }
    
} elseif ($switch_to === 'admin') {
    // SWITCHING FROM USER BACK TO ADMIN MODE
    
    // Check if user is currently in switched mode
    if (!isset($_SESSION['is_switched_user']) || !$_SESSION['is_switched_user']) {
        redirectWithError("You are not currently in switched user mode.", "../Dashboard/userDashboard.php");
    }
    
    // Check if original admin data exists
    if (!isset($_SESSION['original_session_data']) || !is_array($_SESSION['original_session_data'])) {
        redirectWithError("Original admin session data not found. Please login again.", "../index.php");
    }
    
    $original_data = $_SESSION['original_session_data'];
    
    // Validate original session data
    if (empty($original_data['admin_name']) || empty($original_data['admin_role'])) {
        redirectWithError("Invalid original session data. Please login again.", "../index.php");
    }
    
    // Optional: Check if switch session hasn't expired (24 hours max)
    $max_switch_duration = 24 * 3600; // 24 hours
    if (isset($_SESSION['switch_timestamp']) && (time() - $_SESSION['switch_timestamp']) > $max_switch_duration) {
        redirectWithError("Switch session has expired. Please login again.", "../index.php");
    }
    
    // Verify admin account still exists and is active
    $sql = "SELECT user_name, fullName, Role, user_id FROM users1 WHERE user_name = ? AND Role IN ('Admin', 'HOF', 'Rectorate') AND status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $original_data['admin_name']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result->fetch_assoc()) {
        $stmt->close();
        $conn->close();
        redirectWithError("Original admin account no longer exists or is inactive. Please contact system administrator.", "../index.php");
    }
    $stmt->close();
    
    // Store current user data for logging
    $current_user_role = $_SESSION['Role'] ?? 'unknown';
    $current_username = $_SESSION['user_name'] ?? 'unknown';
    
    // Restore original admin session
    $_SESSION['Admin_name'] = $original_data['admin_name'];
    $_SESSION['AdminRole'] = $original_data['admin_role'];
    $_SESSION['UserFullName'] = $original_data['user_full_name'];
    
    // Restore other admin session variables if they exist
    if (isset($original_data['admin_id'])) {
        $_SESSION['Admin_id'] = $original_data['admin_id'];
    }
    if (isset($original_data['faculty_id'])) {
        $_SESSION['Admin_facultyID'] = $original_data['faculty_id'];
    }
    
    // Special handling for SuperAdmin role (removed - not needed)
    // SuperAdmin functionality removed as per requirements
    
    // Clear user sessions and switch flags
    unset($_SESSION['user_name']);
    unset($_SESSION['Role']);
    unset($_SESSION['user_id']);
    unset($_SESSION['user_facultyID']);
    unset($_SESSION['is_switched_user']);
    unset($_SESSION['switch_timestamp']);
    unset($_SESSION['original_session_data']);
    
    // Log the successful switch back
    logRoleSwitch('switch_to_admin', $current_user_role, $original_data['admin_role'], $original_data['admin_name']);
    
    $conn->close();
    
    // Determine redirect location based on role (SuperAdmin logic removed)
    $redirect_location = "../Dashboard/adminDashboard.php";
    
    redirectWithSuccess("Successfully switched back to admin mode.", $redirect_location);
}


// If we reach here, something went wrong
redirectWithError("An unexpected error occurred during role switching.");
?>