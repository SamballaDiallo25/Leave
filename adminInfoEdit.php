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
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$id = $_GET["id"];
$successMessage = "";

if (isset($_POST["submit"])) {
    $faculty_head = $_POST["faculty_head"];
    $faculty_name = $_POST["faculty_name"];

    $sql =
        "UPDATE faculties1 SET faculty_name=?, faculty_head=? WHERE faculty_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $faculty_name, $faculty_head, $id);
    $result = $stmt->execute();

    if ($result) {
        $successMessage = "Admin Info Updated successfully!";
    } else {
        $successMessage = "Error: " . $stmt->error;
    }
}

$sql = "SELECT * FROM faculties1 WHERE faculty_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($faculty_id, $faculty_name, $faculty_head);

// Fetch the row
if ($stmt->fetch()) {
} else {
    echo "No user found with the specified user name.";
}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form</title>
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
      <div class="container mt-5"> <?php if ($successMessage): ?> <div id="success-message" class="alert alert-success"> <?php echo $successMessage; ?> </div> <?php endif; ?> <form action="" method="post">
          <div class="card mb-4">
            <div class="card-header"> <?php echo __('Edit Admin Information');?> </div>
            <div class="card-body"> <?php if (!empty($updateMessage)) { ?> <div class="update-message"> <?php echo $updateMessage; ?> </div> <?php } ?> <div class="form-row align-items-center mb-4 mt-4">
                <div class="form-group col-md-12">
                  <label for="name" class="mr-2"> <?php echo __('Faculty Name');?>: </label>
                  <input type="text" name="faculty_name" class="form-control" value="<?php echo $faculty_name; ?>">
                </div>
              </div>
              <div class="form-row align-items-center mb-2">
                <div class="form-group col-md-12">
                  <label for="name" class="mr-2"> <?php echo __('Faculty Head');?>: </label>
                  <input type="text" name="faculty_head" class="form-control" value="<?php echo $faculty_head; ?>">
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
  document.getElementById("btn").addEventListener("click", function() {
      event.preventDefault();
      window.location.href = "../Status/adminList.php";
  }); 
</script>
  </body>
</html>