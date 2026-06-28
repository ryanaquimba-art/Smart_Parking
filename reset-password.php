<?php
require_once 'config/database.php';
require_once 'config/mail_config.php';
initSecureSession();

if (!isset($_SESSION['reset_user_id'])) {
    header("Location: forgot-password.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("CSRF verification challenge exception encountered.");
    }

    $otp = trim($_POST['otp'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $userId = $_SESSION['reset_user_id'];

    if (empty($otp) || empty($newPassword)) {
        $error = "All fields must be completely filled out.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "The new passwords do not match.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM otp_verification WHERE user_id = ? AND otp_code = ? AND purpose = 'password_reset' AND is_used = 0 AND expires_at > NOW()");
        $stmt->execute([$userId, $otp]);
        $record = $stmt->fetch();

        if ($record) {
            // Update and clear token usage mapping records immediately
            $stmt = $pdo->prepare("UPDATE otp_verification SET is_used = 1 WHERE id = ?");
            $stmt->execute([$record['id']]);

            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$newHash, $userId]);

            unset($_SESSION['reset_user_id'], $_SESSION['reset_user_email'], $_SESSION['reset_user_name']);
            $_SESSION['success_message'] = "Password configuration updated successfully. Sign in with your new password.";
            header("Location: login.php");
            exit;
        } else {
            $error = "The password restoration code token presented is structurally inaccurate or expired.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Set New Access Password Configuration</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 450px;">
    <div class="card shadow">
        <div class="card-header bg-danger text-white text-center"><h4>Reset Password</h4></div>
        <div class="card-body">
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form action="reset-password.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="mb-3"><label>6-Digit Verification Code</label><input type="text" name="otp" class="form-control text-center" required></div>
                <div class="mb-3"><label>New Secure Password</label><input type="password" name="new_password" class="form-control" required></div>
                <div class="mb-3"><label>Confirm New Secure Password</label><input type="password" name="confirm_password" class="form-control" required></div>
                <button type="submit" class="btn btn-danger w-100">Update Profile Password</button>
            </form>
            <div class="mt-3 text-center">
                <a href="resend-otp.php?action=reset" class="btn btn-sm btn-link text-decoration-none">Resend Token Request Code</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>