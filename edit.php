<?php
session_start();
ob_start();

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
if (!isset($_SESSION["user_name"]) || !isset($_SESSION["Role"])) {
    header("Location: ../index.php");
    exit();
}

require_once "../lang.php";
require_once "../navbar.php";
include "../configuration/configuration.php";
$conn = new mysqli($servername, $username, $password, $database);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = $_GET["id"];

// Get form errors and data
$form_errors = isset($_SESSION['form_errors']) ? $_SESSION['form_errors'] : [];
$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
unset($_SESSION['form_errors']);
unset($_SESSION['form_data']);

// Get success flag
$success = isset($_GET['success']) && $_GET['success'] === '1';

// Initialize variables with default values
$submission_number = '';
$user_id = '';
$FullName = '';
$passport_no = '';
$Unit = '';
$input = '';
$PermitStartDate = '';
$LeaveExpiryDate = '';
$PersonToRepresent = '';
$Address = '';
$Phone = '';
$faculty_id = '';
$submission_date = '';
$ClassDuringLeave = '';
$MakeUpDays = '';
$Dayoff = '';
$Department = '';
$HumanResource = '';
$Rectorate = '';
$Comment = '';
$RequestTest = '';
$semester = '';
$faculty_name = '';

// Fetch the form data
$sql = "SELECT * FROM form1 WHERE submission_number = ? LIMIT 1";
$stmt_form = $conn->prepare($sql);
$stmt_form->bind_param("i", $id);
$stmt_form->execute();
$result = $stmt_form->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $submission_number = $row['submission_number'];
    $user_id = $row['user_id'];
    $FullName = $row['FullName'];
    $passport_no = $row['passport_no'];
    $Unit = $row['Unit'];
    $input = $row['input'];
    $PermitStartDate = $row['PermitStartDate'];
    $LeaveExpiryDate = $row['LeaveExpiryDate'];
    $PersonToRepresent = $row['PersonToRepresent'];
    $Address = $row['Address'];
    $Phone = $row['Phone'];
    $faculty_id = $row['faculty_id'];
    $submission_date = $row['submission_date'];
    $ClassDuringLeave = $row['ClassDuringLeave'];
    $MakeUpDays = $row['MakeUpDays'];
    $Dayoff = $row['Dayoff'];
    $Department = $row['Department'];
    $HumanResource = $row['HumanResource'];
    $Rectorate = $row['Rectorate'];
    $Comment = $row['Comment'];
    $RequestTest = $row['RequestTest'];
    $semester = $row['semester'];
}
$stmt_form->close();

// Fetch faculty name
if ($faculty_id) {
    $sql = "SELECT faculty_name FROM faculties1 WHERE faculty_id = ?";
    $stmt_faculty = $conn->prepare($sql);
    $stmt_faculty->bind_param("i", $faculty_id);
    $stmt_faculty->execute();
    $result_faculty = $stmt_faculty->get_result();
    
    if ($result_faculty->num_rows > 0) {
        $faculty_row = $result_faculty->fetch_assoc();
        $faculty_name = $faculty_row['faculty_name'];
        $_SESSION["faculty_name"] = $faculty_name;
    }
    $stmt_faculty->close();
}

// Retrieve faculty head
$sql_admin = "SELECT faculty_head FROM faculties1 WHERE faculty_id = ?";
$stmt_admin = $conn->prepare($sql_admin);
$stmt_admin->bind_param("i", $faculty_id);
$stmt_admin->execute();
$stmt_admin->bind_result($adminName);
if ($stmt_admin->fetch()) {
    $_SESSION["admin_name"] = $adminName;
} else {
    $_SESSION["admin_name"] = "Not Assigned";
    $form_errors['general'] = __("No admin found for the specified faculty.");
}
$stmt_admin->close();

// Get current semester info (like in original form)
$currentDate = date("Y-m-d");
$sql_semester = "SELECT id, Semester_name, Start_date, End_date FROM semesters";
$stmt_semester = $conn->prepare($sql_semester);
$stmt_semester->execute();
$result_semester = $stmt_semester->get_result();

