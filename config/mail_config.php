<?php
// SMTP Mail Configuration Settings
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'angelo.m.serbo@isu.edu.ph'); 
define('SMTP_PASS', 'yeel ayfk zbqr mmhd');       
define('SMTP_ENCRYPTION', 'tls'); 
define('FROM_EMAIL', 'angelo.m.serbo@isu.edu.ph');
define('FROM_NAME', 'Arduino IoT Parking System');

// Helper function to send email using template layout
function sendOTPMail($toEmail, $userName, $otpCode, $purposeText) {
    require_once __DIR__ . '/../vendor/autoload.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($toEmail, $userName);

        $mail->isHTML(true);
        $mail->Subject = "Your Verification Code - " . $purposeText;
        
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f7; margin: 0; padding: 0; }
                .wrapper { width: 100%; table-layout: fixed; background-color: #f4f4f7; padding-bottom: 40px; }
                .main { background-color: #ffffff; margin: 0 auto; width: 100%; max-width: 600px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); overflow: hidden; }
                .header { background-color: #007bff; padding: 25px; text-align: center; color: white; font-size: 22px; font-weight: bold; }
                .content { padding: 30px; color: #333333; line-height: 1.6; }
                .otp-box { background-color: #f1f3f5; border: 2px dashed #007bff; border-radius: 6px; font-size: 32px; font-weight: bold; text-align: center; letter-spacing: 5px; padding: 15px; margin: 20px 0; color: #007bff; }
                .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #6c757d; border-top: 1px solid #dee2e6; }
            </style>
        </head>
        <body>
            <div class='wrapper'>
                <div class='main'>
                    <div class='header'>
                        ⚙️ Arduino Smart Parking System
                    </div>
                    <div class='content'>
                        <p>Hello <strong>" . htmlspecialchars($userName) . "</strong>,</p>
                        <p>You requested a code to verify your identity for <strong>" . htmlspecialchars($purposeText) . "</strong>.</p>
                        <div class='otp-box'>" . $otpCode . "</div>
                        <p>This verification code is valid for <strong>5 minutes</strong>. Do not share this code with anyone under any circumstances.</p>
                    </div>
                    <div class='footer'>
                        &copy; " . date('Y') . " Arduino IoT Systems. Secure Access Node.
                    </div>
                </div>
            </div>
        </body>
        </html>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Global function helper to safely initialize session tokens
function initSecureSession() {
    if (session_status() == PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 0); 
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

// Global rate limiter helper
function checkRateLimit($key, $maxAttempts = 5, $lockoutTime = 60) {
    if (!isset($_SESSION[$key . '_attempts'])) {
        $_SESSION[$key . '_attempts'] = 0;
        $_SESSION[$key . '_time'] = time();
    }
    if ($_SESSION[$key . '_attempts'] >= $maxAttempts) {
        if (time() - $_SESSION[$key . '_time'] < $lockoutTime) {
            return false;
        } else {
            $_SESSION[$key . '_attempts'] = 0;
            $_SESSION[$key . '_time'] = time();
        }
    }
    return true;
}


// Cryptographically safe OTP generator (Strictly deletes old records before inserting new ones)
function generateSecureOTP($userId, $purpose, $pdo) {
    $otp = (string)random_int(100000, 999999);
    
    // BURAHIN ang lahat ng lumang OTP ng user na ito para sa login bago magpasok ng bago
    $stmt = $pdo->prepare("DELETE FROM otp_verification WHERE user_id = ? AND purpose = ?");
    $stmt->execute([$userId, $purpose]);
    
    // Mag-record ng bago na may exact MySQL Time sync (+5 minutes)
    $stmt = $pdo->prepare("INSERT INTO otp_verification (user_id, otp_code, purpose, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE))");
    $stmt->execute([$userId, $otp, $purpose]);
    
    return $otp;
}
?>