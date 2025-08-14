<?php
include("../configuration/configuration.php");

$conn = new mysqli($servername, $username, $password, $database);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8 for proper Turkish character handling
$conn->set_charset("utf8");

require '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$id = $_GET['id'];

// Use prepared statement to prevent SQL injection
$stmt = $conn->prepare("SELECT * FROM form1 WHERE submission_number = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

// Configure Dompdf options for UTF-8 support
$options = new Options();
$options->set('defaultFont', 'DejaVu Sans'); // Font that supports Turkish characters
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);

ob_start();
require("Print_pdf.php");
$html = ob_get_contents();
ob_end_clean();

// Ensure HTML has proper UTF-8 encoding declaration
if (strpos($html, 'charset') === false) {
    $html = str_replace('<head>', '<head><meta charset="UTF-8">', $html);
}

$dompdf->loadHtml($html);

// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render the HTML as PDF
$dompdf->render();

$pdf = $dompdf->output();

// Set proper headers for UTF-8
header('Content-Type: application/pdf; charset=UTF-8');

// Output the generated PDF to the browser
$dompdf->stream('Print_pdf.pdf', ['Attachment' => false]);

$stmt->close();
$conn->close();
?>