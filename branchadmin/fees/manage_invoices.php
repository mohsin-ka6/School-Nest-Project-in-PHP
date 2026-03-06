<?php
$page_title = "Manage Fee Invoices";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$errors = [];
$success_message = '';

// Fetch academic sessions
$stmt_sessions = $db->prepare("SELECT id, name FROM academic_sessions WHERE branch_id = ? ORDER BY start_date DESC");
$stmt_sessions->execute([$branch_id]);
$sessions = $stmt_sessions->fetchAll();

// --- FILTERS ---
$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;
$invoice_month = isset($_GET['invoice_month']) ? trim($_GET['invoice_month']) : date('Y-m');
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

// Default to current session if none is selected
if (!$session_id && !empty($sessions)) {
    $stmt_current_session = $db->prepare("SELECT id FROM academic_sessions WHERE branch_id = ? AND is_current = 1");
    $stmt_current_session->execute([$branch_id]);
    $session_id = $stmt_current_session->fetchColumn();
}

// Fetch classes for dropdown
$stmt_classes = $db->prepare("SELECT id, name FROM classes WHERE branch_id = ? ORDER BY numeric_name ASC");
$stmt_classes->execute([$branch_id]);
$classes = $stmt_classes->fetchAll();

// --- FETCH INVOICES BASED ON FILTERS ---
$invoices = [];
$where_clauses = ["fi.branch_id = :branch_id"];
$params = [':branch_id' => $branch_id];

if ($session_id) {
    $where_clauses[] = "fi.session_id = :session_id";
    $params[':session_id'] = $session_id;
}
if ($class_id) {
    $where_clauses[] = "fi.class_id = :class_id";
    $params[':class_id'] = $class_id;
}
if ($section_id) {
    $where_clauses[] = "se.section_id = :section_id";
    $params[':section_id'] = $section_id;
}
if ($invoice_month) {
    $where_clauses[] = "fi.invoice_month = :invoice_month";
    $params[':invoice_month'] = $invoice_month;
}
if ($status) {
    $where_clauses[] = "fi.status = :status";
    $params[':status'] = $status;
}

$sql = "SELECT 
            fi.id, fi.gross_amount, fi.concession_amount, fi.total_amount, 
            fi.amount_paid, fi.due_date, fi.status, fi.invoice_month,
            u.full_name as student_name, se.roll_no
        FROM fee_invoices fi
        JOIN students s ON fi.student_id = s.id
        JOIN users u ON s.user_id = u.id
        JOIN student_enrollments se ON (s.id = se.student_id AND fi.session_id = se.session_id)
        WHERE " . implode(' AND ', $where_clauses) . "
        ORDER BY se.roll_no, u.full_name";

$stmt_invoices = $db->prepare($sql);
$stmt_invoices->execute($params);
$invoices = $stmt_invoices->fetchAll();


if(isset($_SESSION['success_message'])) { $success_message = $_SESSION['success_message']; unset($_SESSION['success_message']); }

require_once '../../header.php';
?>

