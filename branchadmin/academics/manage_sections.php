<?php
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$errors = [];

if (!$class_id) {
    $_SESSION['error_message'] = "Invalid class selected.";
    redirect('manage_classes.php');
}

// Fetch class details to ensure it belongs to the admin's branch
$stmt = $db->prepare("SELECT * FROM classes WHERE id = ? AND branch_id = ?");
$stmt->execute([$class_id, $branch_id]);
$class = $stmt->fetch();

if (!$class) {
    $_SESSION['error_message'] = "Class not found or you do not have permission to access it.";
    redirect('manage_classes.php');
}

$page_title = "Manage Sections for " . htmlspecialchars($class['name']);

// Handle form submission for adding a new section
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_section'])) {
    $section_name = trim($_POST['section_name']);
    $capacity = trim($_POST['capacity']);

    if (empty($section_name)) {
        $errors[] = "Section name is required.";
    }

    if (empty($errors)) {
        try {
            $stmt = $db->prepare("INSERT INTO sections (class_id, branch_id, name, capacity) VALUES (?, ?, ?, ?)");
            $stmt->execute([$class_id, $branch_id, $section_name, empty($capacity) ? null : $capacity]);
            $_SESSION['success_message'] = "Section '{$section_name}' added successfully to " . htmlspecialchars($class['name']) . "!";
            redirect("manage_sections.php?class_id={$class_id}");
        } catch (PDOException $e) {
            $errors[] = "Database Error: Could not add section. " . $e->getMessage();
        }
    }
}

// Fetch all sections for this class
$stmt = $db->prepare("SELECT * FROM sections WHERE class_id = ? ORDER BY name ASC");
$stmt->execute([$class_id]);
$sections = $stmt->fetchAll();

require_once '../../header.php';
?>

<?php require_once ROOT_PATH . '/sidebar_branchadmin.php'; ?>
<?php require_once ROOT_PATH . '/navbar.php'; ?>

<div class="container-fluid px-4">

    <?php if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>'; unset($_SESSION['success_message']); } ?>
    <?php if (!empty($errors)) { echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; } ?>

    <div class="row">
        <!-- Add Section Form -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-plus me-1"></i> Add New Section</div>
                <div class="card-body">
                    <form action="manage_sections.php?class_id=<?php echo $class_id; ?>" method="POST">
                        <div class="mb-3"><label>Section Name* (e.g., A, Blue)</label><input type="text" name="section_name" class="form-control" required></div>
                        <div class="mb-3"><label>Capacity</label><input type="number" name="capacity" class="form-control"></div>
                        <button type="submit" name="add_section" class="btn btn-primary">Add Section</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sections List -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-table me-1"></i> Sections List</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead><tr><th>Section Name</th><th>Capacity</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($sections as $section): ?>
                                <tr><td><?php echo htmlspecialchars($section['name']); ?></td><td><?php echo htmlspecialchars($section['capacity'] ?? 'N/A'); ?></td><td><!-- Edit/Delete buttons here --></td></tr>
                                <?php endforeach; ?>
                                <?php if (empty($sections)) echo '<tr><td colspan="3" class="text-center">No sections found for this class.</td></tr>'; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../footer.php'; ?>