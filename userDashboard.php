<?php
session_start();


if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
    echo htmlspecialchars($_SESSION['success_message']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    echo '</div>';
    unset($_SESSION['success_message']);
}

// Display error message
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    echo htmlspecialchars($_SESSION['error_message']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    echo '</div>';
    unset($_SESSION['error_message']);
}

// --- User authentication check ---
if (!isset($_SESSION["user_name"]) && !isset($_SESSION["Admin_name"])) {
    header("Location: ../index.php");
    exit();
}
// ADD: Handle role switching scenarios
$is_switched_from_admin = isset($_SESSION['is_switched_user']) && $_SESSION['is_switched_user'] === true;
$original_admin_role = '';

if ($is_switched_from_admin) {
    // Get original admin role from session data
    $original_admin_role = isset($_SESSION['original_session_data']['AdminRole']) 
        ? $_SESSION['original_session_data']['AdminRole'] 
        : (isset($_SESSION['original_session_data']['SRole']) 
            ? $_SESSION['original_session_data']['SRole'] 
            : 'Admin');
} elseif (isset($_SESSION['AdminRole']) || isset($_SESSION['SRole'])) {
    // If admin is accessing user dashboard without switching, redirect them
    header("Location: ../Dashboard/adminDashboard.php");
    exit();
}
require_once "../lang.php";
require_once "../navbar.php";
include "../configuration/configuration.UserDashboard.php";
$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// MODIFY: Use proper user ID for queries
$user_id_for_query = $_SESSION["user_id"];

$totalPendingCount = 0;
$totalApprovedCount = 0;
$totalRejectedCount = 0;

$sqlCountForms = "
    SELECT
        COALESCE(SUM(CASE WHEN Department = 'Approved' AND HumanResource = 'Approved' AND Rectorate = 'Approved' THEN 1 ELSE 0 END), 0) AS approved_count,
        COALESCE(SUM(CASE WHEN Department = 'Rejected' OR HumanResource = 'Rejected' OR Rectorate = 'Rejected' THEN 1 ELSE 0 END), 0) AS rejected_count,
        COALESCE(SUM(CASE WHEN Department = 'Pending' OR HumanResource = 'Pending' OR Rectorate = 'Pending' THEN 1 ELSE 0 END), 0) AS pending_count
    FROM
        form1
    WHERE
        user_id = ?
";

$stmtCountForms = $conn->prepare($sqlCountForms);
$stmtCountForms->bind_param("i", $user_id_for_query);

if (!$stmtCountForms->execute()) {
    die("Error executing statement: " . $stmtCountForms->error);
}

$stmtCountForms->bind_result(
    $totalApprovedCount,
    $totalRejectedCount,
    $totalPendingCount
);

$stmtCountForms->fetch();
$stmtCountForms->close();

$sqlFetchForms = "SELECT submission_number, user_id, Department, HumanResource, Rectorate, PermitStartDate, LeaveExpiryDate, Dayoff, semester FROM form1 WHERE user_id = ?";
$stmtFetchForms = $conn->prepare($sqlFetchForms);
$stmtFetchForms->bind_param("i", $user_id_for_query);

if (!$stmtFetchForms->execute()) {
    die("Error executing statement: " . $stmtFetchForms->error);
}

$stmtFetchForms->bind_result(
    $submission_number,
    $user_id,
    $Department,
    $HumanResource,
    $Rectorate,
    $PermitStartDate,
    $LeaveExpiryDate,
    $Dayoff,
    $semester
);

$rows = [];
while ($stmtFetchForms->fetch()) {
    $rows[] = [
        "submission_number" => $submission_number,
        "user_id" => $user_id,
        "Department" => $Department,
        "HumanResource" => $HumanResource,
        "Rectorate" => $Rectorate,
        "PermitStartDate" => $PermitStartDate,
        "LeaveExpiryDate" => $LeaveExpiryDate,
        "Dayoff" => $Dayoff,
        "semester" => $semester,
    ];
}

