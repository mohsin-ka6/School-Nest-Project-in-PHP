<?php
$page_title = "Admission Queries";
require_once dirname(__DIR__, 2) . '/config.php';
require_once ROOT_PATH . '/functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$user_id = $_SESSION['user_id'];
$errors = [];

// Handle form submission for adding/editing a query
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $query_id = isset($_POST['query_id']) ? (int)$_POST['query_id'] : 0;
    $student_name = trim($_POST['student_name']);
    $contact_person = trim($_POST['contact_person']);
    $contact_phone = trim($_POST['contact_phone']);
    $query_date = trim($_POST['query_date']);
    $status = trim($_POST['status']);

    if (empty($student_name) || empty($contact_person) || empty($contact_phone) || empty($query_date)) {
        $errors[] = "All required fields (*) must be filled out.";
    }

    if (empty($errors)) {
        try {
            if ($query_id > 0) { // Update existing query
                $stmt = $db->prepare(
                    "UPDATE admission_queries SET student_name=?, contact_person=?, contact_phone=?, contact_email=?, class_of_interest=?, source=?, notes=?, query_date=?, next_follow_up_date=?, status=? WHERE id=? AND branch_id=?"
                );
                $stmt->execute([
                    $student_name, $contact_person, $contact_phone,
                    trim($_POST['contact_email']), trim($_POST['class_of_interest']),
                    trim($_POST['source']), trim($_POST['notes']), $query_date,
                    empty($_POST['next_follow_up_date']) ? null : trim($_POST['next_follow_up_date']),
                    $status, $query_id, $branch_id
                ]);
                $_SESSION['success_message'] = "Query updated successfully!";
            } else { // Insert new query
                $stmt = $db->prepare(
                    "INSERT INTO admission_queries (branch_id, student_name, contact_person, contact_phone, contact_email, class_of_interest, source, notes, query_date, next_follow_up_date, status, created_by) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $branch_id, $student_name, $contact_person, $contact_phone,
                    trim($_POST['contact_email']), trim($_POST['class_of_interest']),
                    trim($_POST['source']), trim($_POST['notes']), $query_date,
                    empty($_POST['next_follow_up_date']) ? null : trim($_POST['next_follow_up_date']),
                    $status, $user_id
                ]);
                $_SESSION['success_message'] = "Query added successfully!";
            }
            redirect('admission_query.php');
        } catch (PDOException $e) {
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }
}

// Handle delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $query_id = (int)$_GET['id'];
    try {
        $stmt = $db->prepare("DELETE FROM admission_queries WHERE id = ? AND branch_id = ?");
        $stmt->execute([$query_id, $branch_id]);
        $_SESSION['success_message'] = "Query deleted successfully.";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error deleting query: " . $e->getMessage();
    }
    redirect('admission_query.php');
}

// Fetch all queries for this branch
$stmt = $db->prepare("SELECT * FROM admission_queries WHERE branch_id = ? ORDER BY query_date DESC");
$stmt->execute([$branch_id]);
$queries = $stmt->fetchAll();

require_once ROOT_PATH . '/header.php';
?>

<?php require_once ROOT_PATH . '/sidebar_branchadmin.php'; ?>
<?php require_once ROOT_PATH . '/navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Admission Queries</li>
    </ol>

    <?php display_flash_messages(); ?>
    <?php if (!empty($errors)) echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; ?>

    <!-- Form to add new query -->
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-plus me-1"></i> Add New Admission Query</div>
        <div class="card-body">
            <form action="" method="POST">
                <div class="row">
                    <div class="col-md-4 mb-3"><label>Student Name*</label><input type="text" name="student_name" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label>Contact Person*</label><input type="text" name="contact_person" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label>Contact Phone*</label><input type="text" name="contact_phone" class="form-control" required></div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3"><label>Contact Email</label><input type="email" name="contact_email" class="form-control"></div>
                    <div class="col-md-4 mb-3"><label>Class of Interest</label><input type="text" name="class_of_interest" class="form-control"></div>
                    <div class="col-md-4 mb-3"><label>Source</label><input type="text" name="source" class="form-control" placeholder="e.g., Walk-in, Facebook"></div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3"><label>Query Date*</label><input type="date" name="query_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>"></div>
                    <div class="col-md-4 mb-3"><label>Next Follow-up Date</label><input type="date" name="next_follow_up_date" class="form-control"></div>
                    <div class="col-md-4 mb-3"><label>Status*</label><select name="status" class="form-select"><option value="active">Active</option><option value="closed">Closed</option><option value="enrolled">Enrolled</option></select></div>
                </div>
                <div class="mb-3"><label>Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                <button type="submit" class="btn btn-primary">Save Query</button>
            </form>
        </div>
    </div>

    <!-- Table of existing queries -->
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-table me-1"></i> All Admission Queries</div>
        <div class="card-body">
            <div class="table-responsive">
                 <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Contact Person</th>
                            <th>Phone</th>
                            <th>Query Date</th>
                            <th>Follow-up Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($queries as $query): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($query['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($query['contact_person']); ?></td>
                            <td><?php echo htmlspecialchars($query['contact_phone']); ?></td>
                            <td><?php echo date('d M, Y', strtotime($query['query_date'])); ?></td>
                            <td><?php echo $query['next_follow_up_date'] ? date('d M, Y', strtotime($query['next_follow_up_date'])) : 'N/A'; ?></td>
                            <td>
                                <?php 
                                $status_class = ['active' => 'primary', 'closed' => 'secondary', 'enrolled' => 'success'];
                                echo '<span class="badge bg-' . ($status_class[$query['status']] ?? 'light') . '">' . ucfirst($query['status']) . '</span>';
                                ?>
                            </td>
                            <td class="d-flex gap-1">
                                <?php if ($query['status'] != 'enrolled'): ?>
                                    <a href="../students/add_student.php?query_id=<?php echo $query['id']; ?>" class="btn btn-sm btn-success" title="Convert to Admission"><i class="fas fa-user-plus"></i></a>
                                <?php endif; ?>
                                <!-- Edit and Delete buttons can be added here -->
                                <a href="admission_query.php?action=delete&id=<?php echo $query['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');" title="Delete"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/footer.php'; ?>