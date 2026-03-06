<?php
$page_title = "Manage Public Content";
require_once '../../config.php';
require_once '../../functions.php';

check_role('superadmin');

$errors = [];
$branches = [];

// Fetch all branches for the dropdown
try {
    $stmt_branches = $db->query("SELECT id, name FROM branches ORDER BY name ASC");
    $branches = $stmt_branches->fetchAll();
} catch (PDOException $e) {
    $errors[] = "Could not load school branches: " . $e->getMessage();
}

// Define editable content sections
$content_keys = [
    'public_page_hero_title' => 'Homepage Hero Title',
    'public_page_hero_subtitle' => 'Homepage Hero Subtitle',
    'public_page_about_us' => 'About Us Section Text',
    'public_page_contact_address' => 'Contact Address',
    'public_page_contact_phone' => 'Contact Phone',
    'public_page_contact_email' => 'Contact Email',
    'public_page_main_branch_id' => 'Main Branch for Public Contact',
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        foreach ($content_keys as $key => $label) {
            if (isset($_POST[$key])) {
                $stmt->execute([$key, trim($_POST[$key])]);
            }
        }
        $_SESSION['success_message'] = "Public content updated successfully!";
        redirect('manage_content.php');
    } catch (PDOException $e) {
        $errors[] = "Database error: " . $e->getMessage();
    }
}

// Fetch current values
$stmt_content = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'public_page_%'");
$public_content = $stmt_content->fetchAll(PDO::FETCH_KEY_PAIR);

require_once '../../header.php';
?>

<?php require_once '../../sidebar_superadmin.php'; ?>
<?php require_once '../../navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Manage Public Content</li>
    </ol>

    <?php display_flash_messages(); ?>
    <?php if (!empty($errors)) echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; ?>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-edit me-1"></i>
            Edit Homepage Content
        </div>
        <div class="card-body">
            <form action="manage_content.php" method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="public_page_hero_title" class="form-label"><?php echo $content_keys['public_page_hero_title']; ?></label>
                        <input type="text" class="form-control" id="public_page_hero_title" name="public_page_hero_title" value="<?php echo htmlspecialchars($public_content['public_page_hero_title'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="public_page_hero_subtitle" class="form-label"><?php echo $content_keys['public_page_hero_subtitle']; ?></label>
                        <input type="text" class="form-control" id="public_page_hero_subtitle" name="public_page_hero_subtitle" value="<?php echo htmlspecialchars($public_content['public_page_hero_subtitle'] ?? ''); ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="public_page_about_us" class="form-label"><?php echo $content_keys['public_page_about_us']; ?></label>
                    <textarea class="form-control" id="public_page_about_us" name="public_page_about_us" rows="10"><?php echo htmlspecialchars($public_content['public_page_about_us'] ?? ''); ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="public_page_main_branch_id" class="form-label"><?php echo $content_keys['public_page_main_branch_id']; ?></label>
                    <select class="form-select" id="public_page_main_branch_id" name="public_page_main_branch_id">
                        <option value="">-- None (Use Manual Details Below) --</option>
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['id']; ?>" <?php echo (($public_content['public_page_main_branch_id'] ?? '') == $branch['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($branch['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">This branch's details will be highlighted on the public Contact Us page. If 'None' is selected, the manual details below will be used.</div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-4 mb-3"><label for="public_page_contact_address" class="form-label"><?php echo $content_keys['public_page_contact_address']; ?></label><input type="text" class="form-control" id="public_page_contact_address" name="public_page_contact_address" value="<?php echo htmlspecialchars($public_content['public_page_contact_address'] ?? ''); ?>"></div>
                    <div class="col-md-4 mb-3"><label for="public_page_contact_phone" class="form-label"><?php echo $content_keys['public_page_contact_phone']; ?></label><input type="text" class="form-control" id="public_page_contact_phone" name="public_page_contact_phone" value="<?php echo htmlspecialchars($public_content['public_page_contact_phone'] ?? ''); ?>"></div>
                    <div class="col-md-4 mb-3"><label for="public_page_contact_email" class="form-label"><?php echo $content_keys['public_page_contact_email']; ?></label><input type="email" class="form-control" id="public_page_contact_email" name="public_page_contact_email" value="<?php echo htmlspecialchars($public_content['public_page_contact_email'] ?? ''); ?>"></div>
                </div>
                <button type="submit" class="btn btn-primary">Save Content</button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../footer.php'; ?>

<!-- TinyMCE Rich Text Editor -->
<script src="https://cdn.tiny.cloud/1/arupjacoynrnk1qzrzlz2jm7hu8xv1nghwpdtkextzag3a8w/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({
    selector: 'textarea#public_page_about_us',
    plugins: 'code table lists image link media',
    toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | indent outdent | bullist numlist | code | table | image link media',
    height: 400
  });
</script>
