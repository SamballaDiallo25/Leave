<?php
require_once "configuration/configuration.php";
$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get all users with plain (unhashed) passwords
$sql = "SELECT user_id, password FROM users1 WHERE password_migrated = 0 OR password_migrated IS NULL";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $user_id = $row['user_id'];
    $plain_password = $row['password'];
    $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

    // Update the password and mark as migrated
    $update = $conn->prepare("UPDATE users1 SET password = ?, password_migrated = 1 WHERE user_id = ?");
    $update->bind_param("si", $hashed_password, $user_id);
    $update->execute();
    $update->close();
}

$conn->close();
echo "Password migration complete.";
?>