<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Uncomment these lines for testing:
// $to = "your-email@example.com";
// $fullName = "Your Name";
// $subject = "Test Email";
// $bodyHtml = "<h1>Test Email</h1><p>This is a test email sent using PHPMailer.</p>";
// sendEmail($to, $fullName, $subject, $bodyHtml);

function sendEmail($to, $fullName, $subject, $bodyHtml, $bodyText = '') {
    $mail = new PHPMailer(true);

    try {
        // Enable verbose debug output for testing (comment out in production)
        // $mail->SMTPDebug = 2; // Uncomment this line if you need to debug SMTP issues
        // $mail->Debugoutput = 'html';

        // SMTP settings
        $mail->isSMTP();
        $mail->Host = 'mail.final.digital';
        $mail->SMTPAuth = true;
        $mail->Username = 'forms@final.digital';
        $mail->Password = 'Forms1515!';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port = 465;
        
        // Timeout settings (helpful for slow servers)
        $mail->Timeout = 60;
        $mail->SMTPKeepAlive = true;

        // Email details
        $mail->setFrom('forms@final.digital', 'FIU Leave and absence');
        $mail->addAddress($to, $fullName); // Recipient's email
        
        // Optional: Add a reply-to address
        $mail->addReplyTo('forms@final.digital', 'FIU Leave and absence');

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $bodyHtml;
        $mail->AltBody = $bodyText ?: strip_tags($bodyHtml);
        
        // Set character encoding
        $mail->CharSet = 'UTF-8';

        $result = $mail->send();
        
        // Log successful emails (optional)
        error_log("Email sent successfully to: {$to} - Subject: {$subject}");
        
        return true;

    } catch (Exception $e) {
        // Enhanced error logging
        $errorMsg = "Email could not be sent to {$to}. Mailer Error: {$mail->ErrorInfo}";
        error_log($errorMsg);
        
        // For development/testing - uncomment the next line to see errors on screen
        // echo "Email Error: " . $mail->ErrorInfo . "<br>";
        
        return false;
    }
}

// Test function - uncomment to test email functionality
/*
function testEmail() {
    $to = "your-email@example.com"; // Replace with your email
    $fullName = "Test User";
    $subject = "Test Email from Leave Management System";
    $bodyHtml = "
        <h2>Test Email</h2>
        <p>If you receive this email, your email configuration is working correctly!</p>
        <p>This is a test from your Leave Management System.</p>
        <p>Time sent: " . date('Y-m-d H:i:s') . "</p>
    ";
    
    if (sendEmail($to, $fullName, $subject, $bodyHtml)) {
        echo "Test email sent successfully!";
    } else {
        echo "Failed to send test email. Check error logs.";
    }
}

// Uncomment the next line to test:
// testEmail();
*/
?>