<?php
require_once 'config/database.php';
require_once 'config/mail_config.php';
initSecureSession();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("CSRF verification failed.");
    }

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // I-check kung may umiiral nang username o email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $error = "Username or Email already exists. Please choose another.";
        } else {
            // I-hash ang password para secure
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // I-insert ang user (Awtomatikong verified ang email_verified = 1)
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, email_verified, created_at) VALUES (?, ?, ?, 1, NOW())");
            
            if ($stmt->execute([$username, $email, $hashedPassword])) {
                $_SESSION['success_message'] = "Account registered successfully! You can now log in.";
                header("Location: login.php");
                exit;
            } else {
                $error = "An error occurred while registering the account.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registration - Smart Parking</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 450px;">
    <div class="card shadow">
        <div class="card-header bg-primary text-white text-center"><h4>Create Account</h4></div>
        <div class="card-body">
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            
            <form action="register.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="mb-3"><label>Username</label><input type="text" name="username" class="form-control" required></div>
                <div class="mb-3"><label>Email Address</label><input type="email" name="email" class="form-control" required></div>
                <div class="mb-3"><label>Password</label><input type="password" name="password" class="form-control" required></div>
                <button type="submit" class="btn btn-primary w-100">Register</button>
            </form>
            <div class="mt-3 text-center">
                <a href="login.php" class="text-decoration-none">Already have an account? Login here</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>