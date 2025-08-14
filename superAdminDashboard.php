<?php
session_start();

// --- Session timeout logic for regular users ---
$session_timeout = 1800; // 30 minutes

if (isset($_SESSION['last_activity'])) {
    $inactive_time = time() - $_SESSION['last_activity'];
    if ($inactive_time >= $session_timeout) {
        session_unset();
        session_destroy();
        header("Location: ../index.php?timeout=1"); // Optional: display timeout message
        exit();
    }
}
$_SESSION['last_activity'] = time();

if (!isset($_SESSION["SuperAdminName"])) {
    header("Location: ../index.php");
    exit(); 
}

// IMPORTANT: Include lang.php BEFORE any validation or processing
require_once "../lang.php";
require_once "../navbar.php";
include "../configuration/configuration.php";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    $Semester_name = $_POST["semesterName"];
    $Start_date = $_POST["start_date"];
    $End_date = $_POST["end_date"];
    
    $hasError = false;
    $errorMessages = [];

    // Validation 1: Ensure End Date > Start Date
    if (strtotime($End_date) <= strtotime($Start_date)) {          
        $errorMessages[] = __("End date must be after start date.");
        $hasError = true;
    }
    
    // Validation 2: Ensure dates fall within reasonable range
    $startYear = date('Y', strtotime($Start_date));
    $endYear = date('Y', strtotime($End_date));
    $currentYear = date('Y');

    // Validation 3: Ensure Start Date is not in the past
    if (strtotime($Start_date) < strtotime(date("Y-m-d"))) {
        $errorMessages[] = __("Start date cannot be in the past.");
        $hasError = true;
    }

    
    if ($startYear < ($currentYear - 1) || $startYear > ($currentYear + 5)) {
        $errorMessages[] = __("Start date year must be within reasonable range") . " (" . ($currentYear - 1) . " " . __("to") . " " . ($currentYear + 5) . ").";
        $hasError = true;
    }
    
    if ($endYear > ($currentYear + 6)) {
        $errorMessages[] = __("End date year cannot be more than 6 years in the future.");
        $hasError = true;
    }
    
    // Validation 4: Check for semester overlap
    if (!$hasError) {
        $overlapStmt = $conn->prepare(
            "SELECT Semester_name, Start_date, End_date FROM semesters 
             WHERE (Start_date <= ? AND End_date >= ?) 
             OR (Start_date <= ? AND End_date >= ?) 
             OR (Start_date >= ? AND End_date <= ?)"
        );
        $overlapStmt->bind_param("ssssss", $End_date, $Start_date, $Start_date, $Start_date, $Start_date, $End_date);
        $overlapStmt->execute();
        $overlapResult = $overlapStmt->get_result();
        
        if ($overlapResult->num_rows > 0) {
            $overlappingSemesters = [];
            while ($overlapRow = $overlapResult->fetch_assoc()) {
                $overlapStartYear = date('Y', strtotime($overlapRow['Start_date']));
                $overlapNextYear = $overlapStartYear + 1;
                $overlapSemesterDisplay = $overlapRow['Semester_name'] . "(" . $overlapStartYear . "/" . $overlapNextYear . ")";
                $overlappingSemesters[] = $overlapSemesterDisplay . " (" . $overlapRow['Start_date'] . " " . __("to") . " " . $overlapRow['End_date'] . ")";
            }
            
            // Create a single error message for all overlapping semesters
            if (count($overlappingSemesters) == 1) {
                $errorMessages[] = __("Warning: Date range overlaps with existing semester") . " " . $overlappingSemesters[0] . ".";
            } else {
                $errorMessages[] = __("Warning: Date range overlaps with existing semesters") . ": " . implode(", ", $overlappingSemesters) . ".";
            }
            $hasError = true;
        }
        $overlapStmt->close();
    }
    
    // Validation 5: Check for duplicate semester in the same academic year
    if (!$hasError) {
        // Clean semester name (remove any existing year format)
        $cleanSemesterName = preg_replace('/\s*\(\d{4}\/\d{4}\)\s*$/', '', $Semester_name);
        
        $checkStmt = $conn->prepare(
            "SELECT COUNT(*) as count, Semester_name FROM semesters 
             WHERE (Semester_name = ? OR Semester_name LIKE ?) 
             AND YEAR(Start_date) = ?"
        );
        $likePattern = $cleanSemesterName . "(%";
        $checkStmt->bind_param("ssi", $cleanSemesterName, $likePattern, $startYear);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $row = $result->fetch_assoc();
        $checkStmt->close();
        
        if ($row['count'] > 0) {
            $nextYear = $startYear + 1;
            $errorMessages[] = $cleanSemesterName . "(" . $startYear . "/" . $nextYear . ") " . __("already exists. You cannot add the same semester for the same academic year.");
            $hasError = true;
        }
    }
    
    // Display errors or insert record
    if ($hasError) {
        $errorMessage = "<div class='alert alert-danger'>";
        foreach ($errorMessages as $error) {
            $errorMessage .= "<p class='mb-1'>" . $error . "</p>";
        }
        $errorMessage .= "</div>";
    } else {
        // Clean the semester name before inserting (remove any year format if present)
        $cleanSemesterName = preg_replace('/\s*\(\d{4}\/\d{4}\)\s*$/', '', $Semester_name);
        
        $stmt = $conn->prepare(
            "INSERT INTO semesters (Semester_name, Start_date, End_date) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("sss", $cleanSemesterName, $Start_date, $End_date);

        if ($stmt->execute()) {
            $successMessage = "<div class='alert alert-success'>" . __("Semester added successfully.") . "</div>";
            // Clear URL parameters to prevent showing old messages
            echo "<script>
                if (window.history.replaceState) {
                    window.history.replaceState(null, null, window.location.pathname);
                }
            </script>";
        } else {
            $errorMessage = "<div class='alert alert-danger'>" . __("Error") . ": " . $stmt->error . "</div>";
        }
  
        $stmt->close();
    }
}

