<?php
$page_title = "Manage Photo Gallery";
require_once '../../config.php';
require_once '../../functions.php';

check_role('superadmin');

$errors = [];
$upload_dir = '../../assets/uploads/gallery/';

// Ensure upload directory exists
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $image_id = (int)$_POST['image_id'];
    try {
        // First, get the image path to delete the file
        $stmt_img = $db->prepare("SELECT image_path FROM gallery WHERE id = ?");
        $stmt_img->execute([$image_id]);
        $image_path = $stmt_img->fetchColumn();
        if ($image_path && file_exists('../../' . $image_path)) {
            unlink('../../' . $image_path);
        }

        $stmt = $db->prepare("DELETE FROM gallery WHERE id = ?");
        $stmt->execute([$image_id]);
        $_SESSION['success_message'] = "Image deleted successfully.";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error deleting image: " . $e->getMessage();
    }
    redirect('manage_gallery.php');
}

// Handle Add
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['gallery_image'])) {
    $title = trim($_POST['title']);

    if ($_FILES['gallery_image']['error'] == UPLOAD_ERR_OK) {
        $image_name = uniqid() . '_' . basename($_FILES['gallery_image']['name']);
        $image_upload_path = $upload_dir . $image_name;
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

        if (!in_array($_FILES['gallery_image']['type'], $allowed_types)) {
            $errors[] = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
        } elseif ($_FILES['gallery_image']['size'] > 5242880) { // 5MB limit
            $errors[] = "File size exceeds the 5MB limit.";
        } elseif (move_uploaded_file($_FILES['gallery_image']['tmp_name'], $image_upload_path)) {
            $image_db_path = 'assets/uploads/gallery/' . $image_name;
            try {
                $stmt = $db->prepare("INSERT INTO gallery (title, image_path) VALUES (?, ?)");
                $stmt->execute([$title, $image_db_path]);
                $_SESSION['success_message'] = "Image uploaded successfully!";
            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        } else {
            $errors[] = "Failed to upload the image.";
        }
    } else {
        $errors[] = "Please select an image to upload.";
    }
    if (empty($errors)) redirect('manage_gallery.php');
}

// Fetch all gallery images
$images = $db->query("SELECT * FROM gallery ORDER BY uploaded_at DESC")->fetchAll();

require_once '../../header.php';
?>

<?php require_once '../../sidebar_superadmin.php'; ?>
<?php require_once '../../navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Manage Gallery</li>
    </ol>

    <?php display_flash_messages(); ?>
    <?php if (!empty($errors)) echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; ?>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-upload me-1"></i> Upload New Image</div>
        <div class="card-body">
            <form action="manage_gallery.php" method="POST" enctype="multipart/form-data">
                <div class="row align-items-end">
                    <div class="col-md-5 mb-3">
                        <label for="gallery_image" class="form-label">Image File*</label>
                        <input type="file" class="form-control" id="gallery_image" name="gallery_image" required>
                    </div>
                    <div class="col-md-5 mb-3">
                        <label for="title" class="form-label">Image Title (Optional)</label>
                        <input type="text" class="form-control" id="title" name="title" placeholder="e.g., Annual Sports Day 2024">
                    </div>
                    <div class="col-md-2 mb-3">
                        <button type="submit" class="btn btn-primary w-100">Upload</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-images me-1"></i> Current Gallery</div>
        <div class="card-body">
            <div class="row">
                <?php if (empty($images)): ?>
                    <div class="col-12"><p class="text-center text-muted">No images have been uploaded to the gallery yet.</p></div>
                <?php else: foreach ($images as $image): ?>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100">
                            <img src="<?php echo BASE_URL . '/' . htmlspecialchars($image['image_path']); ?>" class="card-img-top" style="height: 180px; object-fit: cover;" alt="<?php echo htmlspecialchars($image['title']); ?>">
                            <div class="card-body">
                                <p class="card-text small"><?php echo htmlspecialchars($image['title'] ?? 'No Title'); ?></p>
                            </div>
                            <div class="card-footer text-center">
                                <form action="manage_gallery.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this image?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../footer.php'; ?>