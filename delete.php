<?php
// Start session
session_start();

// Check if user is authenticated (either regular user or super admin)
$is_super_admin = isset($_SESSION["SuperAdminName"]);
$is_regular_user = isset($_SESSION["user_name"]) && isset($_SESSION["user_id"]);

if (!$is_super_admin && !$is_regular_user) {
    header("Location: ../index.php");
    exit();
}

// Include the configuration file
if ($is_super_admin) {
    include "../configuration/configuration.php";
} else {
    include "../configuration/configuration.UserDashboard.php";
}

// Get and validate input parameters
if (!isset($_POST['id']) && !isset($_GET['id'])) {
    die("Error: Missing or empty ID parameter");
}

if (!isset($_POST['table']) && !isset($_GET['table'])) {
    die("Error: Missing table parameter");
}

$id = isset($_POST['id']) ? $_POST['id'] : $_GET['id'];
$table = isset($_POST['table']) ? $_POST['table'] : $_GET['table'];

// Validate ID
if (empty($id) || !is_numeric($id)) {
    die("Error: Invalid ID parameter");
}

// Validate table
if (empty($table)) {
    die("Error: Invalid table parameter");
}

// Database connection
$conn = new mysqli($servername, $username, $password, $database);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Define allowed tables based on user type
if ($is_super_admin) {
    $allowed_tables = ['form1', 'semesters', 'faculties1', 'users1'];
} else {
    // Regular users can only delete from form1 table
    $allowed_tables = ['form1'];
}

if (!in_array($table, $allowed_tables)) {
    $conn->close();
    die("Invalid table name: $table");
}

// Determine the primary key column based on the table name
$primary_key = '';
switch ($table) {
    case 'form1':
        $primary_key = 'submission_number';
        break;
    case 'semesters':
        $primary_key = 'id';
        break;
    case 'faculties1':
        $primary_key = 'faculty_id';
        break;
    case 'users1':
        $primary_key = 'user_id';
        break;
    default:
        $conn->close();
        die("Invalid table name in switch: $table");
}

// For regular users, add additional validation for form1 table
if ($is_regular_user && $table === 'form1') {
    // Check if the record belongs to the current user and verify status
    $check_sql = "SELECT user_id, Department, HumanResource, Rectorate FROM form1 WHERE $primary_key = ?";
    $check_stmt = $conn->prepare($check_sql);
    
    if (!$check_stmt) {
        $conn->close();
        die("Prepare failed for ownership check query: " . $conn->error);
    }
    
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $record = $check_result->fetch_assoc();
    $check_stmt->close();
    
    if (!$record) {
        $conn->close();
        die("Record not found with ID: $id");
    }
    
    // Check if the record belongs to the current user
    if ($record['user_id'] != $_SESSION['user_id']) {
        $conn->close();
        die("Access denied: You can only delete your own applications");
    }
    
    // Check if all statuses are either 'Pending' or '0'
    if (!($record['Department'] === 'Pending' || $record['Department'] === '0') ||
        !($record['HumanResource'] === 'Pending' || $record['HumanResource'] === '0') ||
        !($record['Rectorate'] === 'Pending' || $record['Rectorate'] === '0')) {
        $conn->close();
        die("Cannot delete: Application has already been processed (approved or rejected)");
    }
} else {
    // For super admin, check if record exists
    $check_sql = "SELECT COUNT(*) as count, Department FROM form1 WHERE $primary_key = ?";
    $check_stmt = $conn->prepare($check_sql);

    if (!$check_stmt) {
        $conn->close();
        die("Prepare failed for check query: " . $conn->error);
    }

    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $record = $check_result->fetch_assoc();
    $count = $record['count'];
    $department_status = $record['Department'];
    $check_stmt->close();

    if ($count == 0) {
        $conn->close();
        die("Record not found with ID: $id in table: $table using primary key: $primary_key");
    }

    // Special validation for form1 table (for super admin)
    if ($table === 'form1') {
        $status_check_sql = "SELECT Department, HumanResource, Rectorate FROM form1 WHERE $primary_key = ?";
        $status_stmt = $conn->prepare($status_check_sql);
        if (!$status_stmt) {
            $conn->close();
            die("Prepare failed for status check query: " . $conn->error);
        }
        $status_stmt->bind_param("i", $id);
        $status_stmt->execute();
        $status_result = $status_stmt->get_result();
        $row = $status_result->fetch_assoc();
        $status_stmt->close();
        
        if ($row && !($row['Department'] === 'Pending' || $row['Department'] === '0') ||
            !($row['HumanResource'] === 'Pending' || $row['HumanResource'] === '0') ||
            !($row['Rectorate'] === 'Pending' || $row['Rectorate'] === '0')) {
            $conn->close();
            die("Cannot delete: Application has already been processed (approved or rejected)");
        }
    }
}

// Perform the deletion
$sql = "DELETE FROM $table WHERE $primary_key = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    $conn->close();
    die("Prepare failed for delete query: " . $conn->error);
}

$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $affected_rows = $stmt->affected_rows;
    if ($affected_rows > 0) {
        $stmt->close();
        $conn->close();
        
        // Determine redirect location based on user type and table
        $redirect_url = "";
        
        if ($is_regular_user) {
            // For regular users, always redirect to their dashboard
            echo "Application deleted successfully";
            exit();
        } else {
            // For super admin
            switch ($table) {
                case 'users1':
                    $redirect_url = "../Status/userList.php?deleted=success";
                    break;
                case 'faculties1':
                    $redirect_url = "../Status/adminList.php?deleted=success";
                    break;
                case 'form1':
                case 'semesters':
                default:
                    $redirect_url = "../Dashboard/superAdminDashboard.php?deleted=success";
                    break;
            }
            
            header("Location: $redirect_url");
            exit();
        }
    } else {
        $stmt->close();
        $conn->close();
        die("No rows were deleted. Record may not exist.");
    }
} else {
    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    die("Delete failed: " . $error);
}
?>