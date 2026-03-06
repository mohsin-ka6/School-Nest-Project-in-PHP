  <?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Checks if a user is currently logged in.
 * @return bool True if logged in, false otherwise.
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirects to a specified URL.
 * @param string $url The URL to redirect to.
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Checks if the logged-in user has the required role.
 * If not, it redirects them to their dashboard or login page.
 * @param string|array $required_role The role(s) required to access the page.
 */
function check_role($required_role) {
    if (!is_logged_in()) {
        redirect(BASE_URL . '/auth/login.php');
    }

    $user_role = $_SESSION['role'];
    if (is_array($required_role)) {
        if (!in_array($user_role, $required_role)) {
            redirect(BASE_URL . '/index.php'); // Redirect to their own dashboard
        }
    } else {
        if ($user_role !== $required_role) {
            redirect(BASE_URL . '/index.php'); // Redirect to their own dashboard
        }
    }
}

/**
 * Sends an email using PHPMailer with dynamic SMTP settings based on branch context.
 * @param string $to_email The recipient's email address.
 * @param string $to_name The recipient's name.
 * @param string $subject The email subject.
 * @param string $body The HTML email body.
 * @param int|null $branch_id The branch context for sending the email. If null, uses global settings.
 * @param bool $force_global If true, forces use of global settings (e.g., for admin password resets).
 * @return bool True on success, sets session error message on failure.
 */
