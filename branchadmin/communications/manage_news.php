<?php
$page_title = "Manage News & Events";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$errors = [];
$upload_dir = '../../assets/uploads/news/';

// Ensure upload directory exists
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $item_id = (int)$_POST['item_id'];
    try {
        // First, get the image path to delete the file
        $stmt_img = $db->prepare("SELECT image_path FROM news_and_events WHERE id = ? AND branch_id = ?");
        $stmt_img->execute([$item_id, $branch_id]);
        $image_path = $stmt_img->fetchColumn();
        if ($image_path && file_exists('../../' . $image_path)) {
            unlink('../../' . $image_path);
        }

        $stmt = $db->prepare("DELETE FROM news_and_events WHERE id = ? AND branch_id = ?");
        $stmt->execute([$item_id, $branch_id]);
        $_SESSION['success_message'] = "Item deleted successfully.";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error deleting item: " . $e->getMessage();
    }
    redirect('manage_news.php');
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['action'])) {
    $item_id = (int)($_POST['item_id'] ?? 0);
    $title = trim($_POST['title']);
    $content = $_POST['content']; // TinyMCE provides HTML
    $type = $_POST['type'];
    $status = $_POST['status'];
    $event_date = !empty($_POST['event_date']) ? $_POST['event_date'] : null;

    if (empty($title) || empty($content)) {
        $errors[] = "Title and Content are required.";
    }

    $image_db_path = $_POST['existing_image'] ?? null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $image_name = uniqid() . '_' . basename($_FILES['image']['name']);
        $image_upload_path = $upload_dir . $image_name;
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $errors[] = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
        } elseif ($_FILES['image']['size'] > 2097152) { // 2MB limit
            $errors[] = "File size exceeds the 2MB limit.";
        } elseif (move_uploaded_file($_FILES['image']['tmp_name'], $image_upload_path)) {
            // Delete old image if updating
            if ($item_id && $image_db_path && file_exists('../../' . $image_db_path)) {
                unlink('../../' . $image_db_path);
            }
            $image_db_path = 'assets/uploads/news/' . $image_name;
        } else {
            $errors[] = "Failed to upload the image.";
        }
    }

    if (empty($errors)) {
        try {
            if ($item_id > 0) { // Update
                $stmt = $db->prepare("UPDATE news_and_events SET title=?, content=?, image_path=?, event_date=?, type=?, status=? WHERE id=? AND branch_id = ?");
                $stmt->execute([$title, $content, $image_db_path, $event_date, $type, $status, $item_id, $branch_id]);
                $_SESSION['success_message'] = "Item updated successfully!";
            } else { // Insert
                $stmt = $db->prepare("INSERT INTO news_and_events (branch_id, title, content, image_path, event_date, type, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$branch_id, $title, $content, $image_db_path, $event_date, $type, $status]);
                $_SESSION['success_message'] = "Item added successfully!";
            }
            redirect('manage_news.php');
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch item for editing
$edit_item = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM news_and_events WHERE id = ? AND branch_id = ?");
    $stmt->execute([(int)$_GET['id'], $branch_id]);
    $edit_item = $stmt->fetch();
}

// Fetch all items for this branch
$items = $db->prepare("SELECT * FROM news_and_events WHERE branch_id = ? ORDER BY created_at DESC");
$items->execute([$branch_id]);
$items = $items->fetchAll();

require_once '../../header.php';
?>

<?php require_once '../../sidebar_branchadmin.php'; ?>
<?php require_once '../../navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">News & Events</li>
    </ol>

    <?php display_flash_messages(); ?>
    <?php if (!empty($errors)) echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; ?>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-plus me-1"></i> <?php echo $edit_item ? 'Edit' : 'Add New'; ?> Item</div>
        <div class="card-body">
            <form action="manage_news.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="item_id" value="<?php echo $edit_item['id'] ?? 0; ?>">
                <input type="hidden" name="existing_image" value="<?php echo $edit_item['image_path'] ?? ''; ?>">
                <div class="mb-3">
                    <label for="title" class="form-label">Title*</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($edit_item['title'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="content" class="form-label">Content*</label>
                    <textarea class="form-control" id="content" name="content" rows="10"><?php echo htmlspecialchars($edit_item['content'] ?? ''); ?></textarea>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3"><label for="type" class="form-label">Type*</label><select name="type" id="type" class="form-select"><option value="news" <?php echo (($edit_item['type'] ?? 'news') == 'news' ? 'selected' : ''); ?>>News</option><option value="event" <?php echo (($edit_item['type'] ?? '') == 'event' ? 'selected' : ''); ?>>Event</option></select></div>
                    <div class="col-md-3 mb-3"><label for="event_date" class="form-label">Event Date (if applicable)</label><input type="date" class="form-control" id="event_date" name="event_date" value="<?php echo htmlspecialchars($edit_item['event_date'] ?? ''); ?>"></div>
                    <div class="col-md-3 mb-3"><label for="status" class="form-label">Status*</label><select name="status" id="status" class="form-select"><option value="draft" <?php echo (($edit_item['status'] ?? 'draft') == 'draft' ? 'selected' : ''); ?>>Draft</option><option value="published" <?php echo (($edit_item['status'] ?? '') == 'published' ? 'selected' : ''); ?>>Published</option></select></div>
                    <div class="col-md-3 mb-3"><label for="image" class="form-label">Featured Image</label><input type="file" class="form-control" id="image" name="image"><?php if ($edit_item && $edit_item['image_path']) echo '<div class="form-text">Current: ' . basename($edit_item['image_path']) . '</div>'; ?></div>
                </div>
                <button type="submit" class="btn btn-primary"><?php echo $edit_item ? 'Update' : 'Save'; ?> Item</button>
                <?php if ($edit_item): ?><a href="manage_news.php" class="btn btn-secondary">Cancel Edit</a><?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-list me-1"></i> All News & Events</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th>Title</th><th>Type</th><th>Event Date</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['title']); ?></td>
                            <td><?php echo ucfirst($item['type']); ?></td>
                            <td><?php echo $item['event_date'] ? date('d M, Y', strtotime($item['event_date'])) : 'N/A'; ?></td>
                            <td><span class="badge bg-<?php echo $item['status'] == 'published' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($item['status']); ?></span></td>
                            <td>
                                <a href="?action=edit&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                                <form action="manage_news.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../footer.php'; ?>

<!-- TinyMCE Rich Text Editor -->
<script src="https://cdn.tiny.cloud/1/arupjacoynrnk1qzrzlz2jm7hu8xv1nghwpdtkextzag3a8w/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({
    selector: 'textarea#content',
    plugins: 'code table lists image link media',
    toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | indent outdent | bullist numlist | code | table | image link media',
    height: 300
  });
</script>
