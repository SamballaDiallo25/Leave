<?php

session_start();

// Session timeout check (30 mins)
$session_timeout = 1800;
if (isset($_SESSION['last_activity'])) {
    if ((time() - $_SESSION['last_activity']) >= $session_timeout) {
        session_unset();
        session_destroy();
        header("Location: ../index.php?timeout=1");
        exit();
    }
}
$_SESSION['last_activity'] = time();

// Auth check
if (!isset($_SESSION["Admin_name"])) {
    header("Location: ../index.php");
    exit();
}

require_once "../configuration/configuration.php";
require_once "../lang.php";

// DB connection
$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die(__("Connection failed: ") . $conn->connect_error);
}

// Query to get absence details from `form1` including dates
$sql = "SELECT FullName, input AS leave_type, Dayoff AS absent_days, 
               PermitStartDate AS start_date, LeaveExpiryDate AS end_date,
               submission_date, submission_number
        FROM form1
        WHERE Department = 'Approved'
        ORDER BY FullName, submission_date DESC";
$result = $conn->query($sql);

// Organize data by user
$users_data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $user = $row['FullName'];
        $leave_type = trim($row['leave_type']);
        $absent_days = (int)$row['absent_days'];
        $start_date = $row['start_date'];
        $end_date = $row['end_date'];
        $submission_date = $row['submission_date'];
        $submission_number = $row['submission_number'];
        
        if (!isset($users_data[$user])) {
            $users_data[$user] = [
                'leaves' => [],
                'total_requests' => 0,
                'total_days' => 0
            ];
        }
        
        $users_data[$user]['leaves'][] = [
            'leave_type' => $leave_type,
            'absent_days' => $absent_days,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'submission_date' => $submission_date,
            'submission_number' => $submission_number
        ];
        
        $users_data[$user]['total_requests']++;
        $users_data[$user]['total_days'] += $absent_days;
    }
}

?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($translator->getCurrentLanguage()); ?>" dir="<?php echo htmlspecialchars($translator->getTextDirection()); ?>"><head>
<head>
  <meta charset="UTF-8">
  <title><?php echo __("Absence Records"); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Bootstrap + Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Custom Style -->
  <link rel="stylesheet" href="../Css/style.css">

  <style>
    .main-content {
      margin-left: 305px;
      padding: 2rem;
      margin-top: 80px;
    }
    @media (max-width: 768px) {
      .main-content {
        margin-left: 0;
      }
    }
    .user-card {
      margin-bottom: 1rem;
      border: 1px solid #dee2e6;
      border-radius: 8px;
    }
    .user-header {
      background-color: #f8f9fa;
      padding: 1rem;
      border-bottom: 1px solid #dee2e6;
      cursor: pointer;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .user-header:hover {
      background-color: #e9ecef;
    }
    .user-details {
      padding: 1rem;
      background-color: #fff;
    }
    .leave-item {
      background-color: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 5px;
      padding: 0.75rem;
      margin-bottom: 0.5rem;
    }
    .leave-item:last-child {
      margin-bottom: 0;
    }
    .badge-annual-leave, .badge-annual {
      background-color: #28a745;
    }
    .badge-sick-leave, .badge-sick {
      background-color: #dc3545;
    }
    .badge-excuse-leave, .badge-excuse {
      background-color: #17a2b8;
    }
    .badge-unpaid-leave, .badge-unpaid {
      background-color: #6f42c1;
    }
    .badge-other {
      background-color: #6c757d;
    }
    .collapse-icon {
      transition: transform 0.2s ease;
    }
    .collapsed .collapse-icon {
      transform: rotate(-90deg);
    }
    .leave-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 0.5rem;
    }
    .date-range {
      font-size: 0.9em;
      color: #6c757d;
    }
  </style>
</head>
<body>

<?php include("../navbar.php"); ?>

