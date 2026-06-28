<?php
require_once 'config/database.php';
require_once 'config/mail_config.php';
initSecureSession();

// Siguraduhing may session bago mag-resend
if (!isset($_SESSION['login_step_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['login_step_id'];
$email = $_SESSION['login_step_email'];
$username = $_SESSION['login_step_name'];

// HOUSEKEEPING: Burahin ang kahit anong lumang OTP na hindi nagamit o expired na ng user na ito
$stmt = $pdo->prepare("DELETE FROM otp_verification WHERE user_id = ? AND purpose = 'login'");
$stmt->execute([$userId]);

// Mag-generate ng panibagong OTP (Ito ay gagawa ng bagong malinis na record)
$newOtp = generateSecureOTP($userId, 'login', $pdo);

if (sendOTPMail($email, $username, $newOtp, 'Two-Factor Authentication Login (Resend)')) {
    $_SESSION['success_message'] = "Bagong OTP code ang matagumpay na ipinadala sa iyong email!";
} else {
    $_SESSION['success_message'] = "May error sa pagpapadala ng email, ngunit may bago kang code sa system.";
}

header("Location: verify-login-otp.php");
exit;
?>