<?php
require_once 'config/database.php';
require_once 'config/mail_config.php';
initSecureSession();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("CSRF verification failed.");
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Kung tama ang password
    if ($user && password_verify($password, $user['password'])) {
        
        // I-set ang temporary session para sa login step
        $_SESSION['login_step_id'] = $user['id'];
        $_SESSION['login_step_email'] = $user['email'];
        $_SESSION['login_step_name'] = $user['username'];

        // Mag-generate at mag-send ng Secure OTP para sa LOGIN lang
        $otp = generateSecureOTP($user['id'], 'login', $pdo);
        
        if (sendOTPMail($user['email'], $user['username'], $otp, 'Two-Factor Authentication Login')) {
            header("Location: verify-login-otp.php");
            exit;
        } else {
            $error = "Hindi maipadala ang OTP sa iyong email. I-check ang iyong internet o mail config.";
        }
    } else {
        $error = "Mali ang Username o Password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Smart Parking System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 450px;">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>
    <div class="card shadow">
        <div class="card-header bg-dark text-white text-center"><h4>Control Panel Access</h4></div>
        <div class="card-body">
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            
            <form action="login.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="mb-3"><label>Username</label><input type="text" name="username" class="form-control" required></div>
                <div class="mb-3"><label>Password</label><input type="password" name="password" class="form-control" required></div>
                <button type="submit" class="btn btn-dark w-100">Login</button>
            </form>
            <div class="mt-3 text-center">
                <a href="register.php" class="text-decoration-none">Don't have an account? Register here</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>