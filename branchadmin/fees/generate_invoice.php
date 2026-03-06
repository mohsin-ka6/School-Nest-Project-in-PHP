<?php
$page_title = "Generate Fee Invoices";
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

// Fetch classes for dropdown
$stmt_classes = $db->prepare("SELECT id, name FROM classes WHERE branch_id = ? ORDER BY numeric_name ASC");
$stmt_classes->execute([$branch_id]);
$classes = $stmt_classes->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_invoices'])) {
    $session_id = (int)$_POST['session_id'];
    $class_id = (int)$_POST['class_id'];
    $section_id = (int)$_POST['section_id'];
    $invoice_month = trim($_POST['invoice_month']); // Format: YYYY-MM
    $fee_type_ids = isset($_POST['fee_type_ids']) && is_array($_POST['fee_type_ids']) ? $_POST['fee_type_ids'] : [];
    $due_date = trim($_POST['due_date']);

    if (empty($session_id) || empty($class_id) || empty($section_id) || empty($invoice_month) || empty($due_date)) {
        $errors[] = "All fields are required.";
    }

    if (empty($errors)) {
        if (empty($fee_type_ids)) {
            $errors[] = "You must select at least one fee type to generate an invoice.";
        }
    }

    if (empty($errors)) {
        try {
            // 1. Get fee structure for the *selected* fees
            $placeholders = implode(',', array_fill(0, count($fee_type_ids), '?'));
            $stmt_structure = $db->prepare("SELECT fee_type_id, amount FROM class_fee_structure WHERE class_id = ? AND session_id = ? AND branch_id = ? AND fee_type_id IN ($placeholders)");
            $params = array_merge([$class_id, $session_id, $branch_id], $fee_type_ids);
            $stmt_structure->execute($params);
            $fee_structure = $stmt_structure->fetchAll();

            if (empty($fee_structure)) {
                $errors[] = "No valid fee structure found for the selected fees, class, and session. Please define it first.";
            } else {
                // 2. Get all students enrolled in the selected section for the chosen session
                $stmt_students = $db->prepare("SELECT 
                        s.id, s.admission_no, se.roll_no, c.numeric_name as class_numeric,
                        fct.name as concession_name, fct.type as concession_type, fct.value as concession_value
                    FROM student_enrollments se
                    JOIN students s ON se.student_id = s.id
                    JOIN classes c ON se.class_id = c.id
                    LEFT JOIN student_concessions sc ON (s.id = sc.student_id AND se.session_id = sc.session_id)
                    LEFT JOIN fee_concession_types fct ON sc.concession_type_id = fct.id
                    WHERE se.section_id = ? AND se.session_id = ? AND s.branch_id = ?
                ");
                $stmt_students->execute([$section_id, $session_id, $branch_id]);
                $students = $stmt_students->fetchAll();

                if (empty($students)) {
                    $errors[] = "No students found enrolled in the selected section for this session.";
                } else {
                    $db->beginTransaction();
                    $gross_amount = array_sum(array_column($fee_structure, 'amount'));
                    $generated_count = 0;

                    foreach ($students as $student) {
                        $student_id = $student['id'];
                        $concession_amount = 0;
                        $concession_details = null;
                        $net_amount = $gross_amount;

                        // Calculate concession if applicable
                        if ($student['concession_name']) {
                            if ($student['concession_type'] == 'percentage') {
                                $concession_amount = ($gross_amount * $student['concession_value']) / 100;
                                $concession_details = $student['concession_name'] . ' (' . $student['concession_value'] . '%)';
                            } elseif ($student['concession_type'] == 'fixed') {
                                $concession_amount = $student['concession_value'];
                                $concession_details = $student['concession_name'] . ' (Fixed)';
                            }
                            // Ensure concession doesn't exceed gross amount
                            $concession_amount = min($gross_amount, $concession_amount);
                            $net_amount = $gross_amount - $concession_amount;
                        }
                        
                        // Generate Barcode Data
                        $barcode_data = 
                            $student['admission_no'] . 
                            $student['class_numeric'] . 
                            ($student['roll_no'] ?? '0') . 
                            date('dmY', strtotime($due_date));

                        // 3. Create main invoice record (ignore if already exists for that month/session)
                        $stmt_invoice = $db->prepare(
                            "INSERT INTO fee_invoices (branch_id, session_id, student_id, class_id, invoice_month, gross_amount, concession_amount, concession_details, total_amount, due_date, barcode) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE id=id" // This does nothing if a duplicate is found
                        );
                        $stmt_invoice->execute([$branch_id, $session_id, $student_id, $class_id, $invoice_month, $gross_amount, $concession_amount, $concession_details, $net_amount, $due_date, $barcode_data]);
                        
                        if ($stmt_invoice->rowCount() > 0) {
                            $invoice_id = $db->lastInsertId();
                            $generated_count++;

                            // 4. Create invoice detail records
                            $stmt_details = $db->prepare("INSERT INTO fee_invoice_details (invoice_id, fee_type_id, amount) VALUES (?, ?, ?)");
                            foreach ($fee_structure as $fee) {
                                $stmt_details->execute([$invoice_id, $fee['fee_type_id'], $fee['amount']]);
                            }
                        }
                    }

                    $db->commit();
                    if ($generated_count > 0) {
                        $_SESSION['success_message'] = "Successfully generated {$generated_count} new invoices for the selected section.";
                    } else {
                        $_SESSION['success_message'] = "Invoices for this month, session, and section already exist. No new invoices were generated.";
                    }
                    redirect("generate_invoice.php");
                }
            }
        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }
}

if(isset($_SESSION['success_message'])) { $success_message = $_SESSION['success_message']; unset($_SESSION['success_message']); }

require_once '../../header.php';
?>

<?php require_once '../../sidebar_branchadmin.php'; ?>
<?php require_once '../../navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Generate Invoice</li>
    </ol>

    <?php if ($success_message): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo $success_message; ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
    <?php if (!empty($errors)) echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; ?>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-file-invoice-dollar me-1"></i> Generate Invoices for a Section</div>
        <div class="card-body">
            <form action="" method="POST" id="generate-invoice-form">
                <div class="row mb-3">
                    <div class="col-md-3 mb-3"><label>Academic Session*</label>
                        <select name="session_id" id="session_id" class="form-select" required>
                            <option value="">-- Select Session --</option>
                            <?php foreach ($sessions as $session) echo "<option value='{$session['id']}'>" . htmlspecialchars($session['name']) . "</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3"><label>Class*</label>
                        <select name="class_id" id="class_id" class="form-select" required disabled>
                            <option value="">-- Select Class --</option>
                            <?php foreach ($classes as $class) echo "<option value='{$class['id']}'>" . htmlspecialchars($class['name']) . "</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3"><label>Section*</label>
                        <select name="section_id" id="section_id" class="form-select" required disabled>
                            <option value="">-- Select Class First --</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3"><label>Invoice for Month*</label><input type="month" name="invoice_month" class="form-control" required value="<?php echo date('Y-m'); ?>"></div>
                    <div class="col-md-3 mb-3"><label>Due Date*</label><input type="date" name="due_date" class="form-control" required></div>
                </div>

                <div id="fee-types-container" class="mb-3" style="display: none;">
                    <h5>Select Fee Types to Include</h5>
                    <div id="fee-types-list" class="p-3 border rounded" style="max-height: 250px; overflow-y: auto;">
                        <!-- Fee types will be loaded here by JS -->
                    </div>
                </div>
                <button type="submit" name="generate_invoices" class="btn btn-primary">Generate Invoices</button>
            </form>
        </div>
        <div class="card-footer">
            <p class="small text-muted mb-0">This will generate invoices for all students in the selected section based on the class's fee structure for the chosen academic session. If an invoice for a student for the selected month and session already exists, it will be skipped.</p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sessionSelect = document.getElementById('session_id');
    const classSelect = document.getElementById('class_id');
    const sectionSelect = document.getElementById('section_id');
    const feeTypesContainer = document.getElementById('fee-types-container');
    const feeTypesList = document.getElementById('fee-types-list');

    function resetSections() {
        sectionSelect.innerHTML = '<option value="">-- Select Class First --</option>';
        sectionSelect.disabled = true;
    }

    function resetFeeTypes() {
        feeTypesContainer.style.display = 'none';
        feeTypesList.innerHTML = '';
    }

    sessionSelect.addEventListener('change', function() {
        classSelect.value = '';
        classSelect.disabled = this.value === '';
        resetSections();
        resetFeeTypes();
    });

    classSelect.addEventListener('change', function() {
        const classId = this.value;
        const sessionId = sessionSelect.value;

        // Reset dependent fields
        resetSections();
        resetFeeTypes();

        if (!classId || !sessionId) {
            return;
        }

        // Fetch sections
        sectionSelect.disabled = false;
        sectionSelect.innerHTML = '<option value="">Loading Sections...</option>';
        fetch(`<?php echo BASE_URL; ?>/api/get_sections.php?class_id=${classId}`)
            .then(response => response.json())
            .then(data => {
                sectionSelect.innerHTML = '<option value="">-- Select Section --</option>';
                data.forEach(section => {
                    sectionSelect.innerHTML += `<option value="${section.id}">${section.name}</option>`;
                });
            })
            .catch(err => {
                sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
            });

        // Fetch fee types
        feeTypesContainer.style.display = 'block';
        feeTypesList.innerHTML = '<p>Loading fee types...</p>';
        fetch(`<?php echo BASE_URL; ?>/api/get_class_fees.php?session_id=${sessionId}&class_id=${classId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    feeTypesList.innerHTML = `<p class="text-danger">${data.error}</p>`;
                    return;
                }
                if (data.length === 0) {
                    feeTypesList.innerHTML = '<p class="text-muted">No fee structure defined for this class in this session.</p>';
                    return;
                }
                feeTypesList.innerHTML = '';
                data.forEach(fee => {
                    const isChecked = fee.is_default == 1 ? 'checked' : '';
                    feeTypesList.innerHTML += `
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="fee_type_ids[]" value="${fee.fee_type_id}" id="fee-${fee.fee_type_id}" ${isChecked}>
                            <label class="form-check-label" for="fee-${fee.fee_type_id}">
                                ${fee.fee_type_name} (PKR ${parseFloat(fee.amount).toFixed(2)})
                            </label>
                        </div>`;
                });
            })
            .catch(err => {
                feeTypesList.innerHTML = '<p class="text-danger">Error loading fee types.</p>';
            });
    });
});
</script>

<?php require_once '../../footer.php'; ?>