$stmtFetchForms->close();
$conn->close();
?>
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($translator->getCurrentLanguage()); ?>" dir="<?php echo htmlspecialchars($translator->getTextDirection()); ?>"><head>
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
        #example {
            visibility: hidden;
        }
        .btn btn-link{
            color: #007bff;
            text-decoration: none;
        }
        .dataTables_wrapper {
            min-height: 300px; /* Set this to a reasonable min height */
        }
        .card-title{
          text-align:center;
        }
         .form-select {
    width: 90px !important;
  }
        
    </style>
</head>
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<!-- Your custom datatable.js -->
<script src="Datatable/datatable.js"></script>

<body>
<section class="main-content">
  <div class="container mt-5">
    <?php if ($is_switched_from_admin): ?>
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-warning d-flex justify-content-between align-items-center">
                    <span>
                        <i class="bi bi-person-circle"></i>
                        <?php echo __("Currently viewing as:"); ?> <strong><?php echo __("User"); ?></strong>
                        <span class="badge bg-info"><?php echo __("Switched from"); ?> <?php echo $original_admin_role; ?></span>
                    </span>
                    <div>
                        <a href="../Admin/role_switch.php?switch_to=admin" class="btn btn-outline-success btn-sm">
                            <i class="bi bi-arrow-left-circle"></i> <?php echo __("Back to"); ?> <?php echo $original_admin_role; ?> <?php echo __("Mode"); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <div class="card mx-auto" style="background-color: #f8f9fa; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); border-radius: 10px;">
      <div class="card-body">
        <div class="row">
          <div class="col-lg-4 col-sm-6 col-12">
            <div class="dash-widget dash4" style="box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); border-radius: 8px;">
              <div class="dash-widgetimg">
                <i class="bi bi-hourglass-split"></i>
              </div>
              <div class="dash-widgetcontent">
                <h5>
                  <span class="counters" style="text-align: center;"><?php echo $totalPendingCount; ?></span>
                </h5>
                <h6 style="text-align: center;"><?php echo __("Pending"); ?></h6>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-sm-6 col-12">
            <div class="dash-widget dash3" style="box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); border-radius: 8px;">
              <div class="dash-widgetimg">
                <i class="bi bi-check2-circle"></i>
              </div>
              <div class="dash-widgetcontent">
                <h5>
                  <span class="counters" style="text-align: center;"><?php echo $totalApprovedCount; ?></span>
                </h5>
                <h6 style="text-align: center;"><?php echo __("Approved"); ?></h6>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-sm-6 col-12">
            <div class="dash-widget dash2" style="box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); border-radius: 8px;">
              <div class="dash-widgetimg">
                <i class="bi bi-x-circle"></i>
              </div>
              <div class="dash-widgetcontent">
                <h5>
                  <span class="counters" style="text-align: center;"><?php echo $totalRejectedCount; ?></span>
                </h5>
                <h6 style="text-align: center;"><?php echo __("Rejected"); ?></h6>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="card mt-4 mb-4" style="background-color: #f8f9fa; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); border-radius: 10px;">
      <div class="card-body">
        <div class="row">
          <div class="col-lg-12">
            <h5 class="card-title"><?php echo __("Status Table"); ?></h5>
            <div class="container">
              <table id="example" class="table table-striped nowrap" style="width:100%; display:none;">
                <thead>
                  <tr>
                    <th><?php echo __('AppNo'); ?></th>
                    <th><?php echo __('User ID'); ?></th>
                    <th><?php echo __('Department'); ?></th>
                    <th><?php echo __('Human Resources'); ?></th>
                    <th><?php echo __('Rectorate'); ?></th>
                    <th><?php echo __('Start Date'); ?></th>
                    <th><?php echo __('End Date'); ?></th>
                    <th><?php echo __('Days off'); ?></th>
                    <th><?php echo __('Semester'); ?></th>
                    <th><?php echo __('Action'); ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($rows)): ?>
                    <?php foreach ($rows as $row): ?>
                      <tr>
                        <td>
                          <button class="btn btn-link p-0 text-decoration-none" 
                            onclick="window.location.href='../Form_1/formReview.php?id=<?php echo htmlspecialchars($row['submission_number']); ?>'">
                            <?php echo htmlspecialchars($row['submission_number']); ?>
                          </button>
                        </td>
                        <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                        <td><?php 
                            $deptStatus = $row['Department'];
                            switch($deptStatus) {
                              case '0': echo __('Pending'); break;
                              case '1': echo __('Approved'); break;
                              case '2': echo __('Rejected'); break;
                              default: echo __($deptStatus);
                            }
                        ?></td>
                        <td><?php 
                            $hrStatus = $row['HumanResource'];
                            switch($hrStatus) {
                               case '0': echo __('Pending'); break;
                               case '1': echo __('Approved'); break;
                               case '2': echo __('Rejected'); break;
                               default: echo __($hrStatus);
                            }
                        ?></td>
                        <td><?php 
                            $rectStatus = $row['Rectorate'];
                            switch($rectStatus) {
                              case '0': echo __('Pending'); break;
                              case '1': echo __('Approved'); break;
                              case '2': echo __('Rejected'); break;
                              default: echo __($rectStatus);
                            }
                        ?></td>
                        <td><?php echo htmlspecialchars($row['PermitStartDate']); ?></td>
                        <td><?php echo htmlspecialchars($row['LeaveExpiryDate']); ?></td>
                        <td><?php echo htmlspecialchars($row['Dayoff']); ?></td>
                        <td><?php echo htmlspecialchars($row['semester']); ?></td>
                        <td class="action-buttons">
                          <div class="button-wrapper">
                            <?php if ($row['Department'] != 'Approved'): ?>
                              <button class="btn btn-primary custom-btn">
                                <a href="../Form_1/edit.php?id=<?php echo htmlspecialchars($row['submission_number']); ?>" class="text-light">
                                  <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                              </button>
                            <?php endif; ?>
                            <button class="btn btn-primary custom-btn">
                              <a href="../Print-details/Print-details.php?id=<?php echo htmlspecialchars($row['submission_number']); ?>" class="text-light">
                                <i class="fa-solid fa-print"></i>
                              </a>
                            </button>
                           <?php if ($row['Department'] === 'Pending'): ?>
    <button class="btn btn-danger custom-btn delete-btn" 
        data-id="<?php echo htmlspecialchars($row['submission_number']); ?>"
        onclick="deleteRequest(this)">
        <i class="fa-solid fa-trash-can"></i>
    </button>
