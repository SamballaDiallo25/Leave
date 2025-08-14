<?php
session_start();
if (!isset($_SESSION["Admin_name"])) {
    header("Location:../index.php");
    exit();
}
require_once "../lang.php";
require_once "../navbar.php";
include "../configuration/configuration.php";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get form data
    $SelectedOption = $_POST["Status"];
    $Comment = $_POST["Comment"];
    $submission_number = $_GET["id"];

    if (isset($_POST["Update"])) {
        if ($_SESSION["AdminRole"] == "HOF") {
            $updateQuery =
                "UPDATE form1 SET Department = ?, Comment = ? WHERE submission_number = ?";
        } elseif ($_SESSION["AdminRole"] == "Rectorate") {
            $updateQuery =
                "UPDATE form1 SET Rectorate = ?, Comment = IF(Comment != '', CONCAT(Comment, '\n', ?), ?) WHERE submission_number = ?";
        } elseif ($_SESSION["AdminRole"] == "HumanResource") {
            $updateQuery =
                "UPDATE form1 SET HumanResource = ?, Comment = IF(Comment != '', CONCAT(Comment, '\n', ?), ?) WHERE submission_number = ?";
        }

        $updateStmt = $conn->prepare($updateQuery);

        if ($_SESSION["AdminRole"] == "HOF") {
            $updateStmt->bind_param(
                "ssi",
                $SelectedOption,
                $Comment,
                $submission_number
            );
        } elseif (
            $_SESSION["AdminRole"] == "Rectorate" ||
            $_SESSION["AdminRole"] == "HumanResource"
        ) {
            $updateStmt->bind_param(
                "sssi",
                $SelectedOption,
                $Comment,
                $Comment,
                $submission_number
            );
        }

        $updateResult = $updateStmt->execute();

        if ($updateResult) {
        } else {
            // echo "Update Error: " . $conn->error;
        }

        $updateStmt->close();
    }
} else {
    if (isset($_GET["id"])) {
        $id = $_GET["id"];
        $_SESSION["submission_id"] = $id;

        $sql = "SELECT * FROM form1 WHERE submission_number = $id";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
        } else {
            echo "No data found.";
        }
    } else {
        echo "Invalid submission number.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Form</title>
    
    <style>
   .SubmitButton1 {
        background-color: #141414;
        color: white;
        padding: 10px 20px;
        border: none;
        cursor: pointer;
        font-size: 16px;
        border-radius: 5%;
        width: 100px;
        float: right;
        margin-right: 10px;
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
        padding
      }
    </style>
    <link rel="icon" href="../logo/logo1.png" type="image/png">
  </head>
  <body>
  <section class="main-content">
   <div class="container mt-5">
     <form action="../Status/adminStatus.php" method="post" enctype="">
       <div class="card mb-4 mt-4">
         <div class="card-header"><?php echo __("Application");?> </div>
         <div class="card-body">
           <div class="row">
             <div class="form-row align-items-center mb-4 mt-4">
               <div class="form-group col-md-12">
                 <label for="name" class="mr-2"> <?php echo __("Full Name");?>: </label>
                 <input type="text" name="fullName" class="form-control" value="<?php echo $row['FullName']; ?>" data-editable disabled>
               </div>
             </div>
             <div class="form-row align-items-center mb-4">
               <div class="form-group col-md-6">
                 <label for="name" class="mr-2"> <?php echo __("Passport Number")?>: </label>
                 <input type="text" name="PassportNo" class="form-control" value="<?php echo $row['PassportNo']; ?>" data-editable disabled>
               </div>
               <div class="form-group col-md-6">
                 <label for="name" class="mr-2"> <?php echo __("Faculty/Department");?> </label>
                 <input type="text" class="form-control" name="Unit" value="<?php echo $row['Unit']; ?>" data-editable disabled>
               </div>
             </div>
             <p> <?php echo __('Permissions')?> </p>
             <div class="form-row align-items-center mb-4">
               <div class="form-group col-md-12">
                 <div class="row">
                   <div class="col">
                     <div class="form-check form-check-inline">
                       <input class="form-check-input" type="radio" name="input" id="radio1" value="Annual-Leave" <?php if ($row['input'] == 'Annual-Leave') echo 'checked'; ?> disabled>
                       <label class="form-check-label" for="radio1"> <?php echo __('Annual-Leave')?> </label>
                     </div>
                     <div class="form-check form-check-inline">
                       <input class="form-check-input" type="radio" name="input" id="radio2" value="excuse-leave" <?php if ($row['input'] == 'excuse-leave') echo 'checked'; ?> disabled>
                       <label class="form-check-label" for="radio2"> <?php echo __('Excuse-leave')?> </label>
                     </div>
                     <div class="form-check form-check-inline">
                       <input class="form-check-input" type="radio" name="input" id="radio3" value="unpaid-leave" <?php if ($row['input'] == 'unpaid-leave') echo 'checked'; ?> disabled>
                       <label class="form-check-label" for="radio3"> <?php echo __('Unpaid-leave')?> </label>
                     </div>
                     <div class="form-check form-check-inline">
                       <input class="form-check-input" type="radio" name="input" id="radio4" value="sick-leave" <?php if ($row['input'] == 'sick-leave') echo 'checked'; ?> disabled>
                       <label class="form-check-label" for="radio4"> <?php echo __('Sick-leave')?> </label>
                     </div>
                     <div class="form-check form-check-inline">
                       <input class="form-check-input" type="radio" name="input" id="radio5" value="other" <?php if ($row['input'] == 'other') echo 'checked'; ?> disabled>
                       <label class="form-check-label" for="radio5"> <?php echo __('Other')?> </label>
                     </div>
                   </div>
                 </div>
               </div>
             </div>
             <div class="row mt-3">
               <div class="col">
                 <div class="form-group"> <?php if (isset($row['input']) && $row['input'] == 'other') : ?> <label for="textBox" class="form-label specify-label"> <?php echo __('Specify') ?> </label>
                   <input type="text" class="form-control specify-text <?php if ($row['input'] !== 'other') echo 'd-none'; ?>" id="textBox" placeholder="Specify" name="RequestTest" value="<?php echo $row['RequestTest']; ?>" disabled> <?php endif; ?>
                 </div>
               </div>
             </div>
             <div class="row">
               <div class="form-group col-md-6">
                 <div class="form-group">
                   <label for="inputPermitStartDate" class="form-label"> <?php echo __('Permit start date')?> </label>
                   <input type="date" class="form-control" id="inputPermitStartDate" name="PermitStartDate" onchange="updateDayoff()" <?php echo isset($row['PermitStartDate']) ? 'value="' . $row['PermitStartDate'] . '"' : ''; ?> data-editable disabled>
                 </div>
               </div>
               <div class="form-group col-md-6">
                 <div class="form-group">
                   <label for="inputLeaveEndDate" class="form-label"> <?php echo __('Leave end date')?> </label>
                   <input type="date" class="form-control" id="inputLeaveEndDate" name="LeaveExpiryDate" onchange="updateDayoff()" <?php echo isset($row['LeaveExpiryDate']) ? 'value="' . $row['LeaveExpiryDate'] . '"' : ''; ?>data-editable disabled>
                 </div>
               </div>
             </div>
             <div class="row">
               <div class="form-group col-md-6">
                 <div class="form-group">
                   <label for="input3" class="form-label"> <?php echo __('Person to represent')?> </label>
                   <input type="text" class="form-control" id="input3" name="PersonToRepresent" value="<?php echo $row['PersonToRepresent']; ?> " data-editable disabled>
                 </div>
               </div>
               <div class="form-group col-md-6">
                 <div class="form-group">
                   <label for="input4" class="form-label"> <?php echo __('Permission to address')?> </label>
                   <input type="text" class="form-control" id="input4" name="Address" value="<?php echo $row['Address']; ?> " data-editable disabled>
                 </div>
               </div>
             </div>
             <div class="row">
               <div class="form-group col-md-12">
                 <div class="form-group">
                   <label for="input5" class="form-label"> <?php echo __('Phone Number')?> </label>
                   <input type="tel" class="form-control" id="inputPhone" name="Phone" placeholder="099xxxxxxx" value="<?php echo ($row['Phone']); ?>" data-editable disabled>
                 </div>
               </div>
             </div>
             <div class="col"> <?php
                $classDuringLeave = trim($row['ClassDuringLeave']);?> <p> <?php echo __('I  have classes during my leave of absence')?> </p>
               <div class="form-check form-check-inline">
                 <input class="form-check-input" type="radio" name="radioInput1" id="yes" value="yes" <?php if ($classDuringLeave == 'yes') echo 'checked'; ?>disabled>
                 <label class="form-check-label" for="yes"> <?php echo __('Yes') ?> </label>
               </div>
               <div class="form-check form-check-inline">
                 <input class="form-check-input" type="radio" name="radioInput1" id="no" value="no" <?php if ($classDuringLeave == 'no') echo 'checked'; ?> disabled>
                 <label class="form-check-label" for="no"> <?php echo __('No') ?> </label>
               </div>
             </div>
             <div class="row">
               <div class="form-group mt-3" id="makeUpDays"> <?php if ($row['ClassDuringLeave'] == 'yes') : ?> <p> <?php echo __("I'll make it up in these days:")?> </p>
                 <input type="text" class="form-control" id="makeUpText" name="MakeUpDays" value="<?php echo $row['MakeUpDays']; ?>" data-editable disabled>
               </div> <?php endif; ?>
             </div>
             <div class="form-group col-md-12 mt-4 ">
               <label class="form-label"> <?php echo __('Total days')?> </label>
               <input type="text" id="totalDays" name="Dayoff" class="form-control" value="<?php echo $row['Dayoff']; ?> " data-editable disabled>
               </p>
             </div>
             <div class="row mb-5">
               <div class="form-group col-md-12">
                 <label for="dropdown"> <?php echo __('Approval')?> </label>
                 <select name="Status" class="form-control" id="status-select">
                   <option value="#"> <?php echo __("Select");?> -- </option>
                   <option value="Approved"> <?php echo __("Approved");?> </option>
                   <option value="Rejected"> <?php echo __("Rejected");?> </option>
                   <option value="Pending"> <?php echo __("Pending");?> </option>
                 </select>
               </div>
               <div class="form-group col-md-12">
                 <label for="Comment"> <?php echo __('Comment')?> </label>
                 <textarea name="Comment" class="form-control comment-textbox" rows="2" cols="20" id="comment-textbox" disabled></textarea>
               </div>
             </div>
           </div>
           <div class="button">
             <button type="submit" class="SubmitButton1" name="Update">Save</button>
             <button type="button" class="SubmitButton1" name="btn" id="btn">Back</button>
           </div>
         </div>
       </div>
   </div>
   </form>
 </section>
    <script>
  document.addEventListener("DOMContentLoaded", function() {
    const statusSelect = document.getElementById("status-select");
    const commentTextbox = document.getElementById("comment-textbox");

    statusSelect.addEventListener("change", function() {
        const selectedStatus = this.value;

        if (selectedStatus === "Rejected" && <?php echo $_SESSION['AdminRole'] === "HOF" ? "true" : "false"; ?>) {
            commentTextbox.removeAttribute("disabled");
        } else {
            commentTextbox.setAttribute("disabled", "disabled");
        }
    });
});
  document.getElementById("btn").addEventListener("click", function() {
      window.location.href = "../Status/adminStatus.php";
  }); 
</script>
  </body>
</html>