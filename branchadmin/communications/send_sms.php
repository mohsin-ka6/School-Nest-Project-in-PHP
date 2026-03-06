<?php
$page_title = "Send Bulk SMS";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$errors = [];
$sent_to = [];

// Fetch classes and sections for dropdowns
$stmt_classes = $db->prepare("SELECT id, name FROM classes WHERE branch_id = ? ORDER BY numeric_name ASC");
$stmt_classes->execute([$branch_id]);
$classes = $stmt_classes->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $class_id = (int)($_POST['class_id'] ?? 0);
    $section_id = (int)($_POST['section_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if (empty($class_id) || empty($section_id)) {
        $errors[] = "Please select a class and section.";
    }
    if (empty($message)) {
        $errors[] = "Message cannot be empty.";
    }
    if (empty($settings_array['sms_gateway_host']) || empty($settings_array['sms_gateway_token'])) {
        $errors[] = "SMS Gateway is not configured. Please ask a Super Admin to configure it in General Settings.";
    }

    if (empty($errors)) {
        // Get current session
        $stmt_session = $db->prepare("SELECT id FROM academic_sessions WHERE branch_id = ? AND is_current = 1");
        $stmt_session->execute([$branch_id]);
        $current_session_id = $stmt_session->fetchColumn();

        if (!$current_session_id) {
            $errors[] = "No active academic session found for this branch.";
        } else {
            // Fetch parent phone numbers for the selected class/section
            $stmt_parents = $db->prepare("
                SELECT DISTINCT p.father_phone, u.full_name as student_name
                FROM student_enrollments se
                JOIN students s ON se.student_id = s.id
                JOIN users u ON s.user_id = u.id
                JOIN parents p ON s.parent_id = p.id
                WHERE se.session_id = ? AND se.class_id = ? AND se.section_id = ? AND p.father_phone IS NOT NULL AND p.father_phone != ''
            ");
            $stmt_parents->execute([$current_session_id, $class_id, $section_id]);
            $parents = $stmt_parents->fetchAll();

            if (empty($parents)) {
                $errors[] = "No parents with phone numbers found for the selected class/section.";
            } else {
                foreach ($parents as $parent) {
                    $result = send_sms_via_gateway($parent['father_phone'], $message);
                    if ($result['success']) {
                        $sent_to[] = "Successfully queued for " . htmlspecialchars($parent['student_name']) . "'s parent (" . htmlspecialchars($parent['father_phone']) . ").";
                    } else {
                        $errors[] = "Failed to send to " . htmlspecialchars($parent['student_name']) . "'s parent (" . htmlspecialchars($parent['father_phone']) . "): " . $result['message'];
                    }
                }
                if (!empty($sent_to)) {
                    $_SESSION['success_message'] = "SMS sending process initiated.";
                }
            }
        }
    }
}

require_once '../../header.php';
?>

<?php require_once '../../sidebar_branchadmin.php'; ?>
<?php require_once '../../navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/branchadmin/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Send SMS</li>
    </ol>

    <?php display_flash_messages(); ?>
    <?php if (!empty($errors)) echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; ?>
    <?php if (!empty($sent_to)) echo '<div class="alert alert-info"><strong>Sending Log:</strong><ul>' . implode('', array_map(fn($s) => "<li>$s</li>", $sent_to)) . '</ul></div>'; ?>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-paper-plane me-1"></i> Compose Message</div>
        <div class="card-body">
            <form action="" method="POST">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="class_id" class="form-label">Select Class*</label>
                        <select class="form-select" id="class_id" name="class_id" required>
                            <option value="">-- Select Class --</option>
                            <?php foreach ($classes as $class) echo "<option value='{$class['id']}'>" . htmlspecialchars($class['name']) . "</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="section_id" class="form-label">Select Section*</label>
                        <select class="form-select" id="section_id" name="section_id" required>
                            <option value="">-- Select Class First --</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="message" class="form-label">Message*</label>
                    <textarea class="form-control" id="message" name="message" rows="5" required maxlength="160"></textarea>
                    <div class="form-text">160 characters max per SMS.</div>
                </div>
                <button type="submit" class="btn btn-primary" <?php if (empty($settings_array['sms_gateway_host'])) echo 'disabled'; ?>>Send to Class</button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const classSelect = document.getElementById('class_id');
    const sectionSelect = document.getElementById('section_id');

    classSelect.addEventListener('change', function() {
        const classId = this.value;
        sectionSelect.innerHTML = '<option value="">Loading...</option>';

        if (!classId) {
            sectionSelect.innerHTML = '<option value="">-- Select Class First --</option>';
            return;
        }

        fetch(`<?php echo BASE_URL; ?>/api/get_sections.php?class_id=${classId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    sectionSelect.innerHTML = `<option value="">Error</option>`;
                    return;
                }
                let options = '<option value="">-- Select Section --</option>';
                data.forEach(section => {
                    options += `<option value="${section.id}">${section.name}</option>`;
                });
                sectionSelect.innerHTML = options;
            });
    });
});
</script>

<?php require_once '../../footer.php'; ?>
