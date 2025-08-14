<?php

// Create this file as: all_notifications.php
session_start();
include(__DIR__ . "/../configuration/configuration.php");   // If configuration.php is in the same folder
include(__DIR__ . "/notifications.php");                 // If notifications.php is in the same folder
include(__DIR__ . "/../navbar.php");   // If configuration.php is in the same folder

// Database connection
$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current user
$currentUser = '';
if (isset($_SESSION['Admin_name'])) {
    $currentUser = $_SESSION['Admin_name'];
} elseif (isset($_SESSION['user_name'])) {
    $currentUser = $_SESSION['user_name'];
}

if (empty($currentUser)) {
    header("Location: ../Dashboard/index.php");
    exit();
}

// Get user ID - Simple approach to find the correct column
$userSql = "SELECT * FROM users1 WHERE user_name = ?";
$stmt = $conn->prepare($userSql);
$stmt->bind_param("s", $currentUser);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();

if (!$userData) {
    die("User not found");
}

// Try different possible column names for user ID
$user_id = null;
$possibleColumns = ['user_id', 'id', 'User_ID', 'ID', 'userid', 'UserID'];
foreach ($possibleColumns as $column) {
    if (isset($userData[$column])) {
        $user_id = $userData[$column];
        break;
    }
}

if ($user_id === null) {
    die("Could not find user ID column. Available columns: " . implode(', ', array_keys($userData)));
}

// Pagination
$page = intval($_GET['page'] ?? 1);
$limit = 15; // Show 15 notifications per page
$offset = ($page - 1) * $limit;

// Get all notifications for current user
$sql = "SELECT id, message, type, url, is_read, created_at 
        FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM notifications WHERE user_id = ?";
$countStmt = $conn->prepare($countSql);
$countStmt->bind_param("i", $user_id);
$countStmt->execute();
$totalResult = $countStmt->get_result();
$totalCount = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalCount / $limit);

