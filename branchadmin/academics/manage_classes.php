<?php
$page_title = "Manage Classes";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$errors = [];

// Handle form submission for adding a new class
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_class'])) {
    $class_name = trim($_POST['class_name']);
    $numeric_name = trim($_POST['numeric_name']);

    if (empty($class_name)) {
        $errors[] = "Class name is required.";
    }

    if (empty($errors)) {
        try {
            $stmt = $db->prepare("INSERT INTO classes (branch_id, name, numeric_name) VALUES (?, ?, ?)");
            $stmt->execute([$branch_id, $class_name, empty($numeric_name) ? null : $numeric_name]);
            $_SESSION['success_message'] = "Class '{$class_name}' added successfully!";
            redirect('manage_classes.php');
        } catch (PDOException $e) {
            $errors[] = "Database Error: Could not add class. " . $e->getMessage();
        }
    }
}

// Fetch all classes for this branch, along with a count of their sections
$stmt = $db->prepare("
    SELECT c.*, COUNT(s.id) as section_count 
    FROM classes c
    LEFT JOIN sections s ON c.id = s.class_id
    WHERE c.branch_id = ? 
    GROUP BY c.id
    ORDER BY c.numeric_name ASC, c.name ASC
");
$stmt->execute([$branch_id]);
$classes = $stmt->fetchAll();

require_once '../../header.php';
?>

<?php require_once ROOT_PATH . '/sidebar_branchadmin.php'; ?>
<?php require_once ROOT_PATH . '/navbar.php'; ?>

<div class="container-fluid px-4">

    <?php if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>'; unset($_SESSION['success_message']); } ?>
    <?php if (!empty($errors)) { echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; } ?>

    <div class="row">
        <!-- Add Class Form -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-plus me-1"></i> Add New Class</div>
                <div class="card-body">
                    <form action="" method="POST">
                        <div class="mb-3"><label>Class Name* (e.g., Grade 10)</label><input type="text" name="class_name" class="form-control" required></div>
                        <div class="mb-3"><label>Numeric Value (for sorting)</label><input type="number" name="numeric_name" class="form-control"></div>
                        <button type="submit" name="add_class" class="btn btn-primary">Add Class</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Classes List -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-table me-1"></i> Classes List</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead><tr><th>Class Name</th><th>Sections</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($classes)): ?>
                                    <tr><td colspan="3" class="text-center">No classes found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($classes as $class): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($class['name']); ?></td>
                                        <td><?php echo $class['section_count']; ?></td>
                                        <td><a href="manage_sections.php?class_id=<?php echo $class['id']; ?>" class="btn btn-sm btn-info">Manage Sections</a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../footer.php'; ?>