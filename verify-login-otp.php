<?php
require_once 'config/database.php';
require_once 'config/mail_config.php';
initSecureSession();

if (!isset($_SESSION['login_step_id'])) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']); // Linisin ang temporary success alert

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }

    if (!checkRateLimit('login_otp_verify')) {
        $error = "Too many failed attempts. Account locked temporarily.";
    } else {
        $otp = trim($_POST['otp'] ?? '');
        $userId = $_SESSION['login_step_id'];

        // HOUSEKEEPING: Burahin agad ang LAHAT ng expired na OTP sa database para laging malinis
        $pdo->query("DELETE FROM otp_verification WHERE expires_at <= NOW()");

        // I-check kung may umiiral pa na OTP para sa user na ito
        $stmt = $pdo->prepare("SELECT * FROM otp_verification WHERE user_id = ? AND otp_code = ? AND purpose = 'login'");
        $stmt->execute([$userId, $otp]);
        $record = $stmt->fetch();

        if ($record) {
            // MATAGUMPAY! Burahin na agad ang OTP na ito para hindi na magamit muli
            $stmt = $pdo->prepare("DELETE FROM otp_verification WHERE id = ?");
            $stmt->execute([$record['id']]);

            // I-update ang huling login ng user
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$userId]);

            // Set official login session
            $_SESSION['authenticated_user_id'] = $userId;
            unset($_SESSION['login_step_id'], $_SESSION['login_step_email'], $_SESSION['login_step_name']);
            
            session_write_close(); 

            header("Location: dashboard.php");
            exit;
        } else {
            $_SESSION['login_otp_verify_attempts']++;
            $error = "Mali ang OTP code, o tuluyan na itong nag-expire at nabura sa database.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f6f9; }
        .otp-card { max-width: 400px; margin-top: 100px; border: none; border-radius: 12px; }
    </style>
</head>
<body>
<div class="container d-flex justify-content-center">
    <div class="card otp-card shadow w-100">
        <div class="card-header bg-primary text-white text-center p-3" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
            <h4 class="mb-0">🔒 2FA Verification</h4>
        </div>
        <div class="card-body p-4">
            <p class="text-muted text-center small">We have sent a new 6-digit verification code to your email for secure login.</p>
            
            <?php if ($success): ?>
                <div class="alert alert-success text-center py-2 small"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger text-center py-2 small"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form action="verify-login-otp.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="mb-4">
                    <label class="form-label d-block text-center fw-bold">Enter OTP Code</label>
                    <input type="text" name="otp" maxlength="6" class="form-control text-center fs-2 fw-bold tracking-widest text-primary" placeholder="000000" required autocomplete="off">
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Verify & Login</button>
            </form>
            <div class="mt-3 text-center">
                <a href="resend-otp.php" class="btn btn-sm btn-link text-decoration-none">Request New Token</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>