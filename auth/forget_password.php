<?php
require_once '../config.php';
require_once '../functions.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'danger';
    } else {
        try {
            $stmt = $db->prepare("SELECT id, full_name FROM users WHERE email = :email AND status = 'active'");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user) {
                // Generate a unique, secure token
                $token = bin2hex(random_bytes(50));
                // Set an expiration date (e.g., 1 hour from now)
                $expires = date("Y-m-d H:i:s", time() + 3600);

                // Store the token and expiry in the database
                $update_stmt = $db->prepare("UPDATE users SET password_reset_token = :token, password_reset_expires = :expires WHERE id = :id");
                $update_stmt->execute(['token' => $token, 'expires' => $expires, 'id' => $user['id']]);

                // --- Send Email using the global send_email function ---
                $reset_link = BASE_URL . '/auth/reset_password.php?token=' . $token;
                $subject = 'Password Reset Request for ' . SITE_NAME;
                $body = "Hello,<br><br>You requested a password reset. Please click the link below to set a new password. This link is valid for 1 hour.<br><br><a href='{$reset_link}'>Reset Your Password</a><br><br>If you did not request this, please ignore this email.<br><br>Thank you,<br>" . SITE_NAME;
                
                // Use the global send_email function, forcing global settings for security
                if (send_email($email, $user['full_name'], $subject, $body, null, true)) {
                    $message = 'If an account with that email exists, a password reset link has been sent.';
                    $message_type = 'success';
                } else {
                    // The send_email function sets its own detailed error in the session,
                    // but we show a generic message to the user.
                    $message = "Message could not be sent. Please contact support.";
                    $message_type = 'danger';
                }
            } else {
                // For security, show the same message whether the user exists or not to prevent email enumeration
                $message = 'If an account with that email exists, a password reset link has been sent.';
                $message_type = 'success';
            }
        } catch (PDOException $e) {
            $message = "An error occurred. Please try again later.";
            $message_type = 'danger';
            // Optional: log the detailed error $e->getMessage()
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo SITE_NAME; ?></title>
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
                <i class="fas fa-key fa-3x text-primary"></i>
                <h2 class="mt-3">Forgot Password?</h2>
                <p class="text-muted">Enter your email and we'll send you a link to reset your password.</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form action="forget_password.php" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">Send Reset Link</button>
                </div>
            </form>
            <div class="text-center mt-3">
                <a href="login.php"><i class="fas fa-arrow-left me-1"></i> Back to Login</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>