// Include the navbar
require_once(__DIR__ . "/../lang.php"); 
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($translator->getCurrentLanguage()); ?>" dir="<?php echo htmlspecialchars($translator->getTextDirection()); ?>"><head>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __("All Notifications"); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../Css/style.css">
    <link rel="icon" href="../logo/logo1.png" type="image/png" class="logo">
    <style>
        .main-content {
            margin-left: 0px;
            width:100%;
            margin-top: 80px;
            padding: 20px;
        }
        
        @media (max-width: 1202px) {
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }
        
        .notification-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .notification-card.unread {
            background-color: #f8f9ff;
            border-left-color: #007bff;
        }
        
        .notification-card.read {
            background-color: #ffffff;
            border-left-color: #e9ecef;
        }
        
        .notification-type-info { border-left-color: #17a2b8 !important; }
        .notification-type-success { border-left-color: #28a745 !important; }
        .notification-type-warning { border-left-color: #ffc107 !important; }
        .notification-type-danger { border-left-color: #dc3545 !important; }
        .notification-type-rejection { border-left-color: #dc3545 !important; }
    </style>
</head>
<body>

<main id="main" class="main">
    <div class="main-content">
        <div class="pagetitle">
            <h1><i class="bi bi-bell me-2"></i><?php echo __("All Notifications"); ?></h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo generateMainLink1(); ?>"><?php echo __("Dashboard"); ?></a></li>
                    <li class="breadcrumb-item active"><?php echo __("Notifications"); ?></li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">
        <?php echo __("Your Notifications"); ?>
        <span class="badge bg-primary ms-2"><?php echo $totalCount; ?></span>
    </h5>
    <div>
        <button class="btn btn-outline-primary btn-sm me-2" onclick="toggleAllReadStatus()">
            <i class="bi bi-arrow-repeat"></i> <?php echo __("Toggle All Read/Unread"); ?>
        </button>
        <button class="btn btn-outline-danger btn-sm" onclick="clearOldNotifications()">
            <i class="bi bi-trash"></i> <?php echo __("Clear Old"); ?>
        </button>
    </div>
</div>
                        
                        <div class="card-body p-0">
                            <?php if (empty($notifications)): ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-bell-slash display-1 text-muted"></i>
                                    <h5 class="mt-3 text-muted"><?php echo __("No notifications found"); ?></h5>
                                    <p class="text-muted"><?php echo __("You'll see your notifications here when you receive them."); ?></p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($notifications as $index => $notification): ?>
                                    <div class="notification-card card mb-2 mx-2 <?php echo $notification['is_read'] ? 'read' : 'unread'; ?> notification-type-<?php echo $notification['type']; ?>" 
                                         id="notification-<?php echo $notification['id']; ?>">
                                        <div class="card-body py-3">
                                            <div class="d-flex align-items-center justify-content-between w-100">
                                                <!-- Left side: Icon and Message -->
                                                <div class="d-flex align-items-start flex-grow-1">
                                                    <div class="me-3">
                                                        <?php
                                                        $iconClass = '';
                                                        switch($notification['type']) {
                                                            case 'success': $iconClass = 'bi-check-circle text-success'; break;
                                                            case 'warning': $iconClass = 'bi-exclamation-triangle text-warning'; break;
                                                            case 'rejection': $iconClass = 'bi-x-circle text-danger'; break;
                                                            case 'danger': $iconClass = 'bi-exclamation-circle text-danger'; break;
                                                            default: $iconClass = 'bi-info-circle text-info'; break;
                                                        }
                                                        ?>
                                                        <i class="bi <?php echo $iconClass; ?> fs-4"></i>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1 <?php echo !$notification['is_read'] ? 'fw-bold' : ''; ?>">
                                                            <?php echo htmlspecialchars(__($notification['message'])); ?>
                                                            <?php if (!$notification['is_read']): ?>
                                                                <span class="badge bg-primary ms-2"><?php echo __("New"); ?></span>
                                                            <?php endif; ?>
                                                        </h6>
                                                        <small class="text-muted">
                                                            <i class="bi bi-clock me-1"></i>
                                                            <?php echo date('F j, Y \a\t g:i A', strtotime($notification['created_at'])); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                                
                                                <!-- Right side: Action Buttons -->
                                                <div class="ms-3">
                                                    <div class="btn-group" role="group">
                                                        <!-- Toggle Read/Unread Button -->
                                                        <button class="btn <?php echo $notification['is_read'] ? 'btn-primary' : 'btn-outline-primary'; ?> btn-sm toggle-read-btn" 
                                                                onclick="toggleReadStatus(<?php echo $notification['id']; ?>)"
                                                                title="<?php echo $notification['is_read'] ? __('Mark as unread') : __('Mark as read'); ?>"
                                                                data-notification-id="<?php echo $notification['id']; ?>">
                                                            <i class="bi <?php echo $notification['is_read'] ? 'bi-check-circle-fill' : 'bi-check'; ?>"></i>
                                                        </button>
                                                        
                                                        <?php if (!empty($notification['url'])): ?>
                                                            <a href="<?php echo htmlspecialchars($notification['url']); ?>" 
                                                               class="btn btn-outline-secondary btn-sm"
                                                               title="<?php echo __('View details'); ?>">
                                                                <i class="bi bi-arrow-right"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        
                                                        <button class="btn btn-outline-danger btn-sm" 
                                                                onclick="deleteNotification(<?php echo $notification['id']; ?>)"
                                                                title="<?php echo __('Delete'); ?>">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="card-footer">
                                <nav aria-label="Notifications pagination">
                                    <ul class="pagination justify-content-center mb-0">
                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                                <i class="bi bi-chevron-left"></i> <?php echo __("Previous"); ?>
                                            </a>
                                        </li>
                                        
                                        <?php
                                        $start = max(1, $page - 2);
                                        $end = min($totalPages, $page + 2);
                                        
                                        if ($start > 1): ?>
                                            <li class="page-item"><a class="page-link" href="?page=1">1</a></li>
                                            <?php if ($start > 2): ?>
                                                <li class="page-item disabled"><span class="page-link">...</span></li>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = $start; $i <= $end; $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($end < $totalPages): ?>
                                            <?php if ($end < $totalPages - 1): ?>
                                                <li class="page-item disabled"><span class="page-link">...</span></li>
                                            <?php endif; ?>
                                            <li class="page-item"><a class="page-link" href="?page=<?php echo $totalPages; ?>"><?php echo $totalPages; ?></a></li>
                                        <?php endif; ?>
                                        
                                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                                <?php echo __("Next"); ?> <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                                
                                <div class="text-center mt-3">
                                    <small class="text-muted">
                                        <?php echo __("Showing"); ?> <?php echo (($page - 1) * $limit + 1); ?> - <?php echo min($page * $limit, $totalCount); ?> 
                                        <?php echo __("of"); ?> <?php echo $totalCount; ?> <?php echo __("notifications"); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleReadStatus(notificationId) {
    // Prevent multiple clicks
    const button = document.querySelector(`[data-notification-id="${notificationId}"]`);
    if (button.disabled) return;
    button.disabled = true;
    
    fetch('toggle_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ notification_id: notificationId })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Server response:', data); // Debug log
        
        if (data.success) {
            const notificationCard = document.getElementById('notification-' + notificationId);
            const markReadBtn = notificationCard.querySelector('.toggle-read-btn');
            const newBadge = notificationCard.querySelector('.badge.bg-primary');
            const title = notificationCard.querySelector('h6');
            
            if (data.is_read == 1) {
                // Mark as read
                notificationCard.classList.remove('unread');
                notificationCard.classList.add('read');
                
                if (newBadge) newBadge.remove();
                if (title) title.classList.remove('fw-bold');
                
                if (markReadBtn) {
                    markReadBtn.classList.remove('btn-outline-primary');
                    markReadBtn.classList.add('btn-primary');
                    markReadBtn.innerHTML = '<i class="bi bi-check-circle-fill"></i>';
                    markReadBtn.title = '<?php echo __("Mark as unread"); ?>';
                }
            } else {
                // Mark as unread
                notificationCard.classList.remove('read');
                notificationCard.classList.add('unread');
                
                if (title) {
                    title.classList.add('fw-bold');
                    if (!newBadge) {
                        const badge = document.createElement('span');
                        badge.className = 'badge bg-primary ms-2';
                        badge.textContent = '<?php echo __("New"); ?>';
                        title.appendChild(badge);
                    }
                }
                
                if (markReadBtn) {
                    markReadBtn.classList.remove('btn-primary');
                    markReadBtn.classList.add('btn-outline-primary');
                    markReadBtn.innerHTML = '<i class="bi bi-check"></i>';
                    markReadBtn.title = '<?php echo __("Mark as read"); ?>';
                }
            }
            
            // Update header count
            updateNotificationCount();
        } else {
            alert('Error: ' + (data.message || 'Unknown error occurred'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error occurred. Please try again.');
    })
    .finally(() => {
        // Re-enable button
        button.disabled = false;
    });
}

function toggleAllReadStatus() {
    if (confirm('<?php echo __("Toggle read status for all notifications?"); ?>')) {
        fetch('toggle_all_notifications_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Toggle all response:', data); // Debug log
            
            if (data.success) {
                alert(data.message || 'All notifications toggled successfully');
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Unknown error occurred'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error occurred. Please try again.');
        });
    }
}

function updateNotificationCount() {
    fetch('get_notification_count.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update badge count if exists
            const badge = document.querySelector('.badge.bg-primary');
            if (badge) {
                badge.textContent = data.count;
                if (data.count === 0) {
                    badge.style.display = 'none';
                } else {
                    badge.style.display = 'inline';
                }
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

function deleteNotification(notificationId) {
    if (confirm('<?php echo __("Delete this notification?"); ?>')) {
        fetch('delete_notification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ notification_id: notificationId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('notification-' + notificationId).remove();
                updateNotificationCount();
                
                // Check if page is empty and reload if needed
                const remainingNotifications = document.querySelectorAll('[id^="notification-"]');
                if (remainingNotifications.length === 0) {
                    location.reload();
                }
            }
        })
        .catch(error => console.error('Error:', error));
    }
}

function clearOldNotifications() {
    if (confirm('<?php echo __("Delete notifications older than 30 days?"); ?>')) {
        fetch('clear_old_notifications.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('<?php echo __("Old notifications cleared successfully"); ?>');
                location.reload();
            }
        })
        .catch(error => console.error('Error:', error));
    }
}
</script>
</body>
</html>

<?php $conn->close(); ?>