$currentSemester = $semester; // Use existing value from database first
$currentSemesterId = null;

while ($row_semester = $result_semester->fetch_assoc()) {
    if ($currentDate >= $row_semester['Start_date'] && $currentDate <= $row_semester['End_date']) {
        $currentSemester = $row_semester['Semester_name'];
        $currentSemesterId = $row_semester['id'];
        break;
    }
}
$stmt_semester->close();

if (isset($_POST["submit"])) {
    // Initialize error array
    $errors = [];
    
    // Retrieve and validate form data
     $FullName = trim($_POST["FullName"] ?? '');
    $passport_no = trim($_POST["passport_no"] ?? '');
    $Unit = $_SESSION["faculty_name"];
    $input = $_POST["input"] ?? '';
    $RequestTest = !empty($_POST["RequestTest"]) ? trim($_POST["RequestTest"]) : null;   
    $PermitStartDate = $_POST["PermitStartDate"] ?? '';
    $LeaveExpiryDate = $_POST["LeaveExpiryDate"] ?? '';
    $PersonToRepresent = trim($_POST["PersonToRepresent"] ?? '');
    $Address = trim($_POST["Address"] ?? '');
    $Phone = trim($_POST["Phone"] ?? '');
    $ClassDuringLeave = $_POST["radioInput1"] ?? '';
    $semester_id = intval($_POST["semester_id"] ?? $currentSemesterId);
    $MakeUpDays = ($ClassDuringLeave === "yes" && !empty($_POST["MakeUpDays"])) ? trim($_POST["MakeUpDays"]) : null;    $Dayoff = trim($_POST["Dayoff"] ?? '');
    $faculty_id = $_SESSION["faculty_id"];

    // Format semester with academic year
    $base_semester = $_POST["currentSemester"] ?? $currentSemester;
    $current_year = date('Y');
    $next_year = $current_year + 1;
    $semester = $base_semester . " (" . $current_year . "/" . $next_year . ")";

    // Enhanced validation rules
    if (empty($FullName)) {
        $errors['FullName'] = __("Full Name is required");
    }
    
    if (empty($passport_no)) {
        $errors['passport_no'] = __("Passport Number is required");
    } elseif (!preg_match('/^[A-Za-z0-9]{6,9}$/', $passport_no)) {
        $errors['passport_no'] = __("Passport number must be 6 to 9 letters or digits");
    }
    
    if (empty($input)) {
        $errors['input'] = __("Permission type is required");
    }
    
    if (empty($PermitStartDate)) {
        $errors['PermitStartDate'] = __("Permit start date is required");
    } elseif (strtotime($PermitStartDate) < strtotime(date('Y-m-d'))) {
        $errors['PermitStartDate'] = __("Start date cannot be in the past");
    }
    
    if (empty($LeaveExpiryDate)) {
        $errors['LeaveExpiryDate'] = __("Leave end date is required");
    } elseif (strtotime($LeaveExpiryDate) < strtotime(date('Y-m-d'))) {
        $errors['LeaveExpiryDate'] = __("End date cannot be in the past");
    } elseif (!empty($PermitStartDate) && strtotime($LeaveExpiryDate) < strtotime($PermitStartDate)) {
        $errors['LeaveExpiryDate'] = __("End date cannot be before start date");
    }
    
    if (empty($PersonToRepresent)) {
        $errors['PersonToRepresent'] = __("Person to represent is required");
    }
    
    if (empty($Address)) {
        $errors['Address'] = __("Permission address is required");
    }
    
    if (empty($Phone)) {
        $errors['Phone'] = __("Phone number is required");
    } elseif (!preg_match('/^[0-9]{10,15}$/', $Phone)) {
        $errors['Phone'] = __("Phone number must be between 10 and 15 digits");
    }
    
    if (empty($ClassDuringLeave)) {
        $errors['radioInput1'] = __("Please select if you have classes during leave");
    }
    
    if (empty($Dayoff)) {
        $errors['Dayoff'] = __("Total days is required");
    }
    
    // Annual leave validation
    if ($input === "Annual-Leave" && !empty($Dayoff) && intval($Dayoff) > 20) {
        $errors['Dayoff'] = __("Maximum leave duration for Annual-Leave is 20 business days");
    }
    
    // Make-up days validation
    if ($ClassDuringLeave === "yes") {
        if (empty($MakeUpDays)) {
            $errors['MakeUpDays'] = __("Make-up days is required when you have classes during leave");
        } elseif (!empty($PermitStartDate) && !empty($LeaveExpiryDate)) {
            $makeUpDate = strtotime($MakeUpDays);
            $startDate = strtotime($PermitStartDate);
            $endDate = strtotime($LeaveExpiryDate);
            $currentDate = strtotime(date('Y-m-d'));
            
            if ($makeUpDate < $currentDate) {
                $errors['MakeUpDays'] = __("Make-up date cannot be in the past");
            } elseif ($makeUpDate >= $startDate && $makeUpDate <= $endDate) {
                $errors['MakeUpDays'] = __("Make-up date cannot be during the leave period");
            }
        }
    }
    
    // Store form data in session for repopulation
    $_SESSION['form_data'] = $_POST;
    
    // If no errors, proceed with database update
    if (empty($errors)) {
        $sql = "UPDATE `form1` SET `FullName`=?, `passport_no`=?, `Unit`=?, `input`=?, 
`PermitStartDate`=?, `LeaveExpiryDate`=?, `PersonToRepresent`=?, 
`Address`=?, `Phone`=?, `faculty_id`=?, `ClassDuringLeave`=?, 
`MakeUpDays`=?, `Dayoff`=?, `RequestTest`=?, `semester_id`=?, `semester`=? 
WHERE submission_number=?";        
        $stmt_update = $conn->prepare($sql);
     $stmt_update->bind_param(
    "sssssssssissssisi", // 17 characters total
    $FullName,          // 1  - s
    $passport_no,       // 2  - s  
    $Unit,              // 3  - s
    $input,             // 4  - s
    $PermitStartDate,   // 5  - s
    $LeaveExpiryDate,   // 6  - s
    $PersonToRepresent, // 7  - s
    $Address,           // 8  - s
    $Phone,             // 9  - s
    $faculty_id,        // 10 - i (integer)
    $ClassDuringLeave,  // 11 - s
    $MakeUpDays,        // 12 - s
    $Dayoff,            // 13 - s
    $RequestTest,       // 14 - s
    $semester_id,       // 15 - i (integer)
    $semester,          // 16 - s
    $id                 // 17 - i (integer) - WHERE clause
);
        
        if ($stmt_update->execute()) {
            $stmt_update->close();
            unset($_SESSION['form_data']);
            header("Location: ../Dashboard/userDashboard.php?success=1");
            exit();
        } else {
            $errors['general'] = __("Database update failed: ") . $stmt_update->error;
        }
        $stmt_update->close();
    } else {
        $_SESSION['form_errors'] = $errors;
        header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] == 'tr' ? 'tr' : 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __("Edit Application"); ?></title>
    <link rel="icon" href="../logo/logo1.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .error {
            color: red;
            font-size: 14px;
            margin-top: 5px;
        }
        input.error-border, select.error-border {
            border-color: red !important;
        }
        .SubmitButton1 {
            background-color: #141414;
            color: white;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            border-radius: 5%;
            width: 100px;
            margin-right: 10px;
        }
        @media (max-width: 430px) {
            .button {
                display: flex;
                justify-content: center;
                align-items: center;
            }
        }
        h5 { text-align: center; padding: 10px 0; }
    </style>
