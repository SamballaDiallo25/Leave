<?php
session_start();
if (!isset($_SESSION["SuperAdminName"])) {
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

// Fetch faculties to populate the dropdown
$facultyResult = $conn->query(
    "SELECT Faculty_id, Faculty_name FROM faculties1 ORDER BY Faculty_name"
);
$faculties = [];
if ($facultyResult) {
    while ($row = $facultyResult->fetch_assoc()) {
        $faculties[] = $row;
    }
    $facultyResult->close();
} else {
    die("Error fetching faculties: " . $conn->error);
}
$nextUserId = 1; 
$result = $conn->query("SELECT MAX(user_id) AS last_user_id FROM users1");
if ($result) {
    $row = $result->fetch_assoc();
    if ($row && $row['last_user_id']) {
        $nextUserId = $row['last_user_id'] + 1;
    }
    $result->close();
} else {
    die("Error fetching last user ID: " . $conn->error);
}

$successMessage = "";
$errors = [];

if (isset($_POST["submit"])) {
    $fullname = $_POST["fullname"];
    $user_id = $_POST["user_id"];
    $user_name = $_POST["user_name"];
    $password = $_POST["password"];
    $faculty_id = $_POST["faculty_id"];
    $position = $_POST["position"];
    $role = $_POST["isAdmin"];

    // Validate password requirements
    if (strlen($password) < 8) {
        $errors['password'] = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors['password'] = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errors['password'] = "Password must contain at least one lowercase letter.";
    }

    // Check if username already exists
    $checkUserSql = "SELECT user_name FROM users1 WHERE user_name = ?";
    if ($checkUserStmt = $conn->prepare($checkUserSql)) {
        $checkUserStmt->bind_param("s", $user_name);
        $checkUserStmt->execute();
        $checkUserResult = $checkUserStmt->get_result();
        
        if ($checkUserResult->num_rows > 0) {
            $errors['user_name'] = "Username already exists.";
        }
        $checkUserStmt->close();
    }

    // Check if password already exists
    $checkPasswordSql = "SELECT password FROM users1 WHERE password = ?";
    if ($checkPasswordStmt = $conn->prepare($checkPasswordSql)) {
        $checkPasswordStmt->bind_param("s", $password);
        $checkPasswordStmt->execute();
        $checkPasswordResult = $checkPasswordStmt->get_result();
        
        if ($checkPasswordResult->num_rows > 0) {
            if (!isset($errors['password'])) {
                $errors['password'] = "This password is already in use.";
            } else {
                $errors['password'] .= " This password is also already in use.";
            }
        }
        $checkPasswordStmt->close();
    }

    // If no errors, proceed with insertion
    if (empty($errors)) {
        $sql = "INSERT INTO users1 (user_id, fullName, password, faculty_id, Role, user_name, is_admin) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param(
                "ississi",
                $user_id,
                $fullname,
                $password,
                $faculty_id,
                $position,
                $user_name,
                $role
            );

            if ($stmt->execute()) {
                $successMessage = "User added successfully!";
                // Clear form data after successful submission
                $_POST = [];
                // Update next user ID
                $nextUserId++;
            } else {
                $successMessage = "Error: " . $stmt->error;
            }

            $stmt->close();
        } else {
            $successMessage = "Error: " . $conn->error;
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($translator->getCurrentLanguage()); ?>" dir="<?php echo htmlspecialchars($translator->getTextDirection()); ?>"><head>
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Users</title>
    <style>
      .SubmitButton1 {
        background-color: #141414;
        color: white;
        padding: 10px 20px;
        border: none;
        cursor: pointer;
        font-size: 16px;
        border-radius: 5%;
        width: auto;
        float: right;
        margin-right: 10px;
      }

      @media (max-width: 430px) {
        .button {
          display: flex;
          justify-content: center;
          /* Horizontally center the button */
          align-items: center;
          /* Vertically center the button */
          width: 100%;
        }
      }

      h5 {
        text-align: center;
        padding-top: 10px;
        padding-bottom: 10px;
      }

      .error-message {
        color: #dc3545;
        font-size: 12px;
        margin-top: 5px;
        display: block;
      }

      .password-hint {
        color: #6c757d;
        font-size: 12px;
        margin-top: 5px;
        display: block;
      }

      .form-control.error {
        border-color: #dc3545;
      }

      /* Password input container styles */
      .password-container {
        position: relative;
      }

      .password-toggle {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
        color: #6c757d;
        font-size: 18px;
        z-index: 1;
        display: none; /* Hidden by default */
        transition: opacity 0.2s ease;
      }

      .password-toggle.show {
        display: block; /* Show when there's text */
      }

      .password-toggle:hover {
        color: #495057;
      }

      .password-toggle:focus {
        outline: none;
      }

      /* Adjust padding for password input to make room for the eye icon */
      .password-input {
        padding-right: 40px !important;
      }
      
    </style>
    <link rel="icon" href="../logo/logo1.png" type="image/png">
  </head>
  <body>
    <section class="main-content">
      <div class="container mt-5"> 
        <?php if ($successMessage): ?> 
          <div id="success-message" class="alert alert-success"> 
            <?php echo $successMessage; ?> 
          </div> 
        <?php endif; ?> 
        
        <form action="" method="post" enctype="">
          <div class="card mb-4">
            <div class="card-header"> <?php echo __("Add User");?> </div>
            <div class="card-body">
              <div class="form-row align-items-center mb-4 mt-4">
                <div class="form-group col-md-12">
                  <label for="name" class="mr-2"> <?php echo __("Full Name");?>: </label>
                  <input type="text" name="fullname" class="form-control" value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>" required>
                </div>
              </div>
              <div class="form-row align-items-center mb-4">
                <div class="form-group col-md-12">
                  <label for="name" class="mr-2"> <?php echo __("User ID");?>: </label>
                  <input type="text" name="user_id" class="form-control" value="<?php echo $nextUserId; ?>" readonly>
                </div>
              </div>
              <div class="form-row align-items-center mb-4">
                <div class="form-group col-md-12">
                  <label for="name" class="mr-2"> <?php echo __("User Name");?>: </label>
                  <input type="text" name="user_name" class="form-control <?php echo isset($errors['user_name']) ? 'error' : ''; ?>" value="<?php echo isset($_POST['user_name']) ? htmlspecialchars($_POST['user_name']) : ''; ?>" required>
                  <?php if (isset($errors['user_name'])): ?>
                    <span class="error-message"><?php echo $errors['user_name']; ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="form-row align-items-center mb-4">
                <div class="form-group col-md-12">
                  <label for="name" class="mr-2"> <?php echo __("Password");?>: </label>
                  <div class="password-container">
                    <input type="password" name="password" id="password" class="form-control password-input <?php echo isset($errors['password']) ? 'error' : ''; ?>" value="<?php echo isset($_POST['password']) ? htmlspecialchars($_POST['password']) : ''; ?>" required>
                    <button type="button" class="password-toggle" id="togglePassword">
                      <span id="eyeIcon">👁️</span>
                    </button>
                  </div>
                  <span class="password-hint"><?php echo __("Password must have at least 8 characters, one uppercase letter, and one lowercase letter.")?></span>
                  <?php if (isset($errors['password'])): ?>
                    <span class="error-message"><?php echo $errors['password']; ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="form-row align-items-center mb-4">
                <div class="form-group col-md-12">
                  <label for="faculty_id" class="mr-2"> <?php echo __("Faculty/Department");?>: </label>
                  <select class="form-control" id="faculty_id" name="faculty_id"> 
                    <?php foreach($faculties as $faculty): ?> 
                      <option value="<?php echo htmlspecialchars($faculty['Faculty_id']); ?>" <?php echo (isset($_POST['faculty_id']) && $_POST['faculty_id'] == $faculty['Faculty_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($faculty['Faculty_name']); ?> 
                      </option> 
                    <?php endforeach; ?> 
                  </select>
                </div>
              </div>
              <div class="form-row align-items-center mb-4">
                <div class="form-group col-md-12">
                  <label for="position" class="mr-2"> <?php echo __("Position");?>: </label>
                  <select class="form-control" id="position" name="position" required>
                    <option value=""><?php echo __("Select Position");?></option>
                    <option value="HOF" <?php echo (isset($_POST['position']) && $_POST['position'] == 'HOF') ? 'selected' : ''; ?>>
                      HOF
                    </option>
                    <option value="Lecturer" <?php echo (isset($_POST['position']) && $_POST['position'] == 'Lecturer') ? 'selected' : ''; ?>>
                      Lecturer
                    </option>
                    <option value="HumanResource" <?php echo (isset($_POST['position']) && $_POST['position'] == 'HumanResource') ? 'selected' : ''; ?>>
                      Human Resource
                    </option>
                    <option value="Rectorate" <?php echo (isset($_POST['position']) && $_POST['position'] == 'Rectorate') ? 'selected' : ''; ?>>
                      Rectorate
                    </option>
                  </select>
                </div>
              </div>
              <div class="form-row align-items-center mb-4">
                <div class="form-group col-md-12">
                  <label for="role" class="mr-2"> <?php echo __("Role");?>: </label>
                  <select class="form-control" id="role" name="isAdmin">
                    <option value="select"> <?php echo __("Select");?> </option>
                    <option value="1" <?php echo (isset($_POST['isAdmin']) && $_POST['isAdmin'] == '1') ? 'selected' : ''; ?>>
                      <?php echo __("Admin");?> 
                    </option>
                    <option value="0" <?php echo (isset($_POST['isAdmin']) && $_POST['isAdmin'] == '0') ? 'selected' : ''; ?>>
                      <?php echo __("User");?> 
                    </option>
                  </select>
                </div>
              </div>
              <div class="button">
                <button type="submit" class="SubmitButton1" name="submit"><?php echo __("Save")?></button>
                <button type="button" class="SubmitButton1" name="btn" id="btn"><?php echo __("Back")?></button>
              </div>
            </div>
          </div>
      </div>
      </form>
</section>
<script>
     document.getElementById("btn").addEventListener("click", function() {
     event.preventDefault();
     window.location.href = "../Dashboard/superAdminDashboard.php";
 });
 
 document.querySelectorAll('input').forEach(function(input) {
     input.addEventListener('focus', function() {
         var successMessage = document.getElementById('success-message');
         if (successMessage) {
             successMessage.style.display = 'User added successfully!';
         }
     });
 });

 // Password toggle functionality
 const passwordInput = document.getElementById('password');
 const toggleButton = document.getElementById('togglePassword');
 const eyeIcon = document.getElementById('eyeIcon');

 // Function to toggle password visibility
 function togglePasswordVisibility() {
     if (passwordInput.type === 'password') {
         passwordInput.type = 'text';
         eyeIcon.textContent = '🙈'; // Eye with slash or closed eye
     } else {
         passwordInput.type = 'password';
         eyeIcon.textContent = '👁️'; // Open eye
     }
 }

 // Function to show/hide toggle button based on input content
 function toggleButtonVisibility() {
     if (passwordInput.value.length > 0) {
         toggleButton.classList.add('show');
     } else {
         toggleButton.classList.remove('show');
         // Reset to password type when empty
         passwordInput.type = 'password';
         eyeIcon.textContent = '👁️';
     }
 }

 // Event listeners
 toggleButton.addEventListener('click', togglePasswordVisibility);
 passwordInput.addEventListener('input', toggleButtonVisibility);
 passwordInput.addEventListener('keyup', toggleButtonVisibility);
 passwordInput.addEventListener('paste', function() {
     // Small delay to allow paste content to be processed
     setTimeout(toggleButtonVisibility, 10);
 });

 // Check initial state (in case there's pre-filled content)
 toggleButtonVisibility();
</script>
</body>
</html>