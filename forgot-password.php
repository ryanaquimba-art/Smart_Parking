<?php
require_once 'config/database.php';
require_once 'config/mail_config.php';
initSecureSession();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("CSRF Verification failure.");
    }

    $email = trim($_POST['email'] ?? '');
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please state a functional structural standard email target endpoint.";
    } else {
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['reset_user_id'] = $user['id'];
            $_SESSION['reset_user_email'] = $email;
            $_SESSION['reset_user_name'] = $user['username'];

            $otp = generateSecureOTP($user['id'], 'password_reset', $pdo);
            sendOTPMail($email, $user['username'], $otp, 'Password Reset Code Recovery Request Token');
            header("Location: reset-password.php");
            exit;
        } else {
            // Mitigate account enumeration: return an implicit safe message even if the target record doesn't exist
            $success = "If the data entry structural pattern matches records, an authorized code verification token will arrive shortly.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Password Restoration Gateway</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 450px;">
    <div class="card shadow">
        <div class="card-header bg-warning text-dark text-center"><h4>Account Recovery Entry</h4></div>
        <div class="card-body">
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <form action="forgot-password.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="mb-3"><label>Registered Email Address</label><input type="email" name="email" class="form-control" required></div>
                <button type="submit" class="btn btn-warning w-100">Send Recovery Code</button>
            </form>
            <div class="mt-3 text-center"><a href="login.php" class="text-decoration-none">Return to Entry View Gate</a></div>
        </div>
    </div>
</div>
</body>
</html>