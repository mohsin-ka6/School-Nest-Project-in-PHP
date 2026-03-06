<?php
$page_title = "ID Card Generator";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$errors = [];
$results = [];
$card_html_output = '';

// Fetch current settings for colors
$stmt_settings = $db->query("SELECT setting_key, setting_value FROM settings");
$settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);

// --- Fetch data for filter dropdowns ---
$stmt_sessions = $db->prepare("SELECT id, name FROM academic_sessions WHERE branch_id = ? ORDER BY start_date DESC");
$stmt_sessions->execute([$branch_id]);
$sessions = $stmt_sessions->fetchAll();

$stmt_classes = $db->prepare("SELECT id, name FROM classes WHERE branch_id = ? ORDER BY numeric_name ASC");
$stmt_classes->execute([$branch_id]);
$classes = $stmt_classes->fetchAll();

// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_cards'])) {
    $card_type = $_POST['card_type'] ?? '';
    $template = $_POST['template'] ?? '';

    // --- Handle Color Settings Update ---
    if (isset($_POST['card_bg_color'])) {
        $settings_to_save = [
            'card_bg_color' => $_POST['card_bg_color'],
            'card_text_color' => $_POST['card_text_color'],
            'card_header_bg_color' => $_POST['card_header_bg_color'],
            'card_bg_opacity' => $_POST['card_bg_opacity'],
            'card_header_bg_opacity' => $_POST['card_header_bg_opacity'],
            'card_photo_shape' => $_POST['card_photo_shape'] ?? 'round',
        ];
        try {
            $stmt_color = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            foreach ($settings_to_save as $key => $value) {
                $stmt_color->execute([$key, $value]);
            }
            // Refresh settings after update
            $settings = array_merge($settings, $settings_to_save);
            $_SESSION['success_message'] = "Design scheme saved!";
        } catch (PDOException $e) {
            $errors[] = "Database error saving settings: " . $e->getMessage();
        }
    }
    
    switch ($card_type) {
        case 'student':
            $session_id = (int)($_POST['session_id'] ?? 0);
            $class_id = (int)($_POST['class_id'] ?? 0);
            $section_id = (int)($_POST['section_id'] ?? 0);

            if ($session_id && $class_id && $section_id) {
                $stmt = $db->prepare("
                    SELECT 
                        s.id as student_id, u.full_name, s.photo, s.admission_no, s.branch_id,
                        p.father_name, p.father_phone,
                        c.name as class_name, sec.name as section_name, se.roll_no,
                        b.name as branch_name, sess.name as session_name
                    FROM student_enrollments se
                    JOIN students s ON se.student_id = s.id
                    JOIN users u ON s.user_id = u.id
                    JOIN classes c ON se.class_id = c.id
                    JOIN sections sec ON se.section_id = sec.id
                    JOIN academic_sessions sess ON se.session_id = sess.id
                    JOIN branches b ON s.branch_id = b.id
                    LEFT JOIN parents p ON s.parent_id = p.id
                    WHERE se.session_id = ? AND se.class_id = ? AND se.section_id = ? AND s.branch_id = ?
                    ORDER BY se.roll_no, u.full_name
                ");
                $stmt->execute([$session_id, $class_id, $section_id, $branch_id]);
                $results = $stmt->fetchAll();
            } else {
                $errors[] = "Please select Session, Class, and Section for student cards.";
            }
            break;

        case 'staff':
            $staff_status = $_POST['staff_status'] ?? 'active';

            $sql = "
                SELECT 
                    u.id as staff_id, u.full_name, u.email, u.status, u.branch_id,
                    t.photo, t.cnic, t.joining_date,
                    b.name as branch_name, b.phone as branch_phone
                FROM users u
                JOIN teachers t ON u.id = t.user_id
                JOIN branches b ON u.branch_id = b.id
                WHERE u.branch_id = :branch_id AND u.role = 'teacher'
            ";
            $params = [':branch_id' => $branch_id];

            if ($staff_status !== 'all') {
                $sql .= " AND u.status = :status";
                $params[':status'] = $staff_status;
            }
            $stmt = $db->prepare($sql . " ORDER BY u.full_name");
            $stmt->execute($params);
            $results = $stmt->fetchAll();
            break;

        case 'parent':
            $errors[] = "Parent ID card generation is not yet implemented.";
            // Placeholder for parent logic
            break;

        default:
            $errors[] = "Please select a valid card type.";
            break;
    }
}