<?php require_once '../../sidebar_branchadmin.php'; ?>
<?php require_once '../../navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Manage Invoices</li>
    </ol>

    <?php if ($success_message): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo $success_message; ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
    <?php if (!empty($errors)) echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; ?>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-filter me-1"></i> Filter Invoices</div>
        <div class="card-body">
            <form action="" method="GET">
                <div class="row align-items-end">
                    <div class="col-md-3"><label>Session</label>
                        <select name="session_id" class="form-select" onchange="this.form.submit()">
                            <option value="">All Sessions</option>
                            <?php foreach ($sessions as $session) echo "<option value='{$session['id']}' " . ($session_id == $session['id'] ? 'selected' : '') . ">" . htmlspecialchars($session['name']) . "</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-2"><label>Class</label>
                        <select name="class_id" id="class_id" class="form-select">
                            <option value="">All Classes</option>
                            <?php foreach ($classes as $class) echo "<option value='{$class['id']}' " . ($class_id == $class['id'] ? 'selected' : '') . ">" . htmlspecialchars($class['name']) . "</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-2"><label>Section</label>
                        <select name="section_id" id="section_id" class="form-select">
                            <option value="">All Sections</option>
                        </select>
                    </div>
                    <div class="col-md-2"><label>Month</label><input type="month" name="invoice_month" class="form-control" value="<?php echo htmlspecialchars($invoice_month); ?>"></div>
                    <div class="col-md-1"><label>Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="unpaid" <?php echo ($status == 'unpaid' ? 'selected' : ''); ?>>Unpaid</option>
                            <option value="paid" <?php echo ($status == 'paid' ? 'selected' : ''); ?>>Paid</option>
                            <option value="partially_paid" <?php echo ($status == 'partially_paid' ? 'selected' : ''); ?>>Partially Paid</option>
                        </select>
                    </div>
                    <div class="col-md-2"><button type="submit" class="btn btn-primary w-100 mt-3 mt-md-0">Search</button></div>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list-ul me-1"></i> Invoice List</span>
            <?php if (!empty($invoices)): ?>
            <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#printOptionsModal">
                <i class="fas fa-print me-1"></i> Print All Filtered
            </button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Roll No</th><th>Student Name</th><th>Month</th><th>Gross</th><th>Concession</th><th>Net Payable</th><th>Paid</th><th>Due Date</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (empty($invoices)): ?>
                            <tr><td colspan="10" class="text-center">No invoices found for the selected criteria.</td></tr>
                        <?php else: foreach ($invoices as $invoice): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($invoice['roll_no']); ?></td>
                            <td><?php echo htmlspecialchars($invoice['student_name']); ?></td>
                            <td><?php echo date('M, Y', strtotime($invoice['invoice_month'])); ?></td>
                            <td><?php echo number_format($invoice['gross_amount'], 2); ?></td>
                            <td><?php echo number_format($invoice['concession_amount'], 2); ?></td>
                            <td><?php echo number_format($invoice['total_amount'], 2); ?></td>
                            <td><?php echo number_format($invoice['amount_paid'], 2); ?></td>
                            <td><?php echo date('d M, Y', strtotime($invoice['due_date'])); ?></td>
                            <td>
                                <?php 
                                $status_badge = 'secondary';
                                if ($invoice['status'] == 'paid') $status_badge = 'success';
                                if ($invoice['status'] == 'unpaid') $status_badge = 'danger';
                                if ($invoice['status'] == 'partially_paid') $status_badge = 'warning text-dark';
                                echo '<span class="badge bg-' . $status_badge . '">' . ucfirst(str_replace('_', ' ', $invoice['status'])) . '</span>';
                                ?>
                            </td>
                            <td>
                                <a href="collect_fees.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-success" title="Collect Fee"><i class="fas fa-hand-holding-usd"></i></a>
                                <a href="print_invoice.php?id=<?php echo $invoice['id']; ?>" target="_blank" class="btn btn-sm btn-info" title="Print Invoice"><i class="fas fa-print"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Print Options Modal -->
<div class="modal fade" id="printOptionsModal" tabindex="-1" aria-labelledby="printOptionsModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="printOptionsModalLabel">Select Print Format</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="print-options-form" action="print_bulk_invoices.php" method="GET" target="_blank">
        <div class="modal-body">
            <p>Choose the layout for printing the filtered invoices.</p>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="print_format" id="format1" value="3copy" checked>
              <label class="form-check-label" for="format1">
                <strong>1 Student per Page (3 Copies)</strong>
                <small class="d-block text-muted">A4 page with School, Student, and Bank copies.</small>
              </label>
            </div>
            <div class="form-check mt-2">
              <input class="form-check-input" type="radio" name="print_format" id="format2" value="2copy">
              <label class="form-check-label" for="format2">
                <strong>1 Student per Page (2 Copies)</strong>
                <small class="d-block text-muted">A4 page with School and Student copies.</small>
              </label>
            </div>
            <div class="form-check mt-2">
              <input class="form-check-input" type="radio" name="print_format" id="format3" value="3in1">
              <label class="form-check-label" for="format3">
                <strong>3 Students per Page (1 Copy each)</strong>
                <small class="d-block text-muted">Space-saving layout with 3 invoices on one A4 page.</small>
              </label>
            </div>
            <div id="hidden-filters"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Print Invoices</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const classSelect = document.getElementById('class_id');
    const sectionSelect = document.getElementById('section_id');
    const currentSectionId = '<?php echo $section_id; ?>';

    function fetchSections(classId, targetSelect, selectedId) {
        if (!classId) {
            targetSelect.innerHTML = '<option value="">All Sections</option>';
            return;
        }
        targetSelect.innerHTML = '<option value="">Loading...</option>';
        fetch(`<?php echo BASE_URL; ?>/api/get_sections.php?class_id=${classId}`)
            .then(response => response.json())
            .then(data => {
                targetSelect.innerHTML = '<option value="">All Sections</option>';
                data.forEach(section => {
                    const selected = section.id == selectedId ? 'selected' : '';
                    targetSelect.innerHTML += `<option value="${section.id}" ${selected}>${section.name}</option>`;
                });
            });
    }

    classSelect.addEventListener('change', () => fetchSections(classSelect.value, sectionSelect, null));

    // Initial load for section dropdown if a class is already selected
    if (classSelect.value) {
        fetchSections(classSelect.value, sectionSelect, currentSectionId);
    }

    // For Print Modal
    const printModal = document.getElementById('printOptionsModal');
    if (printModal) {
        printModal.addEventListener('show.bs.modal', function () {
            const hiddenFiltersContainer = document.getElementById('hidden-filters');
            hiddenFiltersContainer.innerHTML = '';
            const params = new URLSearchParams(window.location.search);
            params.forEach((value, key) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                hiddenFiltersContainer.appendChild(input);
            });
        });
    }
});
</script>

<?php require_once '../../footer.php'; ?>