</head>
<body>
<section class="main-content">
    <div class="container mt-5">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo __("Application updated successfully!"); ?></div>
        <?php endif; ?>
        <?php if (!empty($form_errors['general'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($form_errors['general']); ?></div>
        <?php elseif (!empty($form_errors)): ?>
            <div class="alert alert-danger">
                <strong><?php echo __("Please correct the following errors:"); ?></strong>
                <ul>
                    <?php foreach ($form_errors as $field => $error): ?>
                        <?php if ($field !== 'general'): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
            <div class="card mb-4">
                <div class="card-header"><?php echo __("Edit Application"); ?></div>
                <div class="card-body">
                    <div class="row">
                        <div class="form-group col-md-12 mb-4">
                            <label for="FullName"><?php echo __("Full Name"); ?>:</label>
                            <input type="text" name="FullName" id="FullName" class="form-control <?php echo isset($form_errors['FullName']) ? 'error-border' : ''; ?>" value="<?php echo htmlspecialchars($FullName ?? ''); ?>" required>
                            <?php if (isset($form_errors['FullName'])): ?>
                                <div class="error"><?php echo htmlspecialchars($form_errors['FullName']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-row align-items-center mb-4">
                            <div class="form-group col-md-6">
                                <label for="passport_no"><?php echo __("Passport Number"); ?>:</label>
                                <input type="text" name="passport_no" id="passport_no" class="form-control <?php echo isset($form_errors['passport_no']) ? 'error-border' : ''; ?>" value="<?php echo htmlspecialchars($passport_no ?? ''); ?>" required pattern="[A-Za-z0-9]{6,9}" title="<?php echo __("Passport number must be 6 to 9 letters or digits"); ?>">
                                <?php if (isset($form_errors['passport_no'])): ?>
                                    <div class="error"><?php echo htmlspecialchars($form_errors['passport_no']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="Unit"><?php echo __("Faculty/Department"); ?>:</label>
                                <input type="text" name="Unit" id="Unit" class="form-control" value="<?php echo htmlspecialchars($faculty_name ?? ''); ?>" readonly>
                            </div>
                        </div>
                        <p><?php echo __("Permissions"); ?></p>
                        <div class="form-row align-items-center mb-4">
                            <div class="form-group col-md-12">
                                <div class="row">
                                    <div class="col">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="input" id="radio1" value="Annual-Leave" <?php if ($input == 'Annual-Leave') echo 'checked'; ?> required>
                                            <label class="form-check-label" for="radio1"><?php echo __("Annual-Leave"); ?></label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="input" id="radio2" value="excuse-leave" <?php if ($input == 'excuse-leave') echo 'checked'; ?> required>
                                            <label class="form-check-label" for="radio2"><?php echo __("Excuse-leave"); ?></label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="input" id="radio3" value="unpaid-leave" <?php if ($input == 'unpaid-leave') echo 'checked'; ?> required>
                                            <label class="form-check-label" for="radio3"><?php echo __("Unpaid-leave"); ?></label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="input" id="radio4" value="sick-leave" <?php if ($input == 'sick-leave') echo 'checked'; ?> required>
                                            <label class="form-check-label" for="radio4"><?php echo __("Sick-leave"); ?></label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="input" id="radio5" value="other" <?php if ($input == 'other') echo 'checked'; ?> required>
                                            <label class="form-check-label" for="radio5"><?php echo __("Other"); ?></label>
                                        </div>
                                        <?php if (isset($form_errors['input'])): ?>
                                            <div class="error"><?php echo htmlspecialchars($form_errors['input']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col">
                                <div class="form-group">
                                    <label for="RequestTest"><?php echo __("Specify"); ?></label>
                                    <input type="text" class="form-control" id="RequestTest" name="RequestTest" placeholder="<?php echo __("more details or other"); ?>" value="<?php echo htmlspecialchars($RequestTest ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="PermitStartDate"><?php echo __("Permit start date"); ?></label>
                                <input type="date" class="form-control <?php echo isset($form_errors['PermitStartDate']) ? 'error-border' : ''; ?>" id="PermitStartDate" name="PermitStartDate" value="<?php echo htmlspecialchars($PermitStartDate ?? ''); ?>" onchange="updateDayoff()" required>
                                <?php if (isset($form_errors['PermitStartDate'])): ?>
                                    <div class="error"><?php echo htmlspecialchars($form_errors['PermitStartDate']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="LeaveExpiryDate"><?php echo __("Leave end date"); ?></label>
                                <input type="date" class="form-control <?php echo isset($form_errors['LeaveExpiryDate']) ? 'error-border' : ''; ?>" id="LeaveExpiryDate" name="LeaveExpiryDate" value="<?php echo htmlspecialchars($LeaveExpiryDate ?? ''); ?>" onchange="updateDayoff()" required>
                                <?php if (isset($form_errors['LeaveExpiryDate'])): ?>
                                    <div class="error"><?php echo htmlspecialchars($form_errors['LeaveExpiryDate']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="PersonToRepresent"><?php echo __("Person to represent"); ?></label>
                                <input type="text" class="form-control <?php echo isset($form_errors['PersonToRepresent']) ? 'error-border' : ''; ?>" id="PersonToRepresent" name="PersonToRepresent" value="<?php echo htmlspecialchars($PersonToRepresent ?? ''); ?>" required>
                                <?php if (isset($form_errors['PersonToRepresent'])): ?>
                                    <div class="error"><?php echo htmlspecialchars($form_errors['PersonToRepresent']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="Address"><?php echo __("Permission to address"); ?></label>
                                <input type="text" class="form-control <?php echo isset($form_errors['Address']) ? 'error-border' : ''; ?>" id="Address" name="Address" value="<?php echo htmlspecialchars($Address ?? ''); ?>" required>
                                <?php if (isset($form_errors['Address'])): ?>
                                    <div class="error"><?php echo htmlspecialchars($form_errors['Address']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label for="Phone"><?php echo __("Phone Number"); ?></label>
                                <input type="tel" name="Phone" id="Phone" class="form-control <?php echo isset($form_errors['Phone']) ? 'error-border' : ''; ?>" placeholder="099xxxxxxx" value="<?php echo htmlspecialchars($Phone ?? ''); ?>" required maxlength="15" pattern="[0-9]{10,15}" title="<?php echo __("Phone number must be between 10 and 15 digits"); ?>">
                                <?php if (isset($form_errors['Phone'])): ?>
                                    <div class="error"><?php echo htmlspecialchars($form_errors['Phone']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col">
                            <p><?php echo __("I have classes during my leave of absence"); ?></p>
                            <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="radioInput1" id="yes" value="yes" <?php echo (isset($form_data['radioInput1']) && $form_data['radioInput1'] === 'yes') ? 'checked' : ($ClassDuringLeave === 'yes' ? 'checked' : ''); ?> required>                                <label class="form-check-label" for="yes"><?php echo __("Yes"); ?></label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="radioInput1" id="no" value="no" <?php echo (isset($form_data['radioInput1']) && $form_data['radioInput1'] === 'no') ? 'checked' : ($ClassDuringLeave === 'no' ? 'checked' : ''); ?> required>
                                <label class="form-check-label" for="no"><?php echo __("No"); ?></label>
                            </div>
                            <?php if (isset($form_errors['radioInput1'])): ?>
                                <div class="error"><?php echo htmlspecialchars($form_errors['radioInput1']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="row">
                            <div class="form-group" id="makeUpDays" style="display: none;">
                                <p><?php echo __("I'll make it up in these days:"); ?></p>
                                <input type="date" class="form-control <?php echo isset($form_errors['MakeUpDays']) ? 'error-border' : ''; ?>" id="makeUpText" name="MakeUpDays" value="<?php echo htmlspecialchars($MakeUpDays ?? ''); ?>">
                                <?php if (isset($form_errors['MakeUpDays'])): ?>
                                    <div class="error"><?php echo htmlspecialchars($form_errors['MakeUpDays']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="form-group col-md-12 mt-4">
                            <label for="totalDays"><?php echo __("Total days"); ?></label>
                            <input type="text" id="totalDays" name="Dayoff" class="form-control <?php echo isset($form_errors['Dayoff']) ? 'error-border' : ''; ?>" value="<?php echo htmlspecialchars($Dayoff ?? ''); ?>" readonly>
                            <?php if (isset($form_errors['Dayoff'])): ?>
                                <div class="error"><?php echo htmlspecialchars($form_errors['Dayoff']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="row mb-5">
                            <div class="form-group col-md-6">
                                <label for="AdminApproval"><?php echo __("Admin Approval"); ?>:</label>
                                <input type="text" name="AdminApproval" id="AdminApproval" class="form-control" value="<?php echo htmlspecialchars($_SESSION['admin_name']); ?>" disabled>
                                <input type="hidden" name="AdminFaculty" value="<?php echo htmlspecialchars($faculty_id); ?>">
                                <input type="hidden" name="semester_id" value="<?php echo htmlspecialchars($currentSemesterId ?? ''); ?>">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="currentSemester"><?php echo __("Semesters"); ?></label>
                                <input type="text" class="form-control" id="currentSemester" name="currentSemester" value="<?php echo htmlspecialchars($currentSemester ?? $semester); ?>" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="button">
                        <button type="submit" class="SubmitButton1" name="submit"><?php echo __("Save"); ?></button>
                        <button type="button" class="SubmitButton1" id="btn"><?php echo __("Back"); ?></button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<script>
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

document.addEventListener("DOMContentLoaded", function() {
    const yesRadioButton = document.getElementById("yes");
    const noRadioButton = document.getElementById("no");
    const makeUpText = document.getElementById("makeUpText");
    const makeUpDays = document.getElementById("makeUpDays");

    function toggleMakeUpText() {
        if (yesRadioButton.checked) {
            makeUpText.style.display = "block";
            makeUpDays.style.display = "block";
        } else {
            makeUpText.style.display = "none";
            makeUpDays.style.display = "none";
            if (!yesRadioButton.checked && !noRadioButton.checked) {
                makeUpText.value = 'Null';
            }
        }
    }
    yesRadioButton.addEventListener("change", toggleMakeUpText);
    noRadioButton.addEventListener("change", toggleMakeUpText);
    toggleMakeUpText();
    
    const textBox = document.getElementById('RequestTest');
    textBox.disabled = false;
    
    const makeUpDateInput = document.getElementById('makeUpText');
    makeUpDateInput.addEventListener('change', validateMakeUpDate);

    const radioInputs = document.getElementsByName("input");
    radioInputs.forEach(function(radio) {
        radio.addEventListener("change", function() {
            updateDayoff();
        });
    });
});

function validateAnnualLeave() {
    const radioInputs = document.getElementsByName("input");
    const dayoffInput = document.getElementById("totalDays");
    const totalDays = parseInt(dayoffInput.value) || 0;
    
    let selectedOption = null;
    for (let i = 0; i < radioInputs.length; i++) {
        if (radioInputs[i].checked) {
            selectedOption = radioInputs[i].value;
            break;
        }
    }
    
    if (selectedOption === "Annual-Leave" && totalDays > 20) {
        showAnnualLeaveError("Maximum leave duration for Annual-Leave is 20 business days. Current: " + totalDays + " days.");
        return false;
    } else {
        clearAnnualLeaveError();
        return true;
    }
}

function showAnnualLeaveError(message) {
    clearAnnualLeaveError();
    
    const dayoffInput = document.getElementById("totalDays");
    const errorDiv = document.createElement('div');
    errorDiv.id = 'annualLeaveError';
    errorDiv.className = 'error';
    errorDiv.style.color = 'red';
    errorDiv.style.fontSize = '14px';
    errorDiv.style.marginTop = '5px';
    errorDiv.textContent = message;
    
    dayoffInput.style.borderColor = 'red';
    dayoffInput.parentNode.appendChild(errorDiv);
}

function clearAnnualLeaveError() {
    const errorDiv = document.getElementById('annualLeaveError');
    const dayoffInput = document.getElementById("totalDays");
    
    if (errorDiv) {
        errorDiv.remove();
    }
    dayoffInput.style.borderColor = '';
}

function validateMakeUpDate() {
    const makeUpDateInput = document.getElementById('makeUpText');
    const startDateInput = document.querySelector("input[name='PermitStartDate']");
    const endDateInput = document.querySelector("input[name='LeaveExpiryDate']");
    
    if (!makeUpDateInput.value) return;
    
    const makeUpDate = new Date(makeUpDateInput.value);
    const startDate = new Date(startDateInput.value);
    const endDate = new Date(endDateInput.value);
    const currentDate = new Date();
    
    makeUpDate.setHours(0, 0, 0, 0);
    startDate.setHours(0, 0, 0, 0);
    endDate.setHours(0, 0, 0, 0);
    currentDate.setHours(0, 0, 0, 0);
    
    let errorMessage = '';
    
    if (makeUpDate < currentDate) {
        errorMessage = 'Make-up date cannot be in the past.';
    } else if (startDateInput.value && endDateInput.value) {
        if (makeUpDate >= startDate && makeUpDate <= endDate) {
            errorMessage = 'Make-up date cannot be during the leave period.';
        }
    }
    
    if (errorMessage) {
        alert(errorMessage);
        makeUpDateInput.value = '';
        makeUpDateInput.style.borderColor = 'red';
        let errorDiv = document.getElementById('makeUpDateError');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.id = 'makeUpDateError';
            errorDiv.className = 'error';
            makeUpDateInput.parentNode.appendChild(errorDiv);
        }
        errorDiv.textContent = errorMessage;
    } else {
        makeUpDateInput.style.borderColor = '';
        const errorDiv = document.getElementById('makeUpDateError');
        if (errorDiv) {
            errorDiv.remove();
        }
    }
}

function updateDayoff() {
    var startDateInput = document.querySelector("input[name='PermitStartDate']");
    var endDateInput = document.querySelector("input[name='LeaveExpiryDate']");
    var dayoffInput = document.getElementById("totalDays");

    var startDate = new Date(startDateInput.value);
    var endDate = new Date(endDateInput.value);

    const publicHolidays = [
        '2025-01-01', '2025-04-23', '2025-05-01', '2025-05-19', '2025-07-20',
        '2025-08-01', '2025-08-30', '2025-10-29', '2025-11-15', '2025-12-25',
        '2026-01-01', '2026-04-23', '2026-05-01', '2026-05-19', '2026-07-20',
        '2026-08-01', '2026-08-30', '2026-10-29', '2026-11-15', '2026-12-25',
        '2027-01-01', '2027-04-23', '2027-05-01', '2027-05-19', '2027-07-20',
        '2027-08-01', '2027-08-30', '2027-10-29', '2027-11-15', '2027-12-25',
        '2028-01-01', '2028-04-23', '2028-05-01', '2028-05-19', '2028-07-20',
        '2028-08-01', '2028-08-30', '2028-10-29', '2028-11-15', '2028-12-25',
        '2029-01-01', '2029-04-23', '2029-05-01', '2029-05-19', '2029-07-20',
        '2029-08-01', '2029-08-30', '2029-10-29', '2029-11-15', '2029-12-25',
        '2030-01-01', '2030-04-23', '2030-05-01', '2030-05-19', '2030-07-20',
        '2030-08-01', '2030-08-30', '2030-10-29', '2030-11-15', '2030-12-25'
    ];

    if (startDateInput.value && endDateInput.value) {
        var totalDays = 0;
        var current = new Date(startDate);

        while (current <= endDate) {
            var day = current.getDay();
            const formattedDate = current.toISOString().split('T')[0];

            if (day !== 0 && day !== 6 && !publicHolidays.includes(formattedDate)) {
                totalDays++;
            }

            current.setDate(current.getDate() + 1);
        }

        dayoffInput.value = totalDays;
        validateAnnualLeave();
    } else {
        dayoffInput.value = "";
        clearAnnualLeaveError();
    }
    
    if (document.getElementById('makeUpText').value) {
        validateMakeUpDate();
    }
}

document.addEventListener("DOMContentLoaded", function() {
    var startDateInput = document.querySelector("input[name='PermitStartDate']");
    var endDateInput = document.querySelector("input[name='LeaveExpiryDate']");
    startDateInput.addEventListener("change", validateDates);
    endDateInput.addEventListener("change", validateDates);

    function validateDates() {
        var startDate = new Date(startDateInput.value);
        var endDate = new Date(endDateInput.value);
        var currentDate = new Date();
        
        currentDate.setHours(0, 0, 0, 0);
        startDate.setHours(0, 0, 0, 0);
        endDate.setHours(0, 0, 0, 0);
        
        var isValid = true;
        var errorMessage = '';
        
        clearDateErrors();
        
        if (startDateInput.value && startDate < currentDate) {
            isValid = false;
            errorMessage = "Start date cannot be in the past.";
            showDateError(startDateInput, errorMessage);
            startDateInput.value = '';
        }
        
        if (endDateInput.value && endDate < currentDate) {
            isValid = false;
            errorMessage = "End date cannot be in the past.";
            showDateError(endDateInput, errorMessage);
            endDateInput.value = '';
        }
        
        if (startDateInput.value && endDateInput.value && startDate > endDate) {
            isValid = false;
            errorMessage = "Start date cannot be after end date.";
            showDateError(endDateInput, errorMessage);
            endDateInput.value = '';
        }
        
        return isValid;
    }

    function showDateError(inputElement, message) {
        inputElement.style.borderColor = 'red';
        
        let errorId = inputElement.name + '_error';
        let errorDiv = document.getElementById(errorId);
        
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.id = errorId;
            errorDiv.className = 'error';
            errorDiv.style.color = 'red';
            errorDiv.style.fontSize = '14px';
            errorDiv.style.marginTop = '5px';
            inputElement.parentNode.appendChild(errorDiv);
        }
        
        errorDiv.textContent = message;
    }

    function clearDateErrors() {
        var startDateInput = document.querySelector("input[name='PermitStartDate']");
        var endDateInput = document.querySelector("input[name='LeaveExpiryDate']");
        
        startDateInput.style.borderColor = '';
        let startError = document.getElementById('PermitStartDate_error');
        if (startError) startError.remove();
        
        endDateInput.style.borderColor = '';
        let endError = document.getElementById('LeaveExpiryDate_error');
        if (endError) endError.remove();
    }

    const form = document.querySelector('form');
    form.addEventListener('submit', function(event) {
        const makeUpDateInput = document.getElementById('makeUpText');
        const yesRadio = document.getElementById('yes');
        const startDateInput = document.querySelector("input[name='PermitStartDate']");
        const endDateInput = document.querySelector("input[name='LeaveExpiryDate']");
        
        if (!validateDates()) {
            alert('Please correct the date errors before submitting.');
            event.preventDefault();
            return false;
        }
        
        if (!startDateInput.value || !endDateInput.value) {
            alert('Please select both start and end dates.');
            event.preventDefault();
            return false;
        }
        
        if (!validateAnnualLeave()) {
            alert('Cannot submit form: Annual Leave cannot exceed 20 business days.');
            event.preventDefault();
            return false;
        }
        
        if (yesRadio.checked && makeUpDateInput.style.display !== 'none') {
            if (!makeUpDateInput.value) {
                alert('Please enter a make-up date.');
                event.preventDefault();
                return false;
            }
            
            const errorDiv = document.getElementById('makeUpDateError');
            if (errorDiv) {
                alert('Please correct the make-up date error before submitting.');
                event.preventDefault();
                return false;
            }
        }
    });
});

document.getElementById("btn").addEventListener("click", function() {
    window.location.href = "../Dashboard/userDashboard.php";
});
</script>
</body>
</html>