<section class="main-content">
  <div class="container-fluid">
    <div class="card mx-auto" style="background-color: #f8f9fa; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 10px;">
      <div class="card-body">
        <h2 class="mb-4 text-center"><?php echo __("Absence Records"); ?></h2>

        <?php if (!empty($users_data)): ?>
          <div class="accordion" id="usersAccordion">
            <?php foreach ($users_data as $user => $user_data): ?>
              <div class="user-card">
                <div class="user-header collapsed" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo md5($user); ?>" aria-expanded="false">
                  <div>
                    <h5 class="mb-0"><?php echo htmlspecialchars($user); ?></h5>
                    <small class="text-muted">
                      <?php echo $user_data['total_requests']; ?> <?php echo __("request(s)"); ?> • 
                      <?php echo $user_data['total_days']; ?> <?php echo __("total days"); ?>
                    </small>
                  </div>
                  <i class="bi bi-chevron-down collapse-icon"></i>
                </div>
                
                <div id="collapse<?php echo md5($user); ?>" class="collapse" data-bs-parent="#usersAccordion">
                  <div class="user-details">
                    <?php foreach ($user_data['leaves'] as $index => $leave): ?>
                      <div class="leave-item">
                        <div class="leave-header">
                          <div>
                            <?php
                            $badge_class = 'badge-other';
                            $leave_type_lower = strtolower(str_replace('-', '-', $leave['leave_type']));
                            
                            if (strpos($leave_type_lower, 'annual') !== false) {
                                $badge_class = 'badge-annual';
                            } elseif (strpos($leave_type_lower, 'sick') !== false) {
                                $badge_class = 'badge-sick';
                            } elseif (strpos($leave_type_lower, 'excuse') !== false) {
                                $badge_class = 'badge-excuse';
                            } elseif (strpos($leave_type_lower, 'unpaid') !== false) {
                                $badge_class = 'badge-unpaid';
                            }
                            ?>
                            <span class="badge <?php echo $badge_class; ?> me-2"><?php echo htmlspecialchars(__($leave['leave_type'])); ?></span>
                            <span class="badge bg-warning text-dark"><?php echo $leave['absent_days']; ?> <?php echo __("days"); ?></span>
                          </div>
                          <small class="text-muted">
                            <?php echo __("Request #"); ?><?php echo $leave['submission_number']; ?>
                          </small>
                        </div>
                        
                        <div class="row mt-2">
                          <div class="col-md-6">
                            <strong><?php echo __("Start Date:"); ?></strong><br>
                            <span class="date-range">
                              <i class="bi bi-calendar-event me-1"></i>
                              <?php echo date('F j, Y', strtotime(__($leave['start_date']))); ?>
                            </span>
                          </div>
                          <div class="col-md-6">
                            <strong><?php echo __("End Date:"); ?></strong><br>
                            <span class="date-range">
                              <i class="bi bi-calendar-event me-1"></i>
                              <?php echo date('F j, Y', strtotime(__($leave['end_date']))); ?>
                            </span>
                          </div>
                        </div>
                        
                        <div class="row mt-2">
                          <div class="col-12">
                            <strong><?php echo __("Submitted:"); ?></strong>
                            <span class="date-range">
                              <i class="bi bi-clock me-1"></i>
                              <?php echo date('F j, Y', strtotime(__($leave['submission_date']))); ?>
                            </span>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                    
                    <!-- Summary for the user -->
                    <div class="mt-3 p-2 bg-light rounded">
                      <strong><?php echo __("Summary:"); ?></strong>
                      <ul class="mb-0 mt-2">
                        <li><?php echo __("Total requests:"); ?> <?php echo $user_data['total_requests']; ?></li>
                        <li><?php echo __("Total absent days:"); ?> <?php echo $user_data['total_days']; ?></li>
                        <?php
                        // Count leave types
                        $leave_type_counts = [];
                        foreach ($user_data['leaves'] as $leave) {
                            $type = $leave['leave_type'];
                            if (!isset($leave_type_counts[$type])) {
                                $leave_type_counts[$type] = ['count' => 0, 'days' => 0];
                            }
                            $leave_type_counts[$type]['count']++;
                            $leave_type_counts[$type]['days'] += $leave['absent_days'];
                        }
                        ?>
                        <?php foreach ($leave_type_counts as $type => $data): ?>
                          <li><?php echo htmlspecialchars($type); ?>: <?php echo $data['count']; ?> <?php echo __("request(s)"); ?>, <?php echo $data['days']; ?> <?php echo __("days"); ?></li>
                        <?php endforeach; ?>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="alert alert-info text-center">
            <i class="bi bi-info-circle"></i> <?php echo __("No absence records found."); ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- JS includes -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  $(document).ready(function () {
    // Add rotation animation to collapse icons
    $('.user-header').on('click', function() {
      $(this).find('.collapse-icon').toggleClass('rotated');
    });
    
    // Handle collapse events to update icon rotation
    $('.collapse').on('show.bs.collapse', function () {
      $(this).prev('.user-header').removeClass('collapsed');
    });
    
    $('.collapse').on('hide.bs.collapse', function () {
      $(this).prev('.user-header').addClass('collapsed');
    });
  });
</script>

</body>
</html>