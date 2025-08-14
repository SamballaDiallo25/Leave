<?php
session_start();
// CORRECT - Check for any valid role
if (!isset($_SESSION["Role"]) && !isset($_SESSION["AdminRole"])) {
    header("Location: ../index.php");
    exit();
}

require_once "../lang.php";

include(__DIR__ . "/../configuration/configuration.php");
include(__DIR__ . "/../navbar.php");

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET["id"])) {
    echo "<div class='alert alert-danger'>Invalid submission number.</div>";
    exit();
}

$id = $_GET["id"];

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
$response_message = '';

// Fetch the form data using the same method as edit.php
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
    $response_message = $row['response_message'] ?? '';
} else {
    echo "<div class='alert alert-danger'>Application not found.</div>";
    exit();
}
$stmt_form->close();

// Fetch faculty name - same method as edit.php
if ($faculty_id) {
    $sql = "SELECT faculty_name FROM faculties1 WHERE faculty_id = ?";
    $stmt_faculty = $conn->prepare($sql);
    $stmt_faculty->bind_param("i", $faculty_id);
    $stmt_faculty->execute();
    $result_faculty = $stmt_faculty->get_result();
    
    if ($result_faculty->num_rows > 0) {
        $faculty_row = $result_faculty->fetch_assoc();
        $faculty_name = $faculty_row['faculty_name'];
    }
    $stmt_faculty->close();
}

