<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "leave_db";

// Function to get next available submission number
function getNextAvailableSubmissionNumber($conn) {
    // Get all used submission numbers
    $sql = "SELECT submission_number FROM form1 ORDER BY submission_number";
    $result = $conn->query($sql);
    
    $used_numbers = [];
    while ($row = $result->fetch_assoc()) {
        $used_numbers[] = (int)$row['submission_number'];
    }
    
    // If no numbers are used, start with 1
    if (empty($used_numbers)) {
        return 1;
    }
    
    // Find the first gap in the sequence
    $prev = 0;
    sort($used_numbers);
    foreach ($used_numbers as $num) {
        if ($num > $prev + 1) {
            return $prev + 1;
        }
        $prev = $num;
    }
    
    // If no gaps found, return next number in sequence
    return max($used_numbers) + 1;
}
?>