function send_email($to_email, $to_name, $subject, $body, $branch_id = null, $force_global = false) {
    global $db;
    
    // Fetch global settings as the default
    $stmt_global = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'smtp_%'");
    $config = $stmt_global->fetchAll(PDO::FETCH_KEY_PAIR);

    // If a branch context is given and we are not forcing global, check for custom settings
    if ($branch_id && !$force_global) {
        $stmt_branch = $db->prepare("SELECT * FROM branch_smtp_settings WHERE branch_id = ?");
        $stmt_branch->execute([$branch_id]);
        $branch_config = $stmt_branch->fetch();

        if ($branch_config && $branch_config['use_custom'] == 1) {
            // Override global config with branch-specific settings
            $config['smtp_host'] = $branch_config['smtp_host'];
            $config['smtp_user'] = $branch_config['smtp_user'];
            $config['smtp_pass'] = $branch_config['smtp_pass']; // This is already encrypted
            $config['smtp_port'] = $branch_config['smtp_port'];
            $config['smtp_secure'] = $branch_config['smtp_secure'];
            $config['smtp_from_email'] = $branch_config['smtp_from_email'];
            $config['smtp_from_name'] = $branch_config['smtp_from_name'];
        }
    }

    // Check if essential settings are present
    if (empty($config['smtp_host']) || empty($config['smtp_user'])) {
        $_SESSION['error_message'] = "Email cannot be sent. SMTP settings are not configured.";
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = $config['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['smtp_user'];
        $mail->Password   = $config['smtp_pass'] ?? '';
        $mail->SMTPSecure = $config['smtp_secure'];
        $mail->Port       = $config['smtp_port'];

        //Recipients
        $mail->setFrom($config['smtp_from_email'], $config['smtp_from_name'] ?? SITE_NAME);
        $mail->addAddress($to_email, $to_name);

        //Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        $_SESSION['error_message'] = "The email could not be sent. Mailer Error: {$mail->ErrorInfo}";
        return false;
    }
}

/**
 * Displays and clears flash messages (success or error) stored in the session.
 */
function display_flash_messages() {
    if (isset($_SESSION['success_message'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['success_message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['error_message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        unset($_SESSION['error_message']);
    }
}

/**
 * Secures an API endpoint by checking for login and role.
 * Responds with JSON and appropriate HTTP status codes on failure.
 * @param string|array|null $required_role The role(s) required. If null, just checks for login.
 */
function check_api_auth($required_role = null) {
    if (!is_logged_in()) {
        http_response_code(401); // Unauthorized
        echo json_encode(['error' => 'Authentication required. Please log in again.']);
        exit();
    }

    if ($required_role) {
        $user_role = $_SESSION['role'];
        $has_permission = is_array($required_role) ? in_array($user_role, $required_role) : ($user_role === $required_role);

        if (!$has_permission) {
            http_response_code(403); // Forbidden
            echo json_encode(['error' => 'You do not have permission to perform this action.']);
            exit();
        }
    }
}

/**
 * Generates a WhatsApp "Click to Chat" link.
 * @param string $phone The phone number in any format.
 * @param string $message The URL-encoded message to pre-fill.
 * @return string The full WhatsApp chat link.
 */
function generate_whatsapp_link($phone, $message = '') {
    // 1. Remove all non-numeric characters except '+'
    $phone = preg_replace('/[^0-9+]/', '', $phone);

    // 2. If it starts with '0', assume a local number and prepend a default country code (e.g., 92 for Pakistan).
    //    You should adjust '92' to your country's code.
    if (substr($phone, 0, 1) === '0') {
        $phone = '92' . substr($phone, 1);
    }

    // 3. Remove any leading '+'
    $phone = ltrim($phone, '+');

    return 'https://wa.me/' . $phone . '?text=' . urlencode($message);
}

/**
 * Sends an SMS using a local Android SMS Gateway app.
 * @param string $phone The recipient's phone number.
 * @param string $message The message to send.
 * @return array ['success' => bool, 'message' => string]
 */
function send_sms_via_gateway($phone, $message) {
    // These values must be configured based on your SMS Gateway App settings
    $gateway_host = $GLOBALS['settings_array']['sms_gateway_host'] ?? null; // e.g., '192.168.1.10:8080'
    $gateway_token = $GLOBALS['settings_array']['sms_gateway_token'] ?? null;

    if (empty($gateway_host) || empty($gateway_token)) {
        return ['success' => false, 'message' => 'SMS Gateway is not configured in settings.'];
    }

    // Prepare the data for the API request
    $postData = [
        'secret' => $gateway_token,
        'mode' => 'devices',
        'device' => '', // Leave empty to use the default device in the app
        'sim' => '',     // Leave empty to use the default SIM
        'priority' => 1,
        'phone' => $phone,
        'message' => $message
    ];

    // Build the final URL, ensuring it has a protocol.
    $url = $gateway_host;
    if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
        // If no protocol is specified, default to http.
        $url = 'http://' . $url;
    }

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "{$url}/send",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($postData),
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        return ['success' => false, 'message' => "cURL Error: " . $err];
    } else {
        if (empty($response)) {
            return ['success' => false, 'message' => 'Gateway Error: Received an empty response from the gateway. Please ensure the gateway app is running and accessible.'];
        }

        $response_data = json_decode($response, true);
        if (isset($response_data['status']) && $response_data['status'] === 'success') {
            return ['success' => true, 'message' => 'SMS sent to queue successfully.'];
        } else {
            // If JSON decoding fails or status is not success, return the raw response for debugging.
            return ['success' => false, 'message' => 'Gateway Error: ' . ($response_data['message'] ?? $response)];
        }
    }
}

/**
 * Returns the relative dashboard path based on user role.
 *
 * @param string $role The role of the user.
 * @return string The path to the dashboard.
 */
function get_dashboard_path_by_role($role) {
    $base_url = BASE_URL;
    switch ($role) {
        case 'superadmin':
            return $base_url . '/superadmin/dashboard.php';
        case 'branchadmin':
            return $base_url . '/branchadmin/dashboard.php';
        case 'teacher':
            return $base_url . '/teacher/dashboard.php';
        case 'student':
            return $base_url . '/student/dashboard.php';
        case 'parent':
            return $base_url . '/parent/dashboard.php';
        default:
            return $base_url . '/auth/login.php'; // Fallback to login
    }
}

/**
 * Gets the real IP address of the user, considering proxies.
 * @return string The user's IP address.
 */
function get_user_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ?: '0.0.0.0';
}
