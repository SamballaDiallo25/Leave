<?php
session_start();

// Set session timeout duration (30 minutes = 1800 seconds)
$session_timeout = 1800;

// Check if last activity time is set
if (isset($_SESSION['last_activity'])) {
    $inactive_time = time() - $_SESSION['last_activity'];
    if ($inactive_time >= $session_timeout) {
        session_unset();
        session_destroy();
        header("Location: ../index.php?timeout=1");
        exit();
    }
}

$_SESSION['last_activity'] = time();

// Check if user is logged in
if (!isset($_SESSION["Admin_name"])) {
    header("Location: ../index.php");
    exit();
}

require_once "../lang.php";
require_once "../navbarAdminDashboard.php";
include "../configuration/configuration.php";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$admin_role = isset($_SESSION["AdminRole"]) ? $_SESSION["AdminRole"] : "";
$faculty_id = isset($_SESSION["Admin_facultyID"]) ? $_SESSION["Admin_facultyID"] : 0;

// Annual leave configuration
$ANNUAL_LEAVE_TOTAL = 20; // Total annual leave days per user

// Function to calculate days between two dates
function calculateDays($start_date, $end_date) {
    if (empty($start_date) || empty($end_date)) {
        return 0;
    }
    
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = $start->diff($end);
    return $interval->days + 1; // +1 to include both start and end dates
}

// Function to get annual leave summary for users
function getAnnualLeaveSummary($conn, $admin_role, $faculty_id, $annual_leave_total) {
    $leave_summary = [];
    
    // Build query based on admin role
    if ($admin_role == "HOF") {
        $sql = "SELECT FullName, input as leave_type, PermitStartDate, LeaveExpiryDate, 
                Department, HumanResource, Rectorate, submission_date, unit, phone
                FROM form1 WHERE faculty_id = ?
                ORDER BY FullName, submission_date";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $faculty_id);
    } elseif ($admin_role == "HumanResource") {
        $sql = "SELECT FullName, input as leave_type, PermitStartDate, LeaveExpiryDate, 
                Department, HumanResource, Rectorate, submission_date, unit, phone
                FROM form1 WHERE Department = 'Approved'
                ORDER BY FullName, submission_date";
        $stmt = $conn->prepare($sql);
    } elseif ($admin_role == "Rectorate") {
        $sql = "SELECT FullName, input as leave_type, PermitStartDate, LeaveExpiryDate, 
                Department, HumanResource, Rectorate, submission_date, unit, phone
                FROM form1 WHERE Department = 'Approved' AND HumanResource = 'Approved'
                ORDER BY FullName, submission_date";
        $stmt = $conn->prepare($sql);
    } else {
        return [];
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $name = $row['FullName'];
        $leave_type = $row['leave_type'];
        $start_date = $row['PermitStartDate'];
        $end_date = $row['LeaveExpiryDate'];
        $department_status = $row['Department'];
        $hr_status = $row['HumanResource'];
        $rectorate_status = $row['Rectorate'];
        $unit = $row['unit'];
        $phone = $row['phone'];
        
        // Determine if leave is approved based on admin role
        $is_approved = false;
        if ($admin_role == "HOF") {
            $is_approved = ($department_status == 'Approved');
        } elseif ($admin_role == "HumanResource") {
            $is_approved = ($department_status == 'Approved' && $hr_status == 'Approved');
        } elseif ($admin_role == "Rectorate") {
            $is_approved = ($department_status == 'Approved' && $hr_status == 'Approved' && $rectorate_status == 'Approved');
        }
        
        // Initialize user data if not exists
        if (!isset($leave_summary[$name])) {
            $leave_summary[$name] = [
                'name' => $name,
                'unit' => $unit,
                'phone' => $phone,
                'total_annual_leave' => $annual_leave_total,
                'annual_leave_taken' => 0,
                'annual_leave_remaining' => $annual_leave_total,
                'other_leave_taken' => 0,
                'total_leave_taken' => 0,
                'leave_records' => []
            ];
        }
        
        // Only count approved leaves
        if ($is_approved && $start_date && $end_date) {
            $days_taken = calculateDays($start_date, $end_date);
            
            // Add to leave records
            $leave_summary[$name]['leave_records'][] = [
                'type' => $leave_type,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'days' => $days_taken,
                'status' => 'Approved'
            ];
            
            // Categorize leave as annual or other
            if (stripos($leave_type, 'annual') !== false || stripos($leave_type, 'vacation') !== false) {
                $leave_summary[$name]['annual_leave_taken'] += $days_taken;
            } else {
                $leave_summary[$name]['other_leave_taken'] += $days_taken;
            }
            
            $leave_summary[$name]['total_leave_taken'] += $days_taken;
        }
    }
    
    // Calculate remaining annual leave for each user
    foreach ($leave_summary as $name => &$user_data) {
        $user_data['annual_leave_remaining'] = max(0, $annual_leave_total - $user_data['annual_leave_taken']);
    }
    
    $stmt->close();
    return $leave_summary;
}

$leave_summary = getAnnualLeaveSummary($conn, $admin_role, $faculty_id, $ANNUAL_LEAVE_TOTAL);

// Sort users by name
ksort($leave_summary);

// Calculate statistics
$total_users = count($leave_summary);
$users_with_full_leave = 0;
$users_with_partial_leave = 0;
$users_with_no_leave = 0;

foreach ($leave_summary as $user_data) {
    if ($user_data['annual_leave_taken'] == 0) {
        $users_with_no_leave++;
    } elseif ($user_data['annual_leave_taken'] >= $ANNUAL_LEAVE_TOTAL) {
        $users_with_full_leave++;
    } else {
        $users_with_partial_leave++;
    }
}

$conn->close();
?>