$semestersResult = $conn->query("SELECT * FROM semesters ORDER BY Semester_name");

if (!$semestersResult) {
    die("Error executing SQL statement: " . $conn->error);
}

$semesters = [];
while ($row = $semestersResult->fetch_assoc()) {
    // Clean semester name (remove any existing year format) before adding our format
    $cleanSemesterName = preg_replace('/\s*\(\d{4}\/\d{4}\)\s*$/', '', $row['Semester_name']);
    
    // Format semester name with academic year
    $startYear = date('Y', strtotime($row['Start_date']));
    $nextYear = $startYear + 1;
    $academicYear = $startYear . '/' . $nextYear;
    $row['formatted_semester'] = $cleanSemesterName . '(' . $academicYear . ')';
    
    $semesters[] = $row;
}

$semestersResult->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
      #addSemesterBtn {
        background-color: #141414;
        padding: 5px 10px;
        border: none;
        cursor: pointer;
        font-size: 14px;
        border-radius: 5%;
      }
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
            /* visibility:hidden; */
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
  .form-select {
    width: 90px !important;
  }
    </style>
  </head>
  <body>
  <section class="main-content">
    <div class="container mt-5">
        <div class="card mt-4" style="background-color: #f8f9fa; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); border-radius: 10px;">
            <div class="card-body">
                <h5><?php echo __("Add Semester");?></h5>
                
                <?php 
                // Display messages within the form card
                if (isset($errorMessage)) {
                    echo $errorMessage;
                }
                if (isset($successMessage)) {
                    echo $successMessage;
                }
                // Add success message for deletion (only if no other success message is shown)
                if (isset($_GET['deleted']) && $_GET['deleted'] === 'success' && !isset($successMessage)) {
                    echo "<div class='alert alert-success'>" . __("Semester deleted successfully.") . "</div>";
                }
                ?>
                
                <form method="post" action="" novalidate>

                   <div class="form-group">
    <label for="semesterName"><?php echo __("Semester");?>:</label>
   <select class="form-control" id="semesterName" name="semesterName" required>
    <option value="" disabled <?php echo (!isset($Semester_name) || $Semester_name == '') ? 'selected' : ''; ?>><?php echo __("Select Semester"); ?></option>
    <option value="<?php echo __("Fall"); ?>" <?php echo (isset($Semester_name) && $Semester_name == __("Fall")) ? 'selected' : ''; ?>><?php echo __("Fall"); ?></option>
    <option value="<?php echo __("Spring"); ?>" <?php echo (isset($Semester_name) && $Semester_name == __("Spring")) ? 'selected' : ''; ?>><?php echo __("Spring"); ?></option>
    <option value="<?php echo __("Summer"); ?>" <?php echo (isset($Semester_name) && $Semester_name == __("Summer")) ? 'selected' : ''; ?>><?php echo __("Summer"); ?></option>
