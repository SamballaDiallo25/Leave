<?php
ob_start();
session_start();
if (!isset($_SESSION["Admin_name"])) {
    header("Location: ../index.php");
    exit();
}
require_once "../lang.php";
require_once "../navbar.php";
include "../configuration/configuration.php";
$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT Role, faculty_id FROM users1 WHERE user_name = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $_SESSION["Admin_name"]);
$stmt->execute();

$stmt->bind_result($role, $faculty_id);

if ($stmt->fetch()) {
    $_SESSION["AdminRole"] = $role;
    $_SESSION["Admin_facultyID"] = $faculty_id;
} else {
    echo "No user found with the specified user name.";
}

$stmt->close();

if ($_SESSION["AdminRole"] == "HOF") {
    $sql = "SELECT * FROM form1 WHERE faculty_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION["Admin_facultyID"]);
    $stmt->execute();
    $stmt->bind_result(
        $submission_number,
        $user_id,
        $FullName,
        $PassportNo,
        $Unit,
        $input,
        $PermitStartDate,
        $LeaveExpiryDate,
        $PersonToRepresent,
        $Address,
        $Phone,
        $faculty_id,
        $submission_date,
        $ClassDuringLeave,
        $MakeUpDays,
        $Dayoff,
        $Department,
        $HumanResource,
        $Rectorate,
        $Comment,
        $RequestTest,
        $semester,
        $semester_id,
        $response_message
    );

    $results = [];
    while ($stmt->fetch()) {
        $results[] = [
            "submission_number" => $submission_number,
            "user_id" => $user_id,
            "FullName" => $FullName,
            "PassportNo" => $PassportNo,
            "Unit" => $Unit,
            "input" => $input,
            "PermitStartDate" => $PermitStartDate,
            "LeaveExpiryDate" => $LeaveExpiryDate,
            "PersonToRepresent" => $PersonToRepresent,
            "Address" => $Address,
            "Phone" => $Phone,
            "faculty_id" => $faculty_id,
            "submission_date" => $submission_date,
            "ClassDuringLeave" => $ClassDuringLeave,
            "MakeUpDays" => $MakeUpDays,
            "Dayoff" => $Dayoff,
            "Department" => $Department,
            "HumanResource" => $HumanResource,
            "Rectorate" => $Rectorate,
            "Comment" => $Comment,
            "RequestTest" => $RequestTest,
            "semester" => $semester,
        ];
    }

    $stmt->close();
} elseif (
    $_SESSION["AdminRole"] == "HumanResource" ||
    $_SESSION["AdminRole"] == "Rectorate"
) {
    $sql = "SELECT * FROM form1 WHERE Department = 'Approved'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $stmt->bind_result(
        $submission_number,
        $user_id,
        $FullName,
        $PassportNo,
        $Unit,
        $input,
        $PermitStartDate,
        $LeaveExpiryDate,
        $PersonToRepresent,
        $Address,
        $Phone,
        $faculty_id,
        $submission_date,
        $ClassDuringLeave,
        $MakeUpDays,
        $Dayoff,
        $Department,
        $HumanResource,
        $Rectorate,
        $Comment,
        $RequestTest,
        $semester
    );

    $results = [];
    while ($stmt->fetch()) {
        $results[] = [
            "submission_number" => $submission_number,
            "user_id" => $user_id,
            "FullName" => $FullName,
            "PassportNo" => $PassportNo,
            "Unit" => $Unit,
            "input" => $input,
            "PermitStartDate" => $PermitStartDate,
            "LeaveExpiryDate" => $LeaveExpiryDate,
            "PersonToRepresent" => $PersonToRepresent,
            "Address" => $Address,
            "Phone" => $Phone,
            "faculty_id" => $faculty_id,
            "submission_date" => $submission_date,
            "ClassDuringLeave" => $ClassDuringLeave,
            "MakeUpDays" => $MakeUpDays,
            "Dayoff" => $Dayoff,
            "Department" => $Department,
            "HumanResource" => $HumanResource,
            "Rectorate" => $Rectorate,
            "Comment" => $Comment,
            "RequestTest" => $RequestTest,
            "semester" => $semester,
        ];
    }

    $stmt->close();
} else {
    $sql = "SELECT * FROM form1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $stmt->bind_result(
        $submission_number,
        $user_id,
        $FullName,
        $PassportNo,
        $Unit,
        $input,
        $PermitStartDate,
        $LeaveExpiryDate,
        $PersonToRepresent,
        $Address,
        $Phone,
        $faculty_id,
        $submission_date,
        $ClassDuringLeave,
        $MakeUpDays,
        $Dayoff,
        $Department,
        $HumanResource,
        $Rectorate,
        $Comment,
        $RequestTest,
        $semester
    );

    $results = [];
    while ($stmt->fetch()) {
        $results[] = [
            "submission_number" => $submission_number,
            "user_id" => $user_id,
            "FullName" => $FullName,
            "PassportNo" => $PassportNo,
            "Unit" => $Unit,
            "input" => $input,
            "PermitStartDate" => $PermitStartDate,
            "LeaveExpiryDate" => $LeaveExpiryDate,
            "PersonToRepresent" => $PersonToRepresent,
            "Address" => $Address,
            "Phone" => $Phone,
            "faculty_id" => $faculty_id,
            "submission_date" => $submission_date,
            "ClassDuringLeave" => $ClassDuringLeave,
            "MakeUpDays" => $MakeUpDays,
            "Dayoff" => $Dayoff,
            "Department" => $Department,
            "HumanResource" => $HumanResource,
            "Rectorate" => $Rectorate,
            "Comment" => $Comment,
            "RequestTest" => $RequestTest,
            "semester" => $semester,
        ];
    }

    $stmt->close();

    $sql = "SELECT Semester_name FROM semesters";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $semesters = [];

        while ($row = $result->fetch_assoc()) {
            $semesters[] = $row["Semester_name"];
        }
    } else {
        echo "No semester options found.";
    }
}

