<?php
session_start();
if (!isset($_SESSION["SuperAdminName"])) {
    header("Location: ../index.php");
    exit();
}

require_once "../lang.php";
include_once "../navbar.php";
include "../configuration/configuration.php";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = $_GET["id"];
$successMessage = "";

if (isset($_POST["submit"])) {
    $fullname = $_POST["fullname"];
    $username = $_POST["user_name"];
    $password = $_POST["password"];
    $role = $_POST["Role"];
    $faculty_id = $_POST["department"];

    $sql = "UPDATE users1 SET fullName = ?, password = ?, Role = ?, user_name = ?, faculty_id = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssii", $fullname, $password, $role, $username, $faculty_id, $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $successMessage = "User Info Updated successfully!";
    } else {
        $successMessage = "Error: " . $stmt->error;
    }
    $stmt->close();
}

$sql = "SELECT fullName, password, user_name, Role, faculty_id FROM users1 WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($fullName, $password, $user_name, $Role, $faculty_id);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Edit</title>
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
            padding-bottom: 10px;
        }
    </style>
</head>
<body>
<section class="main-content">
   <div class="container mt-5">
       <?php if ($successMessage): ?>
           <div id="success-message" class="alert alert-success">
               <?php echo $successMessage; ?>
           </div>
       <?php endif; ?>
       <form action="" method="post">
           <div class="card mb-4">
               <div class="card-header">Edit User Information</div>
               <div class="card-body">
                   <div class="form-row align-items mb-4 mt-4">
                       <div class="form-group col-md-12">
                           <label for="name" class="mr-2"><?php echo __('Full Name'); ?>:</label>
                           <input type="text" name="fullname" class="form-control" value="<?php echo $fullName; ?>">
                       </div>
                   </div>
                   <div class="form-row align-items mb-4 mt-4">
                       <div class="form-group col-md-12">
                           <label for="user_name" class="mr-2"><?php echo __('User Name'); ?>:</label>
                           <input type="text" name="user_name" class="form-control" value="<?php echo $user_name; ?>">
                       </div>
                   </div>
                   <div class="form-row align-items mb-4 mt-4">
                       <div class="form-group col-md-12">
                           <label for="password" class="mr-2"><?php echo __('Password'); ?>:</label>
                           <input type="text" name="password" class="form-control" value="<?php echo $password; ?>">
                       </div>
                   </div>
                   <div class="form-row align-items-center mb-2">
                       <div class="form-group col-md-12">
                           <label for="Department" class="mr-2"><?php echo __('Department'); ?>:</label>
                           <select class="form-control" id="Department" name="department">
                               <?php
                               // Fetch and display faculties dynamically
                               $sql_faculties = "SELECT faculty_id, faculty_name FROM faculties1";
                               $result = $conn->query($sql_faculties);
                               while ($row = $result->fetch_assoc()) {
                                   echo '<option value="' . $row["faculty_id"] . '"' . ($row["faculty_id"] == $faculty_id ? ' selected' : '') . '>' . $row["faculty_name"] . '</option>';
                               }
                               ?>
                           </select>
                       </div>
                   </div>
                   <div class="form-row align-items mb-4 mt-4">
                       <div class="form-group col-md-12">
                           <label for="Role" class="mr-2"><?php echo __('Role'); ?>:</label>
                           <input type="text" name="Role" class="form-control" value="<?php echo $Role; ?>">
                       </div>
                   </div>
                   <div class="button">
                       <button type="submit" class="SubmitButton1" name="submit" id="submit">Save</button>
                       <button type="button" class="SubmitButton1" name="btn" id="btn">Back</button>
                   </div>
               </div>
           </div>
       </form>
   </div>
</section>
<script>
    document.querySelectorAll('input').forEach(function(input) {
        input.addEventListener('focus', function() {
            document.getElementById('success-message').style.display = 'none';
        });
    });

    document.getElementById("btn").addEventListener("click", function(event) {
        event.preventDefault();
        window.location.href = "../Status/userList.php";
    });
</script>
</body>
</html>

<?php $conn->close(); ?>