</select>

</div>

                    <div class="form-group">
                        <label for="start_date"><?php echo __("Start Date");?>:</label>
<input type="date" class="form-control" id="start_date" name="start_date"
       min="<?php echo date('Y-m-d'); ?>"
       value="<?php echo isset($Start_date) ? htmlspecialchars($Start_date) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="end_date"><?php echo __("End Date");?>:</label>
<input type="date" class="form-control" id="end_date" name="end_date"
       min="<?php echo date('Y-m-d'); ?>"
       value="<?php echo isset($End_date) ? htmlspecialchars($End_date) : ''; ?>" required>
                    </div>
                    <button type="submit" class="btn btn-dark" name="submit"><?php echo __("Add Semester");?></button>
                </form>
            </div>
        </div>
        <div class="card mt-4 mb-4" style="background-color: #f8f9fa; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); border-radius: 10px;">
        <div class="card-body">
         <div class="row">
           <div class="col-lg-12">
             <h5 class="card-title"> <?php echo __("Status Table");?> </h5>
             <div class="container">
               <table id="example" class="table table-striped nowrap" style="width:100%;">
                 <thead>
                        <tr>
                            <th><?php echo __("Semester");?></th>
                            <th><?php echo __("Start Date");?></th>
                            <th><?php echo __("End Date");?></th>
                            <th><?php echo __("Action");?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($semesters as $semester): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(__($semester['formatted_semester'])); ?></td>
                            <td><?php echo htmlspecialchars($semester['Start_date']); ?></td>
                            <td><?php echo htmlspecialchars($semester['End_date']); ?></td>
                            <td>
                            <a href="../Form_1/delete.php?table=semesters&id=<?php echo $semester["id"]; ?>" 
                               class="btn btn-dark"
                               onclick="return confirm('<?php echo __("Are you sure you want to delete this semester"); ?>: <?php echo addslashes($semester['formatted_semester']); ?>?');">
                              <i class="fa-solid fa-trash"></i>
                          </a>
                         </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        </div>
    </div>
    </div>
</div>
</section>
 <script>var phpLang = '<?php echo lang(); ?>';</script>
 
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.1/dist/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
  <script src="../DataTable/datatable.js"></script>

  <script>
    $(document).ready(function() {
        var today = new Date().toISOString().split('T')[0];
        $('#start_date').attr('min', today);
        $('#end_date').attr('min', today);

        $('#start_date').on('change', function() {
            var startDate = $(this).val();
            $('#end_date').attr('min', startDate);
        });

        // Auto-suggest dates based on semester selection
        $('#semesterName').on('change', function() {
            var semester = $(this).val();
            var currentYear = new Date().getFullYear();
            var currentMonth = new Date().getMonth() + 1;
            
            // Use translated values for comparison
            var fallText = "<?php echo __('Fall'); ?>";
            var springText = "<?php echo __('Spring'); ?>";
            var summerText = "<?php echo __('Summer'); ?>";
            
            if (semester === fallText) {
                var year = currentMonth >= 8 ? currentYear : currentYear;
                var suggestedStartDate = year + '-09-01';
                $('#start_date').val(suggestedStartDate);
                var suggestedEndDate = year + '-12-31';
                $('#end_date').val(suggestedEndDate);
                $('#end_date').attr('min', suggestedStartDate);
                
            } else if (semester === springText) {
                var year = currentMonth >= 1 && currentMonth <= 5 ? currentYear : currentYear + 1;
                var suggestedStartDate = year + '-01-15';
                $('#start_date').val(suggestedStartDate);
                var suggestedEndDate = year + '-05-31';
                $('#end_date').val(suggestedEndDate);
                $('#end_date').attr('min', suggestedStartDate);
                
            } else if (semester === summerText) {
                var year = currentMonth >= 6 && currentMonth <= 8 ? currentYear : currentYear + 1;
                var suggestedStartDate = year + '-06-01';
                $('#start_date').val(suggestedStartDate);
                var suggestedEndDate = year + '-08-31';
                $('#end_date').val(suggestedEndDate);
                $('#end_date').attr('min', suggestedStartDate);
            }
            
            var startDate = $('#start_date').val();
            if (startDate) {
                $('#end_date').attr('min', startDate);
            }
        });

        // Auto-hide success message after 5 seconds
        setTimeout(function() {
            $('.alert-success').fadeOut('slow');
        }, 5000);
    });
</script>
  </body>
</html>