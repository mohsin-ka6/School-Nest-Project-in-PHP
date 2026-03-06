<?php
$page_title = "Manage Invitation Templates";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$errors = [];

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $template_id = (int)$_POST['template_id'];
    try {
        $stmt = $db->prepare("DELETE FROM invitation_templates WHERE id = ? AND branch_id = ?");
        $stmt->execute([$template_id, $branch_id]);
        $_SESSION['success_message'] = "Template deleted successfully.";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error deleting template: " . $e->getMessage();
    }
    redirect('manage_invitation_templates.php');
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['action'])) {
    $template_id = (int)($_POST['template_id'] ?? 0);
    $title = trim($_POST['title']);
    $language = $_POST['language'];
    $content = trim($_POST['content']);

    if (empty($title) || empty($language) || empty($content)) {
        $errors[] = "Title, Language, and Content are required.";
    }

    if (empty($errors)) {
        try {
            if ($template_id > 0) { // Update
                $stmt = $db->prepare("UPDATE invitation_templates SET title=?, language=?, content=? WHERE id=? AND branch_id=?");
                $stmt->execute([$title, $language, $content, $template_id, $branch_id]);
                $_SESSION['success_message'] = "Template updated successfully!";
            } else { // Insert
                $stmt = $db->prepare("INSERT INTO invitation_templates (branch_id, title, language, content) VALUES (?, ?, ?, ?)");
                $stmt->execute([$branch_id, $title, $language, $content]);
                $_SESSION['success_message'] = "Template added successfully!";
            }
            redirect('manage_invitation_templates.php');
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch template for editing
$edit_template = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM invitation_templates WHERE id = ? AND branch_id = ?");
    $stmt->execute([(int)$_GET['id'], $branch_id]);
    $edit_template = $stmt->fetch();
}

// Fetch all templates for this branch
$stmt_all = $db->prepare("SELECT * FROM invitation_templates WHERE branch_id = ? ORDER BY title ASC");
$stmt_all->execute([$branch_id]);
$templates = $stmt_all->fetchAll();

require_once '../../header.php';
?>

<?php require_once '../../sidebar_branchadmin.php'; ?>
<?php require_once '../../navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="invitation_maker.php">Invitation Maker</a></li>
        <li class="breadcrumb-item active">Manage Templates</li>
    </ol>

    <?php display_flash_messages(); ?>
    <?php if (!empty($errors)) echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; ?>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-plus me-1"></i> <?php echo $edit_template ? 'Edit' : 'Add New'; ?> Template</div>
        <div class="card-body">
            <form action="manage_invitation_templates.php" method="POST">
                <input type="hidden" name="template_id" value="<?php echo $edit_template['id'] ?? 0; ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="title" class="form-label">Template Title*</label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($edit_template['title'] ?? ''); ?>" placeholder="e.g., Annual Results Day" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="language" class="form-label">Language*</label>
                        <select name="language" id="language" class="form-select" required>
                            <option value="en" <?php echo (($edit_template['language'] ?? 'en') == 'en' ? 'selected' : ''); ?>>English</option>
                            <option value="ur" <?php echo (($edit_template['language'] ?? '') == 'ur' ? 'selected' : ''); ?>>Urdu</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="content" class="form-label">Template Content*</label>
                    <textarea class="form-control" id="content" name="content" rows="6" required><?php echo htmlspecialchars($edit_template['content'] ?? ''); ?></textarea>
                    <div class="form-text mt-2">
                        <strong>Click to insert placeholders:</strong>
                        <span class="badge bg-secondary placeholder-badge" data-placeholder="[parent_name]">[parent_name]</span>
                        <span class="badge bg-secondary placeholder-badge" data-placeholder="[student_name]">[student_name]</span>
                        <span class="badge bg-secondary placeholder-badge" data-placeholder="[class_name]">[class_name]</span>
                        <span class="badge bg-secondary placeholder-badge" data-placeholder="[event_date]">[event_date]</span>
                        <span class="badge bg-secondary placeholder-badge" data-placeholder="[event_time]">[event_time]</span>
                        <span class="badge bg-secondary placeholder-badge" data-placeholder="[venue]">[venue]</span>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><?php echo $edit_template ? 'Update' : 'Save'; ?> Template</button>
                <?php if ($edit_template): ?><a href="manage_invitation_templates.php" class="btn btn-secondary">Cancel Edit</a><?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-list me-1"></i> Existing Templates</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th>Title</th><th>Language</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (empty($templates)): ?>
                            <tr><td colspan="3" class="text-center">No templates found. Add one using the form above.</td></tr>
                        <?php else: foreach ($templates as $template): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($template['title']); ?></td>
                            <td><?php echo $template['language'] == 'ur' ? 'Urdu' : 'English'; ?></td>
                            <td>
                                <a href="?action=edit&id=<?php echo $template['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                                <form action="manage_invitation_templates.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this template?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Placeholder Click Logic ---
    const messageTextarea = document.getElementById('content');
    document.querySelectorAll('.placeholder-badge').forEach(badge => {
        badge.style.cursor = 'pointer';
        badge.addEventListener('click', function() {
            const placeholder = this.getAttribute('data-placeholder');
            const cursorPos = messageTextarea.selectionStart;
            const textBefore = messageTextarea.value.substring(0, cursorPos);
            const textAfter = messageTextarea.value.substring(cursorPos);
            messageTextarea.value = textBefore + placeholder + textAfter;
            messageTextarea.focus();
        });
    });
});
</script>

<?php require_once '../../footer.php'; ?>