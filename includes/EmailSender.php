<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Correct path fix  PHPMailer::ENCRYPTION_STARTTLS
require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

class EmailSender {

    private $mail;

    
    public function __construct() {
        $this->mail = new PHPMailer(true);

        // SMTP Settings (Gmail)
        $this->mail->isSMTP();
        $this->mail->Host       = 'smtp.gmail.com';
        $this->mail->SMTPAuth   = true;
        $this->mail->Username   = 'thefpvt@gmail.com';   // your email
        $this->mail->Password   = 'uasf cfmi znkp pmin'; // your app password
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port       = 587;

        $this->mail->setFrom('thefpvt@gmail.com', 'NSS Navneet');
    }

    public function sendEmail($to, $subject, $message, $isHTML = true) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to);
            $this->mail->isHTML($isHTML);
            $this->mail->Subject = $subject;
            $this->mail->Body    = $message;

            $this->mail->send();
            return ['success' => true, 'message' => 'Email sent successfully'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Mailer Error: ' . $this->mail->ErrorInfo];
        }
    }

    public function sendWelcomeEmail($name, $email, $id, $password = null) {
        $subject = "Welcome to NSS - Volunteer Registration Successful";
        $message = $this->getEmailTemplate("
            <h2 style='color: #333; margin-bottom: 20px;'>Welcome to NSS, $name! 👋</h2>
            
            <p style='font-size: 16px; color: #555; margin-bottom: 15px;'>
                We're thrilled to have you join our National Service Scheme (NSS) community!
            </p>
            
            <div style='background: #f5f5f5; padding: 20px; border-left: 4px solid #2196F3; border-radius: 4px; margin: 20px 0;'>
                <p style='margin: 5px 0; font-size: 14px;'>
                    <strong style='color: #333;'>Your Volunteer ID:</strong><br>
                    <span style='font-size: 18px; color: #2196F3; font-weight: bold;'>$id</span>
                </p>
            </div>
            
            <p style='font-size: 14px; color: #777; margin-top: 20px;'>
                Please use your Volunteer ID to login to the NSS portal. You can now browse events, register for activities, and track your service hours.
            </p>
            
            <p style='font-size: 14px; color: #555; margin-top: 20px;'>
                If you have any questions, feel free to reach out to us.
            </p>
        ");
        return $this->sendEmail($email, $subject, $message);
    }

    private function getMailer()
{
    $mail = new PHPMailer(true);

    try {
 
    $mail->SMTPDebug = 2;
$mail->Debugoutput = 'html';

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        $mail->Username = 'thefpvt@gmail.com'; 
        $mail->Password = 'uasf cfmi znkp pmin';

        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('yourgmail@gmail.com', 'NAVNEET NSS');

        return $mail;

    } catch (Exception $e) {
        die("Mailer Error: " . $e->getMessage());
    }
}

    private function getEmailTemplate($content) {
        return "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>NSS Email</title>
        </head>
        <body style='margin: 0; padding: 0; font-family: \"Segoe UI\", Tahoma, Geneva, Verdana, sans-serif; background-color: #f9f9f9;'>
            
            <div style='max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); overflow: hidden;'>
                
                <!-- Header -->
                <div style='background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%); padding: 30px 20px; text-align: center; color: white;'>
                    <h1 style='margin: 0; font-size: 28px; font-weight: 600;'>NSS Portal</h1>
                    <p style='margin: 8px 0 0 0; font-size: 14px; opacity: 0.9;'>National Service Scheme</p>
                </div>
                
                <!-- Content -->
                <div style='padding: 40px 30px; color: #333;'>
                    $content
                </div>
                
                <!-- Footer -->
                <div style='background-color: #f5f5f5; padding: 20px 30px; border-top: 1px solid #e0e0e0; text-align: center; font-size: 12px; color: #777;'>
                    <p style='margin: 0 0 8px 0;'>
                        <strong>NSS Unit - Navneet</strong>
                    </p>
                    <p style='margin: 0;'>
                        This is an automated email. Please do not reply to this message.
                    </p>
                    <p style='margin: 8px 0 0 0; font-size: 11px;'>
                        © 2024-2025 NSS. All rights reserved.
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    public function sendCertificateEmail($name, $email, $type, $code, $pdf_path) {

    $subject = "Congratulations! Your NSS Certificate is Ready";

    if($type == "120_hours"){
        $certType = "120 Hours NSS Service";
    } else {
        $certType = "240 Hours NSS Service";
    }

    $message = $this->getEmailTemplate("
        <h2 style='color: #2196F3; margin-bottom: 10px;'>Congratulations, $name! 🎉</h2>
        <p style='color: #666; font-size: 16px; margin-bottom: 20px;'>
            You have successfully completed the <strong>$certType</strong> milestone!
        </p>
        
        <div style='background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); padding: 25px; border-radius: 8px; margin: 25px 0; border-left: 5px solid #2196F3;'>
            <p style='margin: 0 0 15px 0; font-size: 14px; color: #555;'>
                <strong>Certificate Details:</strong>
            </p>
            <div style='background: white; padding: 15px; border-radius: 4px; margin-bottom: 10px;'>
                <p style='margin: 5px 0; font-size: 14px;'>
                    <strong>Type:</strong> $certType
                </p>
                <p style='margin: 5px 0; font-size: 14px;'>
                    <strong>Certificate Code:</strong> <span style='color: #2196F3; font-weight: bold;'>$code</span>
                </p>
            </div>
        </div>
        
        <p style='font-size: 14px; color: #555; margin-bottom: 15px;'>
            Your certificate has been attached to this email. You can download it and use it for your records.
        </p>
        
        <p style='font-size: 14px; color: #777; margin-top: 25px;'>
            Thank you for your dedicated service to the community. Your contribution makes a difference! 💪
        </p>
    ");

    $mail = $this->getMailer();

    try {

        $mail->addAddress($email);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->isHTML(true);

        // Attach certificate
        $mail->addAttachment($pdf_path);

        $mail->send();

        return ["success" => true];

    } catch (Exception $e) {
        return ["success" => false];
    }
}   public function sendPasswordResetEmail($name, $email, $token) {

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];

    // ✅ Your corrected folder name
    $projectFolder = "2%20FINAL";

    $link = "$protocol://$host/$projectFolder/reset_password.php?token=$token";

    $subject = "Reset Your NSS Password";
    
    $message = $this->getEmailTemplate("
        <h2 style='color: #2196F3; margin-bottom: 10px;'>Password Reset Request</h2>
        
        <p style='font-size: 15px; color: #555; margin-bottom: 20px;'>
            Hello <strong>$name</strong>,
        </p>
        
        <p style='font-size: 15px; color: #555; margin-bottom: 20px;'>
            We received a request to reset your NSS portal password. If you made this request, click the button below to reset your password.
        </p>
        
        <div style='margin: 30px 0;'>
            <a href='$link' style='display: inline-block; background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%); color: white; padding: 12px 40px; text-decoration: none; border-radius: 4px; font-weight: 600; font-size: 15px;'>
                Reset Password
            </a>
        </div>
        
        <p style='font-size: 13px; color: #999; margin-top: 25px;'>
            Or copy and paste this link in your browser:
        </p>
        <p style='font-size: 12px; background: #f5f5f5; padding: 10px; border-radius: 4px; word-break: break-all; color: #666;'>
            $link
        </p>
        
        <p style='font-size: 13px; color: #d32f2f; margin-top: 25px;'>
            <strong>⚠️ Note:</strong> This link will expire in 24 hours for security reasons.
        </p>
        
        <p style='font-size: 13px; color: #777; margin-top: 20px;'>
            If you didn't request a password reset, please ignore this email. Your account is safe.
        </p>
    ");

    return $this->sendEmail($email, $subject, $message);
}
  /* ✅ NEW FUNCTION FOR EVENT NOTIFICATION */
    public function sendNewEventNotificationEmail($name, $email, $eventTitle, $eventDate, $location) {

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $projectFolder = "2%20FINAL";
        
        // Link to register page - user will find event there
        $registerLink = "$protocol://$host/$projectFolder/volunteer/register_event.php";

        $subject = "New NSS Event - Register Now! 🎯";

        $message = $this->getEmailTemplate("
            <h2 style='color: #2196F3; margin-bottom: 10px;'>New NSS Event Available! 🚀</h2>
            
            <p style='font-size: 15px; color: #555; margin-bottom: 25px;'>
                Hello <strong>$name</strong>,
            </p>
            
            <p style='font-size: 15px; color: #555; margin-bottom: 20px;'>
                A new exciting NSS event has been added! Join us and make a difference in the community.
            </p>
            
            <div style='background: linear-gradient(135deg, #f5f5f5 0%, #eeeeee 100%); padding: 25px; border-radius: 8px; margin: 25px 0; border-left: 5px solid #4CAF50;'>
                <p style='margin: 0 0 15px 0; font-size: 14px; color: #333;'>
                    <strong>Event Details:</strong>
                </p>
                <div style='background: white; padding: 15px; border-radius: 4px;'>
                    <p style='margin: 10px 0; font-size: 14px;'>
                        <strong style='color: #2196F3;'>📌 Event Name:</strong> $eventTitle
                    </p>
                    <p style='margin: 10px 0; font-size: 14px;'>
                        <strong style='color: #2196F3;'>📅 Date:</strong> $eventDate
                    </p>
                    <p style='margin: 10px 0; font-size: 14px;'>
                        <strong style='color: #2196F3;'>📍 Location:</strong> $location
                    </p>
                </div>
            </div>
            
            <p style='font-size: 15px; color: #555; margin: 25px 0; text-align: center;'>
                <strong>Register now to confirm your participation and earn service hours!</strong>
            </p>
            
            <div style='margin: 30px 0; text-align: center;'>
                <a href='$registerLink' style='display: inline-block; background: linear-gradient(135deg, #4CAF50 0%, #388E3C 100%); color: white; padding: 14px 50px; text-decoration: none; border-radius: 4px; font-weight: 600; font-size: 16px; box-shadow: 0 2px 5px rgba(76, 175, 80, 0.3);'>
                    Register for Event
                </a>
            </div>
            
            <p style='font-size: 13px; color: #777; margin-top: 25px; text-align: center;'>
                or visit our portal at: <a href='$registerLink' style='color: #2196F3; text-decoration: none;'>Register Page</a>
            </p>
            
            <p style='font-size: 14px; color: #555; margin-top: 25px;'>
                Limited seats available! Register early to secure your spot. See you there! 🙌
            </p>
        ");

        return $this->sendEmail($email, $subject, $message);
    }
    
    /* ✅ EVENT REGISTRATION CONFIRMATION EMAIL */
public function sendEventRegistrationEmail($name, $email, $eventTitle, $eventDate, $location) {

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $projectFolder = "2%20FINAL";
    
    $dashboardLink = "$protocol://$host/$projectFolder/volunteer/dashboard.php";

    $subject = "Event Registration Confirmed! ✅";

    $message = $this->getEmailTemplate("
        <h2 style='color: #4CAF50; margin-bottom: 10px;'>Registration Confirmed! ✅</h2>
        
        <p style='font-size: 15px; color: #555; margin-bottom: 20px;'>
            Hello <strong>$name</strong>,
        </p>
        
        <p style='font-size: 15px; color: #555; margin-bottom: 25px;'>
            Great news! You have successfully registered for the following NSS event. Your participation will contribute valuable service hours to your volunteer record.
        </p>

        <div style='background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); padding: 25px; border-radius: 8px; margin: 25px 0; border-left: 5px solid #4CAF50;'>
            <p style='margin: 0 0 15px 0; font-size: 14px; color: #333;'>
                <strong>Your Event:</strong>
            </p>
            <div style='background: white; padding: 15px; border-radius: 4px;'>
                <p style='margin: 10px 0; font-size: 14px;'>
                    <strong style='color: #2196F3;'>📌 Event:</strong> $eventTitle
                </p>
                <p style='margin: 10px 0; font-size: 14px;'>
                    <strong style='color: #2196F3;'>📅 Date:</strong> $eventDate
                </p>
                <p style='margin: 10px 0; font-size: 14px;'>
                    <strong style='color: #2196F3;'>📍 Location:</strong> $location
                </p>
            </div>
        </div>

        <p style='font-size: 14px; color: #555; margin: 20px 0;'>
            <strong>Important Reminders:</strong>
        </p>
        <ul style='font-size: 14px; color: #555; margin: 10px 0; padding-left: 20px;'>
            <li style='margin: 8px 0;'>Please arrive on time or a few minutes early</li>
            <li style='margin: 8px 0;'>Bring any required documents or identification</li>
            <li style='margin: 8px 0;'>Attendance will be marked for service hours credit</li>
        </ul>

        <div style='margin: 30px 0; text-align: center;'>
            <a href='$dashboardLink' style='display: inline-block; background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%); color: white; padding: 12px 40px; text-decoration: none; border-radius: 4px; font-weight: 600; font-size: 15px;'>
                Go to Dashboard
            </a>
        </div>

        <p style='font-size: 13px; color: #777; margin-top: 25px;'>
            Thank you for your commitment to community service! We look forward to seeing you at the event. 🙌
        </p>
    ");

    return $this->sendEmail($email, $subject, $message);
}
}
?>
