<?php
$page_title = "Marks Grade";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$errors = [];

// Handle form submission for adding/editing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $grade_id = isset($_POST['grade_id']) ? (int)$_POST['grade_id'] : 0;
    $grade_name = trim($_POST['grade_name']);
    $percent_from = trim($_POST['percent_from']);
    $percent_upto = trim($_POST['percent_upto']);
    $grade_point = !empty($_POST['grade_point']) ? trim($_POST['grade_point']) : null;
    $remarks = trim($_POST['remarks']);

    if (empty($grade_name) || !is_numeric($percent_from) || !is_numeric($percent_upto)) {
        $errors[] = "Grade Name, Percentage From, and Percentage To are required and must be numbers.";
    }
    if ($percent_from >= $percent_upto) {
        $errors[] = "Percentage From must be less than Percentage To.";
    }

    if (empty($errors)) {
        try {
            if ($grade_id > 0) { // Update
                $stmt = $db->prepare("UPDATE marks_grades SET grade_name=?, percent_from=?, percent_upto=?, grade_point=?, remarks=? WHERE id=? AND branch_id=?");
                $stmt->execute([$grade_name, $percent_from, $percent_upto, $grade_point, $remarks, $grade_id, $branch_id]);
                $_SESSION['success_message'] = "Grade updated successfully!";
            } else { // Insert
                $stmt = $db->prepare("INSERT INTO marks_grades (branch_id, grade_name, percent_from, percent_upto, grade_point, remarks) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$branch_id, $grade_name, $percent_from, $percent_upto, $grade_point, $remarks]);
                $_SESSION['success_message'] = "Grade added successfully!";
            }
            redirect('marks_grade.php');
        } catch (PDOException $e) {
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }
}

// Handle deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $grade_id = (int)$_GET['id'];
    try {
        $stmt = $db->prepare("DELETE FROM marks_grades WHERE id = ? AND branch_id = ?");
        $stmt->execute([$grade_id, $branch_id]);
        $_SESSION['success_message'] = "Grade deleted successfully.";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Could not delete grade.";
    }
    redirect('marks_grade.php');
}

// Fetch all grades for this branch
$stmt = $db->prepare("SELECT * FROM marks_grades WHERE branch_id = ? ORDER BY percent_from DESC");
$stmt->execute([$branch_id]);
$grades = $stmt->fetchAll();

require_once '../../header.php';
?>

<?php require_once '../../sidebar_branchadmin.php'; ?>
<?php require_once '../../navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Marks Grade</li>
    </ol>

    <?php display_flash_messages(); ?>
    <?php if (!empty($errors)) echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-plus-circle me-1"></i> Add New Grade</div>
                <div class="card-body">
                    <form action="" method="POST">
                        <div class="mb-3"><label>Grade Name*</label><input type="text" name="grade_name" class="form-control" required placeholder="e.g., A+"></div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label>Percentage From*</label><input type="number" step="0.01" name="percent_from" class="form-control" required placeholder="e.g., 90"></div>
                            <div class="col-md-6 mb-3"><label>Percentage To*</label><input type="number" step="0.01" name="percent_upto" class="form-control" required placeholder="e.g., 100"></div>
                        </div>
                        <div class="mb-3"><label>Grade Point</label><input type="number" step="0.01" name="grade_point" class="form-control" placeholder="e.g., 4.0"></div>
                        <div class="mb-3"><label>Remarks</label><input type="text" name="remarks" class="form-control" placeholder="e.g., Excellent"></div>
                        <button type="submit" class="btn btn-primary">Save Grade</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-list-alt me-1"></i> Existing Grades</div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Grade Name</th>
                                <th>Percentage Range</th>
                                <th>Grade Point</th>
                                <th>Remarks</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($grades)): ?>
                                <tr><td colspan="5" class="text-center">No grades found.</td></tr>
                            <?php else: foreach ($grades as $grade): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($grade['grade_name']); ?></td>
                                <td><?php echo htmlspecialchars($grade['percent_from']) . ' - ' . htmlspecialchars($grade['percent_upto']); ?></td>
                                <td><?php echo htmlspecialchars($grade['grade_point'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($grade['remarks']); ?></td>
                                <td>
                                    <!-- Edit functionality can be added later if needed -->
                                    <a href="?action=delete&id=<?php echo $grade['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');" title="Delete"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../footer.php'; ?>