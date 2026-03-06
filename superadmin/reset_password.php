<?php
require_once '../config.php';
require_once '../includes/functions.php';

$message = '';
$message_type = 'danger';
$token_valid = false;

if (!isset($_GET['token'])) {
    $message = 'No reset token provided. Please use the link sent to your email.';
} else {
    $token = $_GET['token'];

    try {
        $stmt = $db->prepare("SELECT id FROM users WHERE password_reset_token = :token AND password_reset_expires > NOW()");
        $stmt->execute([':token' => $token]);
        $user = $stmt->fetch();

        if ($user) {
            $token_valid = true;
            $user_id = $user['id'];
        } else {
            $message = 'This password reset token is invalid or has expired. Please request a new one.';
        }
    } catch (PDOException $e) {
        $message = 'A database error occurred.';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $token_valid) {
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if (empty($password) || empty($password_confirm)) {
        $message = 'Both password fields are required.';
    } elseif ($password !== $password_confirm) {
        $message = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters long.';
    } else {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $update_stmt = $db->prepare("UPDATE users SET password = :password, password_reset_token = NULL, password_reset_expires = NULL WHERE id = :id");
            $update_stmt->execute([
                ':password' => $hashed_password,
                ':id' => $user_id
            ]);

            // Redirect to login with a success message
            $_SESSION['success_message'] = "Your password has been reset successfully. Please log in.";
            redirect(BASE_URL . '/auth/login.php');

        } catch (PDOException $e) {
            $message = "An error occurred while updating your password.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .form-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .form-card { max-width: 500px; width: 100%; }
    </style>
</head>
<body>

<div class="form-container">
    <div class="card shadow-lg form-card">
        <div class="card-body p-5">
            <div class="text-center mb-4">
                <i class="fas fa-lock-open fa-3x text-primary"></i>
                <h2 class="mt-3">Set New Password</h2>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($token_valid): ?>
                <p class="text-muted text-center">Please enter and confirm your new password.</p>
                <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="password_confirm" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Reset Password</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="text-center">
                    <a href="forget_password.php" class="btn btn-secondary">Request a New Link</a>
                </div>
            <?php endif; ?>

            <div class="text-center mt-3">
                <a href="login.php"><i class="fas fa-arrow-left me-1"></i> Back to Login</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>