if (isset($_SESSION["submission_id"])) {
    $id = $_SESSION["submission_id"];
}

if (isset($_POST["Update"])) {
    $SelectedOption = $_POST["Status"];
    $Comment = isset($_POST["Comment"]) ? $_POST["Comment"] : "";

    if ($_SESSION["AdminRole"] == "HOF") {
        $updateQuery =
            "UPDATE form1 SET Department = ?, Comment = ? WHERE submission_number = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("ssi", $SelectedOption, $Comment, $id);
    } elseif ($_SESSION["AdminRole"] == "Rectorate") {
        $updateQuery =
            "UPDATE form1 SET Rectorate = ? WHERE submission_number = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("si", $SelectedOption, $id);
    } elseif ($_SESSION["AdminRole"] == "HumanResource") {
        $updateQuery =
            "UPDATE form1 SET HumanResource = ? WHERE submission_number = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("si", $SelectedOption, $id);
    }

    $updateResult = $updateStmt->execute();

    if ($updateResult) {
        header("Location: ../Status/adminStatus.php");
    } else {
        // echo "Update Error: " . $conn->error;
    }

    $updateStmt->close();
    exit();
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status</title>

<style>
  @media (min-width: 1203px) {
   .main-content {
      margin-left: 300px;
      width: calc(100% - 300px);
      margin-top:50px;
    }
  }
  section{
    padding-top:20px;
  }
  h5{
    text-align:center;
    padding-top:10px;
    padding-bottom:10px;
  }
  #example {
            visibility: hidden;
        }
        /* Optional: to prevent layout shifting */
        .dataTables_wrapper {
            min-height: 300px; /* Set this to a reasonable min height */
        }
        .card-title{
          text-align:center;
        }
        .button-spacing {
    margin-right: 10px;
}
  .hover-link:hover {
    color: red;
  }
  
</style>
   <link rel="icon" href="../logo/logo1.png" type="image/png">
</head>
<body>
<section class="main-content">
  <div class="container mt-5">
    <div class="row">
      <div class="col-lg-12">
        <div class="card mb-4" style="background-color: #f8f9fa; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); border-radius: 10px;">
          <div class="card-body">
            <h5 class="card-title"> <?php echo __("Status Table")?> </h5>
            <div class="container">
              <table id="example" class="table table-striped nowrap" style="width:100%; display:none">
                <thead>
                  <tr>
                    <th> <?php echo __("AppNo");?> </th>
                    <th> <?php echo __("User Name");?> </th>
                    <th> <?php echo __("Department");?> </th>
                    <th> <?php echo __("Human Resources")?> </th>
                    <th> <?php echo __("Rectorate");?> </th>
                    <th> <?php echo __("Semester");?> </th>
                    <th> <?php echo __("Start Date");?> </th>
                    <th> <?php echo __("End Date");?> </th>
                    <th> <?php echo __("Permission Type")?> </th>
                    <th> <?php echo __("Days off")?> </th>
                    <th> <?php echo __("Comment")?> </th>
                  </tr>
                </thead>
                <tbody> 
                <?php
                if (!empty($results)) {
                    foreach ($results as $row) {
                        echo "<tr>";
                        echo '<td><a href="../Form_1/adminForm.php?id=' . htmlspecialchars($row["submission_number"] ?? '') . '" class="hover-link">' . htmlspecialchars($row["submission_number"] ?? '') . '</a></td>';
                        echo "<td>" . htmlspecialchars($row["FullName"] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars($row["Department"] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars($row["HumanResource"] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars($row["Rectorate"] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars($row["semester"] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars($row["PermitStartDate"] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars($row["LeaveExpiryDate"] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars($row["input"] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars($row["Dayoff"] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars($row["Comment"] ?? '') . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr>";
                    for ($i = 0; $i < 11; $i++) {
                        echo "<td></td>";
                    }
                    echo "</tr>";
                }

                $conn->close();
                ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.1/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="../DataTable/datatable.js"></script>
</body>
</html>