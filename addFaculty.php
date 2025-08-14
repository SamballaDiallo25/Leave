<?php session_start();
if (!isset($_SESSION["SuperAdminName"])) {
    // Redirect the user to the index page or login page
    header("Location: ../index.php");
    exit(); // Stop further execution
}
require_once "../lang.php";
require_once "../navbar.php";
include "../configuration/configuration.php";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$lastFacultyIdResult = $conn->query("SELECT MAX(faculty_id) as last_id FROM faculties1");
if ($lastFacultyIdResult) {
    $row = $lastFacultyIdResult->fetch_assoc();
    $lastFacultyId = $row['last_id'] ?? 0;
    $newFacultyId = $lastFacultyId + 1;
    $lastFacultyIdResult->close();
} else {
    die("Error fetching last faculty ID: " . $conn->error);
}

$successMessage = "";

if (isset($_POST["submit"])) {
    $faculty_id = $newFacultyId; // Use the auto-generated ID
    $faculty_name = $_POST["faculty_name"];
    $faculty_head = $_POST["faculty_head"];

    $sql = "INSERT INTO faculties1 (faculty_id, faculty_name, faculty_head) 
            VALUES (?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("iss", $faculty_id, $faculty_name, $faculty_head);

        if ($stmt->execute()) {
            $successMessage = "Faculty added successfully!";
        } else {
            $successMessage = "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        $successMessage = "Error: " . $conn->error;
    }

    $conn->close();
}
?> 
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($translator->getCurrentLanguage()); ?>" dir="<?php echo htmlspecialchars($translator->getTextDirection()); ?>"><head>
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Faculty</title>
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
        }
      }

      h5 {
        text-align: center;
        padding-top: 10px;
        padding-bottom: 10px;
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
          </div> <?php endif; ?> 
          <form action="" method="post" enctype="">
          <div class="card mb-4">
            <div class="card-header"> 
          <?php echo __("Add Faculty");?> </div>
            <div class="card-body">
              <div class="form-row align-items-center mb-4 mt-4">
                <div class="form-group col-md-12">
                  <label for="faculty_id" class="mr-2"> <?php echo __("Faculty ID");?>: </label>
                  <input type="text" name="faculty_id" class="form-control" value="<?php echo $newFacultyId; ?>" disabled>
                </div>
              </div>
              <div class="form-row align-items-center mb-4">
                <div class="form-group col-md-12">
                  <label for="faculty_name" class="mr-2"> <?php echo __("Faculty Name");?>: </label>
                  <input type="text" name="faculty_name" class="form-control" required>
                </div>
              </div>
              <div class="form-row align-items-center mb-4">
                <div class="form-group col-md-12">
                  <label for="faculty_head" class="mr-2"> <?php echo __("Faculty Head");?>: </label>
                  <input type="text" name="faculty_head" class="form-control" required>
                </div>
              </div>
              <div class="button">
                <button type="submit" class="SubmitButton1" name="submit"><?php echo __("Save")?></button>
                <button type="button" class="SubmitButton1" name="btn" id="btn"><?php echo __("Back")?></button>
              </div>
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
            document.getElementById('success-message').style.display = 'none';
        });
    }); 
</script>
</body>
</html>
