<?php
require_once 'config/database.php';
require_once 'config/mail_config.php';
initSecureSession();

if (!isset($_SESSION['temp_user_id'])) {
    header("Location: register.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("CSRF validation error.");
    }

    if (!checkRateLimit('otp_verify')) {
        $error = "Too many failed security code entry actions. Try again later.";
    } else {
        $otp = trim($_POST['otp'] ?? '');
        $userId = $_SESSION['temp_user_id'];

        $stmt = $pdo->prepare("SELECT * FROM otp_verification WHERE user_id = ? AND otp_code = ? AND purpose = 'registration' AND is_used = 0 AND expires_at > NOW()");
        $stmt->execute([$userId, $otp]);
        $record = $stmt->fetch();

        if ($record) {
            $stmt = $pdo->prepare("UPDATE otp_verification SET is_used = 1 WHERE id = ?");
            $stmt->execute([$record['id']]);

            $stmt = $pdo->prepare("UPDATE users SET email_verified = 1 WHERE id = ?");
            $stmt->execute([$userId]);

            unset($_SESSION['temp_user_id'], $_SESSION['temp_user_email'], $_SESSION['temp_user_name']);
            $_SESSION['success_message'] = "Your registration profile is now verified. You may sign in.";
            header("Location: login.php");
            exit;
        } else {
            $_SESSION['otp_verify_attempts']++;
            $error = "The verification code is invalid or expired.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Code Registration</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 450px;">
    <div class="card shadow">
        <div class="card-header bg-success text-white text-center"><h4>Verify Registration Profile</h4></div>
        <div class="card-body">
            <p class="text-center">Enter the security code issued to your registration email address.</p>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form action="verify-registration-otp.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="mb-3"><label>6-Digit Code</label><input type="text" name="otp" maxlength="6" class="form-control text-center fs-4" required></div>
                <button type="submit" class="btn btn-success w-100">Confirm Verification</button>
            </form>
            <div class="mt-3 text-center">
                <a href="resend-otp.php?action=registration" class="btn btn-sm btn-link">Resend Security Code</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>