// Retrieve faculty head - same method as edit.php
$admin_name = "Not Assigned";
if ($faculty_id) {
    $sql_admin = "SELECT faculty_head FROM faculties1 WHERE faculty_id = ?";
    $stmt_admin = $conn->prepare($sql_admin);
    $stmt_admin->bind_param("i", $faculty_id);
    $stmt_admin->execute();
    $stmt_admin->bind_result($adminName);
    if ($stmt_admin->fetch()) {
        $admin_name = $adminName;
    }
    $stmt_admin->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] == 'tr' ? 'tr' : 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __("Application Review"); ?></title>
    <link rel="icon" href="../logo/logo1.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .SubmitButton1 {
            background-color: #141414;
            color: white;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            border-radius: 5px;
            width: 100px;
            margin-right: 10px;
        }
        
        .SubmitButton1:hover {
            background-color: #333333;
        }
        
        @media (max-width: 430px) {
            .button {
                display: flex;
                justify-content: center; 
                align-items: center; 
            }
        }
        
        h5 {
            text-align: center;
            padding-top: 10px;
        }
        
        .form-control:disabled, .form-control[readonly] {
            background-color: #f8f9fa;
            opacity: 1;
        }
        
        .card-header {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .review-info {
            background-color: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<section class="main-content">
    <div class="container mt-5">
        <div class="review-info">
            <h5><?php echo __("Application Review"); ?></h5>
            <p class="mb-0 text-center">
                <strong><?php echo __("Submission Number"); ?>:</strong> <?php echo htmlspecialchars($submission_number); ?> | 
                <strong><?php echo __("Submission Date"); ?>:</strong> <?php echo htmlspecialchars($submission_date); ?>
            </p>
        </div>
        
        <div class="card mb-4">
            <div class="card-header"><?php echo __("Application Details"); ?></div>
            <div class="card-body">
                <div class="row">
                    <div class="form-group col-md-12 mb-4">
                        <label for="FullName" class="mr-2"><strong><?php echo __('Full Name'); ?>:</strong></label>
                        <input type="text" name="FullName" class="form-control" value="<?php echo htmlspecialchars($FullName); ?>" readonly>
                    </div>
                </div>
                
                <div class="form-row align-items-center mb-4">
                    <div class="form-group col-md-6">
                        <label for="passport_no" class="mr-2"><strong><?php echo __('Passport Number'); ?>:</strong></label>
                        <input type="text" name="passport_no" class="form-control" value="<?php echo htmlspecialchars($passport_no); ?>" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="Unit" class="mr-2"><strong><?php echo __('Faculty/Department'); ?>:</strong></label>
                        <input type="text" name="Unit" class="form-control" value="<?php echo htmlspecialchars($faculty_name ? __($faculty_name) : __($Unit)); ?>" readonly>
                    </div>
                </div>
                
                <p><strong><?php echo __('Permissions'); ?>:</strong></p>
                <div class="form-row align-items-center mb-4">
                    <div class="form-group col-md-12">
                        <div class="row">
                            <div class="col">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="input" id="radio1" value="Annual-Leave" <?php if ($input == 'Annual-Leave') echo 'checked'; ?> disabled>
                                    <label class="form-check-label" for="radio1"><?php echo __('Annual-Leave'); ?></label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="input" id="radio2" value="excuse-leave" <?php if ($input == 'excuse-leave') echo 'checked'; ?> disabled>
                                    <label class="form-check-label" for="radio2"><?php echo __('Excuse-leave'); ?></label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="input" id="radio3" value="unpaid-leave" <?php if ($input == 'unpaid-leave') echo 'checked'; ?> disabled>
                                    <label class="form-check-label" for="radio3"><?php echo __('Unpaid-leave'); ?></label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="input" id="radio4" value="sick-leave" <?php if ($input== 'sick-leave') echo 'checked'; ?> disabled>
                                    <label class="form-check-label" for="radio4"><?php echo __('Sick-leave'); ?></label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="input" id="radio5" value="other" <?php if ($input == 'other') echo 'checked'; ?> disabled>
                                    <label class="form-check-label" for="radio5"><?php echo __('Other'); ?></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($input == 'other' && !empty($RequestTest)) : ?>
                <div class="row mt-3">
                    <div class="col">
                        <div class="form-group">
                            <label for="RequestTest" class="form-label"><strong><?php echo __('Specify'); ?>:</strong></label>
                            <input type="text" class="form-control" id="RequestTest" name="RequestTest" value="<?php echo htmlspecialchars($RequestTest); ?>" readonly>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="form-group col-md-6">
                        <div class="form-group">
                            <label for="PermitStartDate" class="form-label"><strong><?php echo __('Permit start date'); ?>:</strong></label>
                            <input type="date" class="form-control" id="PermitStartDate" name="PermitStartDate" value="<?php echo htmlspecialchars($PermitStartDate); ?>" readonly>
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <div class="form-group">
                            <label for="LeaveExpiryDate" class="form-label"><strong><?php echo __('Leave end date'); ?>:</strong></label>
                            <input type="date" class="form-control" id="LeaveExpiryDate" name="LeaveExpiryDate" value="<?php echo htmlspecialchars($LeaveExpiryDate); ?>" readonly>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="form-group col-md-6">
                        <div class="form-group">
                            <label for="PersonToRepresent" class="form-label"><strong><?php echo __('Person to represent'); ?>:</strong></label>
                            <input type="text" class="form-control" id="PersonToRepresent" name="PersonToRepresent" value="<?php echo htmlspecialchars($PersonToRepresent); ?>" readonly>
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <div class="form-group">
                            <label for="Address" class="form-label"><strong><?php echo __('Permission to address'); ?>:</strong></label>
                            <input type="text" class="form-control" id="Address" name="Address" value="<?php echo htmlspecialchars($Address); ?>" readonly>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="form-group col-md-12">
                        <div class="form-group">
                            <label for="Phone" class="form-label"><strong><?php echo __('Phone Number'); ?>:</strong></label>
                            <input type="tel" class="form-control" id="Phone" name="Phone" value="<?php echo htmlspecialchars($Phone); ?>" readonly>
                        </div>
                    </div>
                </div>
                
                <div class="col">
                    <p><strong><?php echo __('I have classes during my leave of absence'); ?>:</strong></p>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="radioInput1" id="yes" value="yes" <?php if (trim($ClassDuringLeave) == 'yes') echo 'checked'; ?> disabled>
                        <label class="form-check-label" for="yes"><?php echo __('Yes'); ?></label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="radioInput1" id="no" value="no" <?php if (trim($ClassDuringLeave) == 'no') echo 'checked'; ?> disabled>
                        <label class="form-check-label" for="no"><?php echo __('No'); ?></label>
                    </div>
                </div>
                
                <?php if ($ClassDuringLeave == 'yes' && !empty($MakeUpDays)) : ?>
                <div class="row">
                    <div class="form-group mt-3">
                        <p><strong><?php echo __("I'll make it up in these days:"); ?></strong></p>
                        <input type="text" class="form-control" id="MakeUpDays" name="MakeUpDays" value="<?php echo htmlspecialchars($MakeUpDays); ?>" readonly>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="form-group col-md-12 mt-4">
                    <label class="form-label"><strong><?php echo __('Total days'); ?>:</strong></label>
                    <input type="text" id="Dayoff" name="Dayoff" class="form-control" value="<?php echo htmlspecialchars($Dayoff); ?>" readonly>
                </div>
                
                <div class="row mb-5">
                    <div class="form-group col-md-6">
                        <label for="AdminApproval" class="mr-2"><strong><?php echo __("Admin Approval"); ?>:</strong></label>
                        <input type="text" name="AdminApproval" class="form-control" value="<?php echo htmlspecialchars($admin_name); ?>" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="semester"><strong><?php echo __('Semester'); ?>:</strong></label>
                        <input type="text" class="form-control" id="semester" name="semester" value="<?php echo htmlspecialchars($semester); ?>" readonly>
                    </div>
                </div>
                
                <?php if (!empty($response_message)) : ?>
                <div class="row mb-4">
                    <div class="form-group col-md-12">
                        <label class="form-label"><strong><?php echo __('Admin Response'); ?>:</strong></label>
                        <textarea class="form-control" rows="3" readonly><?php echo htmlspecialchars($response_message); ?></textarea>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="button">
                    <button type="button" class="SubmitButton1" id="backBtn"><?php echo __("Back"); ?></button>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.getElementById('backBtn').addEventListener('click', function() {
        // Redirect to all_notifications.php
        window.location.href = '/../notifications/all_notifications.php';
    });
</script>
</body>
</html>