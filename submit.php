<?php
session_start();
require_once "../configuration/configuration.php";
require_once "../notifications/notifications.php";
require_once "../lang.php";

// Session timeout logic
$session_timeout = 1800;
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

// Authentication check
if (!isset($_SESSION['user_name']) || !isset($_SESSION['fullName'])) {
    header("Location: ../index.php");
    exit();
}

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to get the next available submission number
function getNextAvailableSubmissionNumber($conn) {
    $query = "SELECT COALESCE(MAX(submission_number), 0) + 1 FROM form1";
    $result = $conn->query($query);
    $row = $result->fetch_row();
    return $row[0];
}

// Function to get user_id by username
function getUserIdByUsername($conn, $username) {
    $stmt = $conn->prepare("SELECT user_id FROM users1 WHERE user_name = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($user_id);
    if ($stmt->fetch()) {
        $stmt->close();
        return $user_id;
    }
    $stmt->close();
    return false;
}


if (isset($_POST['submit'])) {
    // === 1. Form Validation ===
    $errors = [];
    
    // Required fields
    $requiredFields = [
    'Passport_no', 'input', 'PermitStartDate', 'LeaveExpiryDate',
    'PersonToRepresent', 'Address', 'Phone', 'radioInput1',
    'Dayoff', 'AdminFaculty', 'semester_id', 'currentSemester'
];
    
    foreach ($requiredFields as $field) { 
        if (empty($_POST[$field])) { 
            $errors[$field] = __("$field is required.");
        }
    }
    
    // Conditional validation for MakeUpDays
    if (!empty($_POST['radioInput1']) && strtolower($_POST['radioInput1']) === 'yes') {
        if (empty($_POST['MakeUpDays'])) {
            $errors['MakeUpDays'] = __("MakeUpDays is required when you have classes during leave.");
        }
    }
    
    // Validate phone number
    if (!empty($_POST['Phone']) && !preg_match('/^[0-9]{10,15}$/', $_POST['Phone'])) {
        $errors['Phone'] = __("Phone number must be 10 to 15 digits.");
    }
    
    // Validate date range
    if (!empty($_POST['PermitStartDate']) && !empty($_POST['LeaveExpiryDate'])) {
        if (strtotime($_POST['PermitStartDate']) > strtotime($_POST['LeaveExpiryDate'])) {
            $errors['PermitStartDate'] = __("Start date cannot be after end date.");
        }
        $current_date = new DateTime();
        $current_date->setTime(0, 0, 0);
        $start_date = new DateTime($_POST['PermitStartDate']);
        $end_date = new DateTime($_POST['LeaveExpiryDate']);
        if ($start_date < $current_date) {
            $errors['PermitStartDate'] = __("Start date cannot be in the past.");
        }
        if ($end_date < $current_date) {
            $errors['LeaveExpiryDate'] = __("End date cannot be in the past.");
        }
    }
    
    // Validate Annual Leave
    if ($_POST['input'] === 'Annual-Leave' && !empty($_POST['Dayoff']) && intval($_POST['Dayoff']) > 20) {
        $errors['Dayoff'] = __("Annual Leave cannot exceed 20 business days.");
    }
    
    // Validate MakeUpDays
    if (!empty($_POST['MakeUpDays']) && !empty($_POST['PermitStartDate']) && !empty($_POST['LeaveExpiryDate'])) {
        $make_up_date = new DateTime($_POST['MakeUpDays']);
        if ($make_up_date < $current_date) {
            $errors['MakeUpDays'] = __("Make-up date cannot be in the past.");
        }
        if ($make_up_date >= $start_date && $make_up_date <= $end_date) {
            $errors['MakeUpDays'] = __("Make-up date cannot be during the leave period.");
        }
    }
    
    // Redirect with errors
    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
        header("Location: ../Form_1/applicationForm.php");
        exit;
    }
    
    // === 2. Get and sanitize data ===
    $fullName = $_SESSION['fullName'];
    $Passport_no = $_POST['Passport_no'];
    $Unit = $_SESSION['faculty_name'];
    $input = $_POST['input'];
    $RequestTest = !empty($_POST['RequestTest']) ? $_POST['RequestTest'] : null;
    $PermitStartDate = $_POST['PermitStartDate'];
    $LeaveExpiryDate = $_POST['LeaveExpiryDate'];
    $PersonToRepresent = $_POST['PersonToRepresent'];
    $Address = $_POST['Address'];
    $Phone = $_POST['Phone'];
    $ClassDuringLeave = $_POST['radioInput1'];
    
    $MakeUpDays = null;
    if (strtolower($ClassDuringLeave) === 'yes' && !empty($_POST['MakeUpDays'])) {
        $MakeUpDays = $_POST['MakeUpDays'];
    }
    
    $Dayoff = $_POST['Dayoff'];
    $faculty_id = intval($_POST['AdminFaculty']);
    $semester_id = intval($_POST['semester_id']);
    $semester_name = $_POST['currentSemester'];
    $submission_date = date('Y-m-d');

    
    // Get user_id
    $user_id = getUserIdByUsername($conn, $_SESSION['user_name']);
    if (!$user_id) {
        $_SESSION['form_errors'] = ['database' => __("User not found.")];
        $_SESSION['form_data'] = $_POST;
        header("Location: ../Form_1/applicationForm.php");
        exit;
    }

    // Fetch start_date to compute academic year
$start_date = null;
$semester_name_from_db = null;
$stmt = $conn->prepare("SELECT Start_date, Semester_name FROM semesters WHERE id = ?");
$stmt->bind_param("i", $semester_id);
$stmt->execute();
$stmt->bind_result($start_date, $semester_name_from_db);
if ($stmt->fetch()) {
    $start_year = date('Y', strtotime($start_date));
    $end_year = $start_year + 1;
    $semester = $semester_name_from_db . "($start_year/$end_year)";
} else {
    $_SESSION['form_errors'] = ['semester_id' => __("Invalid semester ID.")];
    $_SESSION['form_data'] = $_POST;
    $stmt->close();
    header("Location: ../Form_1/applicationForm.php");
    exit;
}
$stmt->close();
    
    // Default approval status
    $defaultDepartment = "Pending";
    $defaultHumanResource = "Pending";
    $defaultRectorate = "Pending";
    
    // Get submission number
    $submission_number = getNextAvailableSubmissionNumber($conn);
    
    // === 3. Insert into form1 ===
   $sql = "INSERT INTO form1 (
    submission_number, FullName, user_id, Passport_no, Unit, input, RequestTest,
    PermitStartDate, LeaveExpiryDate, PersonToRepresent, Address, Phone,
    ClassDuringLeave, MakeUpDays, Dayoff, faculty_id,
    submission_date, Department, HumanResource, Rectorate, semester_id, semester
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
    "isissssssssssssissisis",
    $submission_number,
    $fullName,
    $user_id,
    $Passport_no,
    $Unit,
    $input,
    $RequestTest,
    $PermitStartDate,
    $LeaveExpiryDate,
    $PersonToRepresent,
    $Address,
    $Phone,
    $ClassDuringLeave,
    $MakeUpDays,
    $Dayoff,
    $faculty_id,
    $submission_date,
    $defaultDepartment,
    $defaultHumanResource,
    $defaultRectorate,
    $semester_id,
    $semester
);
    
    if ($stmt->execute()) {
        // === 4. Send Notifications (FIXED TO USE PROPER FUNCTION) ===
        // Use the comprehensive notification function that sends both database notifications and emails
        sendFormSubmissionNotifications($conn, $submission_number, $user_id, $faculty_id, $fullName, $input);
        
        // Clear form data
        unset($_SESSION['form_errors']);
        unset($_SESSION['form_data']);
        
        $stmt->close();
        $conn->close();
        
        // Redirect to success page
        header("Location: ../Dashboard/userDashboard.php?success=1");
        exit;
    } else {
        $_SESSION['form_errors'] = ['database' => __("Error saving form: ") . $stmt->error];
        $_SESSION['form_data'] = $_POST;
        $stmt->close();
        $conn->close();
        header("Location: ../Form_1/applicationForm.php");
        exit;
    }
}

$conn->close();
header("Location: ../index.php");
exit;
?>