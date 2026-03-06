<?php
$page_title = "Login";
require_once '../config.php';
require_once '../functions.php';

// If user is already logged in, redirect them to their dashboard
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? '';
    $dashboard_path = get_dashboard_path_by_role($role);
    redirect($dashboard_path);
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $errors[] = "Username/Email and Password are required.";
    }

    if (empty($errors)) {
        try {
            // Fetch user by username or email
            $stmt = $db->prepare("SELECT * FROM users WHERE username = :username OR email = :username");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            // Prepare to log the attempt
            $ip_address = get_user_ip();
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $log_stmt = $db->prepare(
                "INSERT INTO activity_log (user_id, username_attempt, ip_address, user_agent, action, details) 
                 VALUES (:user_id, :username_attempt, :ip_address, :user_agent, :action, :details)"
            );

            // Check if login notifications are enabled
            $send_login_email = ($settings_array['login_notifications'] ?? 0) == 1;

            if ($user && password_verify($password, $user['password'])) {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);

                // Password is correct, set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['branch_id'] = $user['branch_id']; // Can be null for superadmin
                $_SESSION['last_activity'] = time(); // Set initial activity time
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT']; // Bind session to user agent

                // Log successful login
                $log_stmt->execute([
                    ':user_id' => $user['id'],
                    ':username_attempt' => $username,
                    ':ip_address' => $ip_address,
                    ':user_agent' => $user_agent,
                    ':action' => 'login_success',
                    ':details' => 'User logged in successfully.'
                ]);

                // Send successful login email if enabled
                if ($send_login_email) {
                    $email_subject = "Successful Login to Your " . SITE_NAME . " Account";
                    $email_body = "Hello " . htmlspecialchars($user['full_name']) . ",<br><br>This is a notification that your account was successfully accessed.<br><br><b>Time:</b> " . date('Y-m-d H:i:s') . "<br><b>IP Address:</b> " . $ip_address . "<br><br>If this was not you, please contact the administration immediately.<br><br>Thank you,<br>" . SITE_NAME;
                    send_email($user['email'], $user['full_name'], $email_subject, $email_body, $user['branch_id']);
                    // We don't need to show the success/error message for this background task.
                    unset($_SESSION['success_message'], $_SESSION['error_message']);
                }

                // Redirect to the appropriate dashboard
                $dashboard_path = get_dashboard_path_by_role($user['role']);
                redirect($dashboard_path);

            } else {
                // Log failed login
                $log_stmt->execute([
                    ':user_id' => $user['id'] ?? null, // Log user_id if user was found but password was wrong
                    ':username_attempt' => $username,
                    ':ip_address' => $ip_address,
                    ':user_agent' => $user_agent,
                    ':action' => 'login_fail',
                    ':details' => 'Invalid username or password.'
                ]);

                // Send failed login email if enabled and the user exists
                if ($send_login_email && $user) {
                    $email_subject = "Security Alert: Failed Login Attempt to Your " . SITE_NAME . " Account";
                    $email_body = "Hello " . htmlspecialchars($user['full_name']) . ",<br><br>An attempt was made to log into your account with an incorrect password.<br><br><b>Time:</b> " . date('Y-m-d H:i:s') . "<br><b>IP Address:</b> " . $ip_address . "<br><br>If this was you, you can ignore this message. If you did not attempt to log in, please contact the administration immediately to secure your account.<br><br>Thank you,<br>" . SITE_NAME;
                    send_email($user['email'], $user['full_name'], $email_subject, $email_body, $user['branch_id']);
                    unset($_SESSION['success_message'], $_SESSION['error_message']);
                }
                $errors[] = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error. Please try again later.";
            // In a production environment, you would log this error instead of showing it.
            // error_log("Login Error: " . $e->getMessage());
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title . ' - ' . SITE_NAME; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>

<div class="container-fluid ps-md-0">
    <div class="row g-0">
        <div class="d-none d-md-flex col-md-4 col-lg-6 bg-image"></div>
        <div class="col-md-8 col-lg-6">
            <div class="login d-flex align-items-center py-5">
                <div class="container">
                    <div class="row">
                        <div class="col-md-9 col-lg-8 mx-auto">
                            <div class="text-center mb-4">
                                <?php if (SITE_LOGO && file_exists(ROOT_PATH . '/' . SITE_LOGO)): ?>
                                    <img src="<?php echo BASE_URL . '/' . SITE_LOGO; ?>" alt="Logo" style="max-height: 70px; margin-bottom: 1rem;">
                                <?php endif; ?>
                                <h3 class="login-heading mb-0"><?php echo SITE_NAME; ?></h3>
                                <p class="text-muted">Please sign in to continue</p>
                            </div>

                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php foreach ($errors as $error): ?>
                                        <p class="mb-0"><?php echo $error; ?></p>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($_SESSION['success_message'])): ?>
                                <div class="alert alert-success" role="alert">
                                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                                </div>
                            <?php endif; ?>

                            <form action="login.php" method="POST">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="username" name="username" placeholder="name@example.com" required autofocus>
                                    <label for="username">Email or Username</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                                    <label for="password">Password</label>
                                </div>

                                <div class="d-grid">
                                    <button class="btn btn-lg btn-primary btn-login text-uppercase fw-bold mb-2" type="submit">Sign in</button>
                                    <div class="text-center">
                                        <a class="small" href="forget_password.php">Forgot password?</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .login {
        min-height: 100vh;
    }

    .bg-image {
        background-image: url('https://images.unsplash.com/photo-1580582932707-520aed937b7b?auto=format&fit=crop&w=1932');
        background-size: cover;
        background-position: center;
        position: relative;
    }
    .bg-image::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(to bottom, rgba(29, 53, 87, 0.6), rgba(69, 123, 157, 0.8));
    }

    .login-heading {
        font-weight: 300;
    }

    .btn-login {
        font-size: 0.9rem;
        letter-spacing: 0.05rem;
        padding: 0.75rem 1rem;
    }

    .form-floating>label {
        font-size: .8rem;
        padding-top: .8rem;
        opacity: .65;
    }

    .form-floating>.form-control:focus~label,
    .form-floating>.form-control:not(:placeholder-shown)~label,
    .form-floating>.form-select~label {
        padding-top: .4rem;
        opacity: .65;
        transform: scale(.85) translateY(-.5rem) translateX(.15rem);
    }
</style>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>