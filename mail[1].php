<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // ou require_once 'path/to/PHPMailer/autoload.php';

function sendNotificationEmail($to, $fullName, $subject, $bodyHtml, $bodyText = '') {
    $mail = new PHPMailer(true);

    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host = 'mail.final.digital';
        $mail->SMTPAuth = true;
        $mail->Username = 'forms@final.digital';
        $mail->Password = 'Forms1515!';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Use SSL
        $mail->Port = 465;

        // Email details
        $mail->setFrom('forms@final.digital', 'FIU RMS');
        $mail->addAddress($to, $fullName); // Recipient's email
        // $mail->addAddress('luc.banze@final.edu.tr', 'Luc Banze');

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;
        $mail->AltBody = $bodyText ?: strip_tags($bodyHtml);

        $mail->send();
    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}