require_once '../../header.php';
?>

<style>
    /* General ID Card Styles */
    .id-card-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); 
        gap: 20px; 
        justify-content: center;
    }
    .id-card { border: 1px solid #ccc; background: #fff; color: #000; font-family: Arial, sans-serif; page-break-inside: avoid; }
    .id-card-header { text-align: center; padding: 10px; }
    .id-card-header img.logo { height: 40px; }
    .id-card-header h6, .id-card-header p { margin: 0; font-weight: bold; }
    .id-card-body { padding: 10px; }
    .id-card-photo { text-align: center; margin-bottom: 10px; }
    .id-card-photo img { width: 90px; height: 110px; object-fit: cover; border: 2px solid #eee; }
    .id-card-details { font-size: 0.8rem; }
    .id-card-details p { margin-bottom: 4px; }
    .id-card-details strong { color: #333; }
    .id-card-footer { text-align: center; padding: 5px; }
    .id-card-footer img.barcode { height: 35px; }

    /* Template 1: Student Portrait */
    .id-card.student-portrait { width: 250px; height: 400px; }
    .id-card.student-portrait .id-card-header { background-color: #0d6efd; color: #fff; }

    /* Shared landscape styles */
    .id-card.student-landscape, .id-card.staff-landscape {
    /* Template 2: Student Landscape (New Design) */
        position: relative;
        width: 3.7in;
        height: 2.2in;
        padding: 7.7px;
        margin: 10px;
        background-size: cover;
        background-position: center;
        font-family: 'Arial', sans-serif;
        text-transform: uppercase;
        font-weight: bold;
        box-sizing: border-box;
        overflow: hidden; /* Needed for pseudo-element positioning */
    }
    .id-card.student-landscape .card-top-header, .id-card.staff-landscape .card-top-header {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        padding: 5px 10px;
        color: #fff; /* Default text color for header, can be overridden */
        z-index: 3;
        line-height: 1.2;
        display: flex;
        align-items: center;
        gap: 8px;
        box-sizing: border-box;
    }
    .id-card.student-landscape .card-top-header .header-logo, .id-card.staff-landscape .card-top-header .header-logo {
        height: 30px;
        width: 30px;
        flex-shrink: 0;
    }
    .id-card.student-landscape .card-top-header .header-text, .id-card.staff-landscape .card-top-header .header-text {
        text-align: left;
    }
    .id-card.student-landscape .card-top-header .school-main-name, .id-card.staff-landscape .card-top-header .school-main-name { font-weight: bold; font-size: 10px; }
    .id-card.student-landscape .card-top-header .school-branch-name, .id-card.staff-landscape .card-top-header .school-branch-name { font-size: 8px; opacity: 0.9; }

    .id-card.student-landscape::before, .id-card.staff-landscape::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: var(--bg-logo-url);
        background-size: contain; /* Scales the image to fit without cropping */
        background-position: center;
        background-repeat: no-repeat;
        opacity: 0.3; /* 30% opacity */
        z-index: 1; /* Places the logo behind the content */
    }
    .id-card.student-landscape .card-content-wrapper, .id-card.staff-landscape .card-content-wrapper {
        width: 100%;
        height: 100%;
        display: flex;
        position: relative; /* Ensure content is on top of the pseudo-element */
        z-index: 2;
    }
    .id-card.student-landscape .left-section, .id-card.staff-landscape .left-section {
        width: 34%;
        position: relative;
    }
    .id-card.student-landscape .circular-image, .id-card.staff-landscape .circular-image {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        margin-top: 32px;
        margin-left: 33px;
        border: 2px solid #fff;
    }
    .id-card.student-landscape .photo-shape-round, .id-card.staff-landscape .photo-shape-round {
        border-radius: 50%;
    }
    .id-card.student-landscape .photo-shape-box, .id-card.staff-landscape .photo-shape-box {
        border-radius: 8px;
    }
    .id-card.student-landscape .qr-image, .id-card.staff-landscape .qr-image {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 65px;
        height: 60px;
        margin: 17px;
    }
    .id-card.student-landscape .right-section, .id-card.staff-landscape .right-section {
        width: 66%;
        margin-top: 45px; /* Pushed down to clear the new header */
        padding-left: 5px;
    }
    .id-card.student-landscape .details-grid, .id-card.staff-landscape .details-grid {
        width: 100%;
        display: flex;
        justify-content: space-between;
        line-height: 1.8; /* Adjusted line-height for better fit */
        margin-top: 10px; /* Reduced top margin */
        margin-left: 3px;
        font-size: 8px;
    }
    .id-card.student-landscape .details-labels, .id-card.student-landscape .details-values, .id-card.staff-landscape .details-labels, .id-card.staff-landscape .details-values {
        width: 50%;
        white-space: nowrap;
    }
    .id-card.student-landscape .barcode-container, .id-card.staff-landscape .barcode-container {
        margin-top: 8px;
        text-align: center;
    }
    .id-card.student-landscape .barcode-image, .id-card.staff-landscape .barcode-image {
        height: 30px;
        width: 80%;
    }
    .id-card.student-landscape .principal-signature, .id-card.staff-landscape .principal-signature {
        width: 45px;
        height: 30px;
        float: right;
        margin-top: 10px;
        margin-right: 13px;
    }

    @media print {
        body * { visibility: hidden; }
        .print-area, .print-area * { visibility: visible; }
        .print-area { position: absolute; left: 0; top: 0; width: 100%; }
        .no-print { display: none; }
        .id-card-grid { display: flex; flex-wrap: wrap; }
    }
</style>

<?php require_once ROOT_PATH . '/sidebar_branchadmin.php'; ?>
<?php require_once ROOT_PATH . '/navbar.php'; ?>

<div class="container-fluid px-4">

    <?php display_flash_messages(); ?>
    <?php if (!empty($errors)) echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; ?>

    <?php
    // Helper function to convert hex to rgba
    function hex2rgba($color, $opacity = 1) {
        $color = ltrim($color, '#');
        if (strlen($color) == 3) {
            $r = hexdec(substr($color, 0, 1) . substr($color, 0, 1));
            $g = hexdec(substr($color, 1, 1) . substr($color, 1, 1));
            $b = hexdec(substr($color, 2, 1) . substr($color, 2, 1));
        } else {
            $r = hexdec(substr($color, 0, 2));
            $g = hexdec(substr($color, 2, 2));
            $b = hexdec(substr($color, 4, 2));
        }
        return "rgba($r, $g, $b, $opacity)";
    }
    ?>

    <div class="card mb-4 no-print">
        <div class="card-header"><i class="fas fa-id-card me-1"></i> Generate ID Cards</div>
        <div class="card-body">
            <form action="" method="POST" id="generator-form">
                <!-- Step 1: Card Type -->
                <div class="mb-3">
                    <label class="form-label"><strong>Step 1: Select Card Type</strong></label>
                    <select name="card_type" id="card_type" class="form-select" required>
                        <option value="">-- Select --</option>
                        <option value="student" <?php echo (($_POST['card_type'] ?? '') == 'student' ? 'selected' : ''); ?>>Student</option>
                        <option value="staff" <?php echo (($_POST['card_type'] ?? '') == 'staff' ? 'selected' : ''); ?>>Staff</option>
                        <option value="parent" <?php echo (($_POST['card_type'] ?? '') == 'parent' ? 'selected' : ''); ?>>Parent</option>
                    </select>
                </div>

                <!-- Step 2: Filters (Dynamic) -->
                <div id="filters-container" class="mb-3" style="display: none;">
                    <label class="form-label"><strong>Step 2: Apply Filters</strong></label>
                    <!-- Student Filters -->
                    <div id="student-filters" class="filter-group p-3 border rounded" style="display: none;">
                        <div class="row">
                            <div class="col-md-4"><label>Session</label><select name="session_id" class="form-select"><option value="">-- Select --</option><?php foreach ($sessions as $session) echo "<option value='{$session['id']}'>" . htmlspecialchars($session['name']) . "</option>"; ?></select></div>
                            <div class="col-md-4"><label>Class</label><select name="class_id" id="class_id" class="form-select"><option value="">-- Select --</option><?php foreach ($classes as $class) echo "<option value='{$class['id']}'>" . htmlspecialchars($class['name']) . "</option>"; ?></select></div>
                            <div class="col-md-4"><label>Section</label><select name="section_id" id="section_id" class="form-select"><option value="">-- Select Class First --</option></select></div>
                        </div>
                    </div>
                    <!-- Staff Filters -->
                    <div id="staff-filters" class="filter-group p-3 border rounded" style="display: none;">
                        <div class="row">
                            <div class="col-md-4">
                                <label>Staff Status</label>
                                <select name="staff_status" class="form-select">
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="all">All</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <!-- Parent Filters -->
                    <div id="parent-filters" class="filter-group" style="display: none;"><p class="text-muted">Parent filters will appear here.</p></div>
                </div>

                <!-- Step 2.5: Color Customization -->
                <div id="color-container" class="mb-3" style="display: none;">
                    <label class="form-label"><strong>Design & Color Scheme (for Landscape Template)</strong></label>
                    <div class="p-3 border rounded">
                        <div class="row">
                            <div class="col-md-2">
                                <label for="card_bg_color" class="form-label">Background Color</label>
                                <input type="color" class="form-control form-control-color" id="card_bg_color" name="card_bg_color" value="<?php echo htmlspecialchars($settings['card_bg_color'] ?? '#f7a942'); ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="card_bg_opacity" class="form-label">BG Opacity: <span id="card_bg_opacity_value"><?php echo (float)($settings['card_bg_opacity'] ?? 1) * 100; ?></span>%</label>
                                <input type="range" class="form-range" id="card_bg_opacity" name="card_bg_opacity" min="0" max="1" step="0.01" value="<?php echo htmlspecialchars($settings['card_bg_opacity'] ?? '1'); ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="card_text_color" class="form-label">Text Color</label>
                                <input type="color" class="form-control form-control-color" id="card_text_color" name="card_text_color" value="<?php echo htmlspecialchars($settings['card_text_color'] ?? '#4b260d'); ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="card_header_bg_color" class="form-label">Header Bar Color</label>
                                <input type="color" class="form-control form-control-color" id="card_header_bg_color" name="card_header_bg_color" value="<?php echo htmlspecialchars($settings['card_header_bg_color'] ?? '#1d3557'); ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="card_header_bg_opacity" class="form-label">Header Opacity: <span id="card_header_bg_opacity_value"><?php echo (float)($settings['card_header_bg_opacity'] ?? 1) * 100; ?></span>%</label>
                                <input type="range" class="form-range" id="card_header_bg_opacity" name="card_header_bg_opacity" min="0" max="1" step="0.01" value="<?php echo htmlspecialchars($settings['card_header_bg_opacity'] ?? '1'); ?>">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <label class="form-label">Photo Shape</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="card_photo_shape" id="photo_shape_round" value="round" <?php echo (($settings['card_photo_shape'] ?? 'round') == 'round' ? 'checked' : ''); ?>>
                                        <label class="form-check-label" for="photo_shape_round">Round</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="card_photo_shape" id="photo_shape_box" value="box" <?php echo (($settings['card_photo_shape'] ?? 'round') == 'box' ? 'checked' : ''); ?>>
                                        <label class="form-check-label" for="photo_shape_box">Box</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Template Selection -->
                <div id="template-container" class="mb-3" style="display: none;">
                    <label class="form-label"><strong>Step 3: Choose a Template</strong></label>
                    <!-- Student Templates -->
                    <div id="student-templates" class="template-group" style="display: none;">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="template" id="student-portrait" value="student-portrait" checked>
                            <label class="form-check-label" for="student-portrait">Portrait</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="template" id="student-landscape" value="student-landscape">
                            <label class="form-check-label" for="student-landscape">Landscape</label>
                        </div>
                    </div>
                    <!-- Staff Templates -->
                    <div id="staff-templates" class="template-group" style="display: none;">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="template" id="staff-landscape" value="staff-landscape" checked>
                            <label class="form-check-label" for="staff-landscape">Landscape</label>
                        </div>
                        <!-- Add other staff templates here if needed -->
                    </div>
                    <!-- Parent Templates -->
                    <div id="parent-templates" class="template-group" style="display: none;"><p class="text-muted">Parent templates will appear here.</p></div>
                </div>

                <button type="submit" name="generate_cards" class="btn btn-primary">Generate Cards</button>
            </form>
        </div>
    </div>

    <?php if (!empty($results)): ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-id-card-alt me-1"></i> Generated Cards (<?php echo count($results); ?>)</span>
            <form method="POST" target="_blank" class="no-print" id="print-form">
                <input type="hidden" name="card_data" id="card_data_input">
                <button type="submit" id="print-fronts-btn" class="btn btn-success btn-sm">
                    <i class="fas fa-print me-1"></i> Preview Fronts
                </button>
                <button type="submit" id="print-backs-btn" class="btn btn-info btn-sm">
                    <i class="fas fa-print me-1"></i> Print Backs
                </button>
            </form>
        </div>
        <div class="card-body print-area">
            <div class="id-card-grid" id="card-grid-container">
                <?php foreach ($results as $person): ?>
                    <?php
                    // --- RENDER THE CARD BASED ON TEMPLATE ---
                    $card_bg_img_style = SITE_LOGO ? "--bg-logo-url: url('" . BASE_URL . '/' . SITE_LOGO . "');" : "";
                    $card_bg_color = "background-color: " . hex2rgba($settings['card_bg_color'] ?? '#f7a942', $settings['card_bg_opacity'] ?? 1) . ";";
                    $card_header_bg_color = "background-color: " . hex2rgba($settings['card_header_bg_color'] ?? '#1d3557', $settings['card_header_bg_opacity'] ?? 1) . ";";
                    $photo_shape_class = 'photo-shape-' . ($settings['card_photo_shape'] ?? 'round');
                    $photo_url = $person['photo'] ? BASE_URL . '/' . htmlspecialchars($person['photo']) : BASE_URL . '/assets/images/default_avatar.png';

                    // Default values
                    $barcode_data = $person['staff_id'] ?? $person['admission_no'] ?? '';
                    $barcode_url = 'https://barcode.tec-it.com/barcode.ashx?data=' . urlencode($barcode_data) . '&code=Code128&dpi=96';
                    
                    if ($card_type == 'student') {
                        $inquiry_url = BASE_URL . '/public/student/inq/student.php?branch_id=' . $person['branch_id'] . '&admission_no=' . urlencode($person['admission_no']);
                    } else { // For staff
                        $inquiry_url = BASE_URL . '/public/staff/profile.php?id=' . $person['staff_id']; // Example URL
                    }
                    $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=70x70&data=' . urlencode($inquiry_url);

                    if ($template == 'student-portrait'):
                    ?>
                        <div class="id-card student-portrait">
                            <div class="id-card-header">
                                <?php if (SITE_LOGO): ?><img src="<?php echo BASE_URL . '/' . SITE_LOGO; ?>" class="logo" alt="Logo"><?php endif; ?>
                                <h6><?php echo htmlspecialchars(SITE_NAME); ?></h6>
                                <p style="font-size: 0.7rem;"><?php echo htmlspecialchars($person['branch_name']); ?></p>
                            </div>
                            <div class="id-card-body">
                                <div class="id-card-photo">
                                    <img src="<?php echo $photo_url; ?>" alt="Student Photo">
                                </div>
                                <div class="id-card-details text-center">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($person['full_name']); ?></h5>
                                    <p><strong>Class:</strong> <?php echo htmlspecialchars($person['class_name'] . ' - ' . $person['section_name']); ?></p>
                                    <p><strong>Roll No:</strong> <?php echo htmlspecialchars($person['roll_no']); ?> | <strong>Adm No:</strong> <?php echo htmlspecialchars($person['admission_no']); ?></p>
                                    <p><strong>Father:</strong> <?php echo htmlspecialchars($person['father_name']); ?></p>
                                    <p><strong>Contact:</strong> <?php echo htmlspecialchars($person['father_phone'] ?? 'N/A'); ?></p>
                                </div>
                            </div>
                            <div class="id-card-footer">
                                <p class="small mb-1">Valid for Session: <?php echo htmlspecialchars($person['session_name']); ?></p>
                                <img src="<?php echo $barcode_url; ?>" class="barcode" alt="Barcode" style="height: 35px;">
                            </div>
                        </div>
                    <?php elseif ($template == 'student-landscape'): ?>
                        <div class="id-card student-landscape" style="<?php echo $card_bg_img_style; ?> <?php echo $card_bg_color; ?> color: <?php echo htmlspecialchars($settings['card_text_color'] ?? '#4b260d'); ?>;">
                            <div class="card-top-header" style="<?php echo $card_header_bg_color; ?>">
                                <?php $header_logo = $_SESSION['branch_logo'] ?? SITE_LOGO; ?>
                                <?php if ($header_logo): ?>
                                    <img src="<?php echo BASE_URL . '/' . $header_logo; ?>" class="header-logo" alt="Logo" />
                                <?php endif; ?>
                                <div class="header-text">
                                    <div class="school-main-name"><?php echo htmlspecialchars(SITE_NAME); ?></div>
                                    <div class="school-branch-name"><?php echo htmlspecialchars($person['branch_name']); ?></div>
                                </div>
                            </div>
                            <div class="card-content-wrapper">
                                <div class="left-section">
                                    <img src="<?php echo $photo_url; ?>" class="circular-image <?php echo $photo_shape_class; ?>" alt="Student Photo" />
                                    <img src="<?php echo $qr_code_url; ?>" class="qr-image" alt="QR Code"/>
                                </div>
                                <div class="right-section">
                                    <div class="details-grid">
                                        <div class="details-labels">
                                            <div>STUDENT NAME:</div>
                                            <div>FATHER'S NAME:</div>
                                            <div>CLASS:</div>
                                            <div>GR. No:</div>
                                            <div>CONTACT:</div>
                                        </div>
                                        <div class="details-values">
                                            <div><?php echo htmlspecialchars($person['full_name']); ?></div>
                                            <div><?php echo htmlspecialchars($person['father_name']); ?></div>
                                            <div><?php echo htmlspecialchars($person['class_name'] . ' - ' . $person['section_name']); ?></div>
                                            <div><?php echo htmlspecialchars($person['admission_no']); ?></div>
                                            <div><?php echo htmlspecialchars($person['father_phone'] ?? 'N/A'); ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="barcode-container">
                                        <img src="<?php echo $barcode_url; ?>" class="barcode-image" alt="Barcode">
                                    </div>

                                    <?php /* Placeholder for signature if available in DB */ ?>
                                    <!-- <img src="path/to/signature.png" class="principal-signature" /> -->
                                </div>
                            </div>
                        </div>
                    <?php elseif ($template == 'staff-landscape'): ?>
                        <div class="id-card staff-landscape" style="<?php echo $card_bg_img_style; ?> <?php echo $card_bg_color; ?> color: <?php echo htmlspecialchars($settings['card_text_color'] ?? '#4b260d'); ?>;">
                            <div class="card-top-header" style="<?php echo $card_header_bg_color; ?>">
                                <?php $header_logo = $_SESSION['branch_logo'] ?? SITE_LOGO; ?>
                                <?php if ($header_logo): ?>
                                    <img src="<?php echo BASE_URL . '/' . $header_logo; ?>" class="header-logo" alt="Logo" />
                                <?php endif; ?>
                                <div class="header-text">
                                    <div class="school-main-name"><?php echo htmlspecialchars(SITE_NAME); ?></div>
                                    <div class="school-branch-name"><?php echo htmlspecialchars($person['branch_name']); ?></div>
                                </div>
                            </div>
                            <div class="card-content-wrapper">
                                <div class="left-section">
                                    <img src="<?php echo $photo_url; ?>" class="circular-image <?php echo $photo_shape_class; ?>" alt="Staff Photo" />
                                    <img src="<?php echo $qr_code_url; ?>" class="qr-image" alt="QR Code"/>
                                </div>
                                <div class="right-section">
                                    <div class="details-grid">
                                        <div class="details-labels">
                                            <div>STAFF NAME:</div>
                                            <div>DESIGNATION:</div>
                                            <div>STAFF ID:</div>
                                            <div>CONTACT:</div>
                                            <div>JOINING DATE:</div>
                                        </div>
                                        <div class="details-values">
                                            <div><?php echo htmlspecialchars($person['full_name']); ?></div>
                                            <div>TEACHER</div>
                                            <div><?php echo htmlspecialchars($person['staff_id']); ?></div>
                                            <div><?php echo htmlspecialchars($person['branch_phone'] ?? 'N/A'); ?></div>
                                            <div><?php echo $person['joining_date'] ? date('d-M-Y', strtotime($person['joining_date'])) : 'N/A'; ?></div>
                                        </div>
                                    </div>
                                    <div class="barcode-container"><img src="<?php echo $barcode_url; ?>" class="barcode-image" alt="Barcode"></div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
        // Capture the generated HTML for the form
        ob_start();
        ?><div class="id-card-grid"><?php
        foreach ($results as $person) { /* ... re-render logic ... */ }
        ?></div><?php
        $card_html_output = ob_get_clean();
    ?>
    <?php elseif ($_SERVER['REQUEST_METHOD'] == 'POST'): ?>
        <div class="alert alert-warning">No records found for the selected criteria.</div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cardTypeSelect = document.getElementById('card_type');
    const filtersContainer = document.getElementById('filters-container');
    const templateContainer = document.getElementById('template-container');
    const colorContainer = document.getElementById('color-container');
    const filterGroups = document.querySelectorAll('.filter-group');
    const templateGroups = document.querySelectorAll('.template-group');

    function toggleSections() {
        const selectedType = cardTypeSelect.value;

        if (selectedType) {
            filtersContainer.style.display = 'block';
            templateContainer.style.display = 'block';
            colorContainer.style.display = 'block';
        } else {
            filtersContainer.style.display = 'none';
            templateContainer.style.display = 'none';
            colorContainer.style.display = 'none';
        }

        filterGroups.forEach(group => {
            group.style.display = group.id === `${selectedType}-filters` ? 'block' : 'none';
        });

        templateGroups.forEach(group => {
            group.style.display = group.id === `${selectedType}-templates` ? 'block' : 'none';
        });
    }

    cardTypeSelect.addEventListener('change', toggleSections);

    // Trigger on page load if a type is already selected (e.g., after form submission)
    toggleSections();

    // --- Dynamic Section Dropdown ---
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
            })
            .catch(error => {
                sectionSelect.innerHTML = '<option value="">-- Error --</option>';
            });
    });
    
    // --- Opacity Slider Value Display ---
    function setupOpacitySlider(sliderId, displayId) {
        const slider = document.getElementById(sliderId);
        const display = document.getElementById(displayId);
        if (slider && display) {
            slider.addEventListener('input', () => display.textContent = Math.round(slider.value * 100));
        }
    }
    setupOpacitySlider('card_bg_opacity', 'card_bg_opacity_value');
    setupOpacitySlider('card_header_bg_opacity', 'card_header_bg_opacity_value');

    // --- Prepare data for print page ---
    const printForm = document.getElementById('print-form');
    if (printForm) {
        const cardDataInput = document.getElementById('card_data_input');
        const cardGridContainer = document.getElementById('card-grid-container');
        const cardData = {
            count: <?php echo count($results); ?>,
            html: cardGridContainer.innerHTML,
            branch_id: <?php echo json_encode($branch_id); ?>,
            session_id: <?php echo json_encode($_POST['session_id'] ?? 0); ?>
        };
        cardDataInput.value = btoa(JSON.stringify(cardData));

        const printFrontsBtn = document.getElementById('print-fronts-btn');
        const printBacksBtn = document.getElementById('print-backs-btn');

        if (printFrontsBtn) {
            printFrontsBtn.addEventListener('click', (e) => {
                printForm.action = 'print_id_card.php';
            });
        }

        if (printBacksBtn) {
            printBacksBtn.addEventListener('click', (e) => {
                printForm.action = 'print_id_card_back.php';
            });
        }
    }
});
</script>

<?php require_once '../../footer.php'; ?>
