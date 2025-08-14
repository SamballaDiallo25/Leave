<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['AdminID']) && !isset($_SESSION['SuperAdminId'])) {
    header("Location: index.php?error=not_logged_in");
    exit();
}

// Set logged-in user name
$logged_in_user = $_SESSION['is_admin'] ? ($_SESSION['SRole'] === 'SuperAdmin' ? $_SESSION['AdminfullName'] : $_SESSION['AdminfullName']) : $_SESSION['fullName'];

// Redirect to appropriate dashboard
if (isset($_SESSION['SuperAdminId']) && $_SESSION['SRole'] === 'SuperAdmin') {
    header("Location: ../Dashboard/superAdminDashboard.php");
    exit();
} elseif (isset($_SESSION['AdminID']) && $_SESSION['is_admin'] == 1) {
    header("Location: ../Dashboard/adminDashboard.php");
    exit();
} elseif (isset($_SESSION['user_id']) && $_SESSION['is_admin'] == 0) {
    header("Location: ../Dashboard/userDashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="login.css?v=<?php echo time(); ?>">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", sans-serif;
        }
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            text-align: center;
        }
        .user {
            color: red;
            font-weight: bold;
        }
        .button-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        .button-red, .button-gray {
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
        }
        .button-red {
            background-color: #900;
        }
        .button-gray {
            background-color: gray;
        }
        p {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Confirm</h1>
        <div class="login-form">
            <p class="user"><?php echo htmlspecialchars($logged_in_user, ENT_QUOTES, 'UTF-8'); ?>,</p>
            <p>You are already logged in. Continue to your dashboard or log out.</p>
            <div class="button-container">
                <?php if (isset($_SESSION['SuperAdminId']) && $_SESSION['SRole'] === 'SuperAdmin'): ?>
                    <a href="../Dashboard/superAdminDashboard.php" class="button-red">Continue</a>
                <?php elseif (isset($_SESSION['AdminID']) && $_SESSION['is_admin'] == 1): ?>
                    <a href="../Dashboard/adminDashboard.php" class="button-red">Continue</a>
                <?php else: ?>
                    <a href="../Dashboard/userDashboard.php" class="button-red">Continue</a>
                <?php endif; ?>
                <a href="../Dashboard/logout.php" class="button-gray">Log Out</a>
            </div>
        </div>
    </div>
    <script>
        window.history.pushState(null, "", window.location.href);
        window.onpopstate = function() {
            window.history.pushState(null, "", window.location.href);
        };
    </script>
</body>
</html>