<?php endif; ?>

                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="10" class="text-center">No data found</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<script>
    const currentLang = "<?php echo $_SESSION['lang']; ?>";
</script>


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
     document.getElementById('addSemesterBtn').addEventListener('click', function() {
         var semesterName = document.getElementById('semesterName').value.trim();
         if (semesterName) {
             var option = document.createElement('option');
             option.value = semesterName;
             option.textContent = semesterName;
             document.getElementById('dropdown').appendChild(option);
             document.getElementById('semesterName').value = '';
             var dropdown = document.getElementById('dropdown');
             var options = Array.from(dropdown.options).sort((a, b) => a.textContent.localeCompare(b.textContent));
             dropdown.innerHTML = '';
             options.forEach(option => dropdown.appendChild(option));
         }
     }); 
    </script>
    <script>
function deleteRequest(button) {
    if (confirm('Are you sure you want to delete this request? This action cannot be undone.')) {
        const id = button.getAttribute('data-id');
        
        fetch(`../Form_1/delete.php?id=${id}&table=form1`, {
            method: 'GET'
        })
        .then(response => response.text())
        .then(data => {
            if (data.includes('successful')) {
                // Show success message
                alert('Request deleted successfully');
                // Remove the row from the table
                button.closest('tr').remove();
                // Reload the page to update counts
                location.reload();
            } else {
                alert('Error deleting request');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting request');
        });
    }
}
</script>
</body>
</html>
