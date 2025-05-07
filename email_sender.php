<?php
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmail($to, $subject, $body, $username, &$error = null) {
    $mail = new PHPMailer(true);
    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Use Gmail's SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'labacelemschool@gmail.com'; // Your Gmail address
        $mail->Password = 'hszkwyrssrcagdda'; // Your Gmail password or app password
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        // Email settings
        $mail->setFrom('labacelemschool@gmail.com', 'Labac Elementary System');
        $mail->addAddress($to);
        $mail->Subject = $subject;

        // Enhanced email body with username and OTP as the center of attraction
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <h2 style='color: #007bff;'>Dear $username,</h2>
                <p>We have received a request to log in to your admin account. To proceed, please use the following One-Time Password (OTP):</p>
                <div style='text-align: center; margin: 20px 0;'>
                    <span style='display: inline-block; font-size: 24px; font-weight: bold; color: #d9534f; padding: 10px 20px; border: 2px dashed #d9534f; border-radius: 8px;'>
                        Your OTP code is: $body
                    </span>
                </div>
                <p style='font-size: 14px; color: #555;'>Please note that this OTP is valid for a limited time and should not be shared with anyone.</p>
                <p>If you did not request this, please contact our support team immediately.</p>
                <p style='margin-top: 20px;'>Best regards,<br><strong>Labac System Team</strong></p>
                <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
                <p style='font-size: 12px; color: #999;'>This is an automated email. Please do not reply to this message.</p>
            </div>
        ";
        $mail->isHTML(true);

        $mail->send();
        return true;
    } catch (Exception $e) {
        $error = "Mailer Error: " . $mail->ErrorInfo;
        return false;
    }
}
?>
