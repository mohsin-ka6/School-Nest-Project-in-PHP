<?php
$page_title = "Complaints";
require_once dirname(__DIR__, 2) . '/config.php';
require_once ROOT_PATH . '/functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$user_id = $_SESSION['user_id'];
$errors = [];

// Handle delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $complaint_id = (int)$_GET['id'];
    try {
        $stmt = $db->prepare("DELETE FROM complaints WHERE id = ? AND branch_id = ?");
        $stmt->execute([$complaint_id, $branch_id]);
        $_SESSION['success_message'] = "Complaint deleted successfully.";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error deleting complaint: " . $e->getMessage();
    }
    redirect('complaints.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $complaint_id = isset($_POST['complaint_id']) ? (int)$_POST['complaint_id'] : 0;
    $complaint_source = trim($_POST['complaint_source']);
    $complaint_by = trim($_POST['complaint_by']);
    $complaint_date = trim($_POST['complaint_date']);
    $description = trim($_POST['description']);
    $source_person_id = !empty($_POST['source_person_id']) ? (int)$_POST['source_person_id'] : null;
    $source_student_ids = null;

    if (empty($complaint_source) || empty($complaint_date) || empty($description)) {
        $errors[] = "Complaint Source, Date, and Description are required.";
    }

    if ($complaint_source === 'student') {
        if (!empty($_POST['student_ids'])) {
            $source_student_ids = implode(',', $_POST['student_ids']);
            // For simplicity, we'll just list the number of students in the 'complaint_by' field
            $complaint_by = count($_POST['student_ids']) . ' student(s)';
        } else {
            $errors[] = "You must select at least one student for the complaint.";
        }
    } elseif ($complaint_source === 'teacher' && empty($source_person_id)) {
        $errors[] = "You must select a teacher/staff member for the complaint.";
    } elseif (empty($complaint_by)) {
        $errors[] = "The 'Complaint By' name is required.";
    }


    if (empty($errors)) {
        try {
            if ($complaint_id > 0) { // Update existing complaint
                $stmt = $db->prepare(
                    "UPDATE complaints SET complaint_source=?, source_person_id=?, source_student_ids=?, complaint_by=?, phone=?, complaint_date=?, description=?, action_taken=?, notes=?, complaint_type=?, status=? 
                     WHERE id=? AND branch_id=?"
                );
                $stmt->execute([
                    $complaint_source, $source_person_id, $source_student_ids, $complaint_by, trim($_POST['phone']), $complaint_date, $description,
                    trim($_POST['action_taken']), trim($_POST['notes']), trim($_POST['complaint_type']), trim($_POST['status']),
                    $complaint_id, $branch_id
                ]);
                
                $_SESSION['success_message'] = "Complaint updated successfully!";
            } else { // Insert new complaint
                // Generate new complaint number
                $stmt_last_no = $db->prepare("SELECT complaint_no FROM complaints WHERE branch_id = ? ORDER BY id DESC LIMIT 1");
                $stmt_last_no->execute([$branch_id]);
                $last_no = $stmt_last_no->fetchColumn();
                if ($last_no) {
                    $number = (int)substr($last_no, 3) + 1;
                } else {
                    $number = 1;
                }
                $new_complaint_no = 'cmp' . str_pad($number, 6, '0', STR_PAD_LEFT);

                $stmt = $db->prepare(
                    "INSERT INTO complaints (complaint_no, branch_id, complaint_source, source_person_id, source_student_ids, complaint_by, phone, complaint_date, description, action_taken, notes, complaint_type, status, created_by) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $new_complaint_no, $branch_id, $complaint_source, $source_person_id, $source_student_ids, $complaint_by, trim($_POST['phone']), $complaint_date, $description,
                    trim($_POST['action_taken']), trim($_POST['notes']), trim($_POST['complaint_type']),
                    trim($_POST['status']), $user_id
                ]);
                $_SESSION['success_message'] = "Complaint logged successfully!";
            }
            redirect('complaints.php');
        } catch (PDOException $e) {
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }
}

// Fetch a specific complaint for editing
$edit_complaint = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $complaint_id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM complaints WHERE id = ? AND branch_id = ?");
    $stmt->execute([$complaint_id, $branch_id]);
    $edit_complaint = $stmt->fetch();

    // If editing a student complaint, fetch student details for display
    if ($edit_complaint && $edit_complaint['complaint_source'] === 'student' && !empty($edit_complaint['source_student_ids'])) {
        $student_ids = explode(',', $edit_complaint['source_student_ids']);
        $placeholders = implode(',', array_fill(0, count($student_ids), '?'));
        $stmt_students = $db->prepare("
            SELECT u.full_name, c.name as class_name, s.name as section_name
            FROM student_enrollments se
            JOIN users u ON se.student_id = u.id
            JOIN sections s ON se.section_id = s.id
            JOIN classes c ON se.class_id = c.id
            JOIN academic_sessions acs ON se.session_id = acs.id
            WHERE se.student_id IN ($placeholders) AND acs.is_current = 1 AND acs.branch_id = ? GROUP BY u.id");
        $stmt_students->execute(array_merge($student_ids, [$branch_id]));
        $edit_complaint['student_details'] = $stmt_students->fetchAll();
    }
}

// Fetch data for dropdowns
$stmt_classes = $db->prepare("SELECT id, name FROM classes WHERE branch_id = ? ORDER BY numeric_name ASC");
$stmt_classes->execute([$branch_id]);
$classes = $stmt_classes->fetchAll();

$stmt_staff = $db->prepare("SELECT id, full_name FROM users WHERE branch_id = ? AND role IN ('teacher', 'branchadmin') ORDER BY full_name ASC");
$stmt_staff->execute([$branch_id]);
$staff_list = $stmt_staff->fetchAll();


// --- Filtering Logic ---
$filter_status = $_GET['filter_status'] ?? '';
$filter_start_date = $_GET['filter_start_date'] ?? '';
$filter_end_date = $_GET['filter_end_date'] ?? '';

// --- Pagination Logic ---
$limit = 15; // Number of complaints per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;


$sql_conditions = [];
$sql_params = [$branch_id];

if (!empty($filter_status)) {
    $sql_conditions[] = "status = ?";
    $sql_params[] = $filter_status;
}
if (!empty($filter_start_date)) {
    $sql_conditions[] = "complaint_date >= ?";
    $sql_params[] = $filter_start_date;
}
if (!empty($filter_end_date)) {
    $sql_conditions[] = "complaint_date <= ?";
    $sql_params[] = $filter_end_date;
}

$where_clause = "";
if (!empty($sql_conditions)) {
    $where_clause = " AND " . implode(" AND ", $sql_conditions);
}

// Get total count for pagination
$count_sql = "SELECT COUNT(*) FROM complaints WHERE branch_id = ? {$where_clause}";
$count_stmt = $db->prepare($count_sql);
$count_stmt->execute($sql_params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch complaints for the current page
$sql = "SELECT id, complaint_no, complaint_by, complaint_source, complaint_date, description, status FROM complaints WHERE branch_id = ? {$where_clause} ORDER BY complaint_date DESC LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);

// Bind the filter parameters (which can be strings or dates)
$param_index = 1;
foreach ($sql_params as $param) {
    $stmt->bindValue($param_index++, $param);
}
// Bind the LIMIT and OFFSET parameters as integers
$stmt->bindValue($param_index++, $limit, PDO::PARAM_INT);
$stmt->bindValue($param_index++, $offset, PDO::PARAM_INT);

$stmt->execute();
$complaints = $stmt->fetchAll();

require_once ROOT_PATH . '/header.php';
?>

<?php require_once ROOT_PATH . '/sidebar_branchadmin.php'; ?>
<?php require_once ROOT_PATH . '/navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Complaints</li>
    </ol>

    <?php display_flash_messages(); ?>
    <?php if (!empty($errors)) echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; ?>

    <!-- Form to add new complaint -->
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-comment-dots me-1"></i> <?php echo $edit_complaint ? 'Edit' : 'Log New'; ?> Complaint</div>
        <div class="card-body">
            <form action="complaints.php" method="POST">
                <input type="hidden" name="complaint_id" value="<?php echo $edit_complaint['id'] ?? 0; ?>">
                <input type="hidden" name="source_person_id" id="source_person_id">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="complaint_source" class="form-label">Complaint Source*</label>
                        <select name="complaint_source" id="complaint_source" class="form-select" required <?php if ($edit_complaint) echo 'readonly'; ?>>
                            <option value="">-- Select Source --</option>
                            <option value="student" <?php echo (($edit_complaint['complaint_source'] ?? '') == 'student') ? 'selected' : ''; ?>>Student</option>
                            <option value="parent" <?php echo (($edit_complaint['complaint_source'] ?? '') == 'parent') ? 'selected' : ''; ?>>Parent</option>
                            <option value="teacher" <?php echo (($edit_complaint['complaint_source'] ?? '') == 'teacher') ? 'selected' : ''; ?>>Teacher / Staff</option>
                            <option value="public" <?php echo (($edit_complaint['complaint_source'] ?? '') == 'public') ? 'selected' : ''; ?>>Public</option>
                        </select>
                        <?php if ($edit_complaint): ?>
                            <input type="hidden" name="complaint_source" value="<?php echo htmlspecialchars($edit_complaint['complaint_source']); // Keep submitting the value ?>">
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 mb-3"><label>Complaint Date*</label><input type="date" name="complaint_date" class="form-control" required value="<?php echo htmlspecialchars($edit_complaint['complaint_date'] ?? date('Y-m-d')); ?>"></div>
                </div>

                <!-- Dynamic Fields Container -->
                <div id="dynamic-fields-container"></div>

                <div class="row" id="common-fields">
                    <div class="col-md-4 mb-3">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($edit_complaint['phone'] ?? ''); ?>">
                        <div id="complaint-history" class="mt-2"></div>
                    </div>
                    <div class="col-md-4 mb-3" id="complaint_by_container">
                        <label for="complaint_by">Complaint By*</label>
                        <input type="text" id="complaint_by" name="complaint_by" class="form-control" value="<?php echo htmlspecialchars($edit_complaint['complaint_by'] ?? ''); ?>" required>
                    </div>
                </div>

                <!-- Student Specific Fields (hidden by default) -->
                <div id="student-fields-container" class="mb-3" style="display: none;">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="student_class_id" class="form-label">Class</label>
                            <select id="student_class_id" class="form-select">
                                <option value="">-- Select Class --</option>
                                <?php foreach ($classes as $class): ?><option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="student_section_id" class="form-label">Section</label>
                            <select id="student_section_id" class="form-select"></select>
                        </div>
                    </div>
                    <div id="student-list-container" style="display: none;"></div>
                    
                    <!-- Container to show selected students in edit mode -->
                    <?php if ($edit_complaint && !empty($edit_complaint['student_details'])): ?>
                        <div id="edit-student-info">
                            <label class="form-label">Students Involved:</label>
                            <ul class="list-group">
                                <?php foreach ($edit_complaint['student_details'] as $student): ?>
                                    <li class="list-group-item list-group-item-info">
                                        <strong><?php echo htmlspecialchars($student['full_name']); ?></strong> (Class: <?php echo htmlspecialchars($student['class_name']); ?>, Section: <?php echo htmlspecialchars($student['section_name']); ?>)
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mb-3"><label>Description*</label><textarea name="description" class="form-control" rows="2" required><?php echo htmlspecialchars($edit_complaint['description'] ?? ''); ?></textarea></div>
                <div class="mb-3"><label>Action Taken</label><textarea name="action_taken" class="form-control" rows="2"><?php echo htmlspecialchars($edit_complaint['action_taken'] ?? ''); ?></textarea></div>
                <div class="row">
                    <div class="col-md-4 mb-3"><label>Complaint Type</label><input type="text" name="complaint_type" class="form-control" placeholder="e.g., Academic, Transport" value="<?php echo htmlspecialchars($edit_complaint['complaint_type'] ?? ''); ?>"></div>
                    <div class="col-md-4 mb-3"><label>Status</label><select name="status" class="form-select"><option value="pending" <?php echo (($edit_complaint['status'] ?? '') == 'pending') ? 'selected' : ''; ?>>Pending</option><option value="in_progress" <?php echo (($edit_complaint['status'] ?? '') == 'in_progress') ? 'selected' : ''; ?>>In Progress</option><option value="resolved" <?php echo (($edit_complaint['status'] ?? '') == 'resolved') ? 'selected' : ''; ?>>Resolved</option></select></div>
                </div>
                <div class="mb-3"><label>Notes</label><textarea name="notes" class="form-control" rows="2"><?php echo htmlspecialchars($edit_complaint['notes'] ?? ''); ?></textarea></div>
                <button type="submit" class="btn btn-primary"><?php echo $edit_complaint ? 'Update' : 'Log'; ?> Complaint</button>
                <?php if ($edit_complaint): ?><a href="complaints.php" class="btn btn-secondary">Cancel Edit</a><?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Table of complaints -->
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-filter me-1"></i> Filter Complaints</div>
        <div class="card-body">
            <form action="complaints.php" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="filter_start_date" class="form-label">From Date</label>
                    <input type="date" id="filter_start_date" name="filter_start_date" class="form-control" value="<?php echo htmlspecialchars($filter_start_date); ?>">
                </div>
                <div class="col-md-3">
                    <label for="filter_end_date" class="form-label">To Date</label>
                    <input type="date" id="filter_end_date" name="filter_end_date" class="form-control" value="<?php echo htmlspecialchars($filter_end_date); ?>">
                </div>
                <div class="col-md-3">
                    <label for="filter_status" class="form-label">Status</label>
                    <select id="filter_status" name="filter_status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo ($filter_status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="in_progress" <?php echo ($filter_status == 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                        <option value="resolved" <?php echo ($filter_status == 'resolved') ? 'selected' : ''; ?>>Resolved</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i> Filter</button>
                    <a href="complaints.php" class="btn btn-secondary"><i class="fas fa-times me-1"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-table me-1"></i> Complaint List</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th>Complaint No</th><th>Complaint By</th><th>Date</th><th>Description</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (empty($complaints)): ?>
                            <tr><td colspan="6" class="text-center">No complaints logged.</td></tr>
                        <?php else: foreach ($complaints as $complaint): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($complaint['complaint_no']); ?></strong></td>
                                <td><?php echo htmlspecialchars($complaint['complaint_by']); ?><br><small class="text-muted"><?php echo ucfirst($complaint['complaint_source']); ?></small></td>
                                <td><?php echo date('d M, Y', strtotime($complaint['complaint_date'])); ?></td>
                                <td style="max-width: 300px;"><?php echo htmlspecialchars($complaint['description']); ?></td>
                                <td>
                                    <?php 
                                    $status_class = ['pending' => 'warning', 'in_progress' => 'info', 'resolved' => 'success'];
                                    echo '<span class="badge bg-' . ($status_class[$complaint['status']] ?? 'light') . '">' . ucfirst($complaint['status']) . '</span>';
                                    ?>
                                </td>
                                <?php
                                    // Temporary debugging output
                                    echo "<!-- Complaint ID: " . htmlspecialchars($complaint['id']) . " -->";
                                    echo "<!-- Complaint No: " . htmlspecialchars($complaint['complaint_no']) . " -->";
                                    // You can add more debugging info here as needed
                                ?>
                                <td class="d-flex gap-1">
                                    <a href="view_complaint.php?id=<?php echo $complaint['id']; ?>" class="btn btn-sm btn-info" title="View Details"><i class="fas fa-eye"></i></a>
                                    <a href="complaints.php?action=edit&id=<?php echo $complaint['id']; ?>" class="btn btn-sm btn-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                    <form action="complaints.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this complaint?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="complaint_id" value="<?php echo $complaint['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav>
                <ul class="pagination justify-content-center">
                    <?php
                    // Build query string for pagination links
                    $query_params = [
                        'filter_start_date' => $filter_start_date,
                        'filter_end_date' => $filter_end_date,
                        'filter_status' => $filter_status
                    ];
                    $pagination_query = http_build_query(array_filter($query_params));
                    ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>&<?php echo $pagination_query; ?>"><?php echo $i; ?></a></li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const complaintSource = document.getElementById('complaint_source');
    const dynamicFieldsContainer = document.getElementById('dynamic-fields-container');
    const commonFields = document.getElementById('common-fields');
    const complaintByContainer = document.getElementById('complaint_by_container');
    const complaintByNameInput = document.getElementById('complaint_by');
    const phoneInput = document.getElementById('phone');
    const sourcePersonIdInput = document.getElementById('source_person_id');
    const editStudentInfo = document.getElementById('edit-student-info');
    const studentFieldsContainer = document.getElementById('student-fields-container');
    const isEditMode = <?php echo $edit_complaint ? 'true' : 'false'; ?>;

    complaintSource.addEventListener('change', function () {
        const source = this.value;
        // Reset all fields
        dynamicFieldsContainer.innerHTML = '';
        studentFieldsContainer.style.display = 'none';
        if (editStudentInfo) editStudentInfo.style.display = 'none';
        complaintByContainer.style.display = 'block';
        complaintByNameInput.required = true;
        sourcePersonIdInput.value = '';

        // Only reset fields if not in edit mode
        if (!isEditMode) {
            complaintByNameInput.value = '';
            phoneInput.value = '';
        }
        if (source === 'public') {
            // No special fields needed, just the common ones
        } else if (source === 'teacher') {
            complaintByContainer.style.display = 'none';
            complaintByNameInput.required = false;
            const teacherSelect = `
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="teacher_id" class="form-label">Select Teacher/Staff*</label>
                        <select id="teacher_id" class="form-select" required>
                            <option value="">-- Loading Staff --</option>
                        </select>
                    </div>
                </div>`;
            dynamicFieldsContainer.innerHTML = teacherSelect;
            fetchStaff();
        } else if (source === 'parent') {
            complaintByNameInput.readOnly = true;
            complaintByNameInput.required = true; // Name is still required, but filled automatically
        } else if (source === 'student') {
            complaintByContainer.style.display = 'none';
            studentFieldsContainer.style.display = 'block';
            complaintByNameInput.required = false;
            if (isEditMode && editStudentInfo) {
                editStudentInfo.style.display = 'block';
            }
        }
    });

    function fetchStaff() {
        const teacherSelect = document.getElementById('teacher_id');
        fetch(`<?php echo BASE_URL; ?>/api/get_staff.php`)
            .then(res => {
                if (!res.ok) throw new Error('Network response was not ok');
                return res.json();
            })
            .then(staffList => {
                let options = '<option value="">-- Select Staff --</option>';
                staffList.forEach(staff => {
                    options += `<option value="${staff.id}" data-name="${staff.full_name}">${staff.full_name} (${staff.role})</option>`;
                });
                teacherSelect.innerHTML = options;

                // If in edit mode, select the correct teacher
                <?php if ($edit_complaint && $edit_complaint['complaint_source'] === 'teacher' && $edit_complaint['source_person_id']): ?>
                    teacherSelect.value = '<?php echo $edit_complaint['source_person_id']; ?>';
                <?php endif; ?>

                teacherSelect.addEventListener('change', function() {
                    sourcePersonIdInput.value = this.value || '';
                    complaintByNameInput.value = this.options[this.selectedIndex].getAttribute('data-name');
                });
            });
    }

    // --- Student Fields Logic ---
    const studentClassSelect = document.getElementById('student_class_id');
    const studentSectionSelect = document.getElementById('student_section_id');
    const studentListContainer = document.getElementById('student-list-container');

    studentClassSelect.addEventListener('change', function() {
        const classId = this.value;
        studentSectionSelect.innerHTML = '<option value="">Loading...</option>';
        studentListContainer.style.display = 'none';
        studentListContainer.innerHTML = '';
        if (!classId) {
            studentSectionSelect.innerHTML = '<option value="">-- Select Class First --</option>';
            return;
        }

        fetch(`<?php echo BASE_URL; ?>/api/get_sections.php?class_id=${classId}&branch_id=<?php echo $branch_id; ?>`)
            .then(res => res.json()).then(data => {
                let options = '<option value="">-- Select Section --</option>';
                data.forEach(sec => options += `<option value="${sec.id}">${sec.name}</option>`);
                studentSectionSelect.innerHTML = options;

                <?php if ($edit_complaint && $edit_complaint['complaint_source'] === 'student'): ?>
                // In edit mode, we can't know the section, so we don't pre-select it. The user must re-select class/section to see students.
                <?php endif; ?>
            });
    });

    studentSectionSelect.addEventListener('change', function() {
        const sectionId = this.value;
        if (!sectionId) {
            studentListContainer.style.display = 'none';
            studentListContainer.innerHTML = '';
            return;
        }
        studentListContainer.innerHTML = '<div><i class="fas fa-spinner fa-spin"></i> Loading students...</div>';
        studentListContainer.style.display = 'block';

        fetch(`<?php echo BASE_URL; ?>/api/get_students_by_section.php?section_id=${sectionId}`)
            .then(res => res.json()).then(data => {
                if (data.length > 0) {
                    <?php
                        $selected_student_ids = '[]';
                        if ($edit_complaint && !empty($edit_complaint['source_student_ids'])) {
                            $selected_student_ids = json_encode(explode(',', $edit_complaint['source_student_ids']));
                        }
                    ?>
                    const selectedStudentIds = <?php echo $selected_student_ids; ?>.map(String); // Ensure IDs are strings for comparison
                    let studentHtml = '<label class="form-label">Select Student(s)*</label><div class="student-checkbox-group">';
                    data.forEach(student => {
                        const isChecked = selectedStudentIds.includes(String(student.id)) ? 'checked' : '';
                        studentHtml += `<div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="student_ids[]" value="${student.id}" id="student_${student.id}" ${isChecked}><label class="form-check-label" for="student_${student.id}">${student.full_name}</label></div>`;
                    });
                    studentHtml += '</div>';
                    studentListContainer.innerHTML = studentHtml;
                } else {
                    studentListContainer.innerHTML = '<div class="alert alert-warning">No students found in this section.</div>';
                }
            });
    });

    if (isEditMode) {
        // Trigger the change event to set up the form correctly for the existing complaint
        complaintSource.dispatchEvent(new Event('change'));
        // Make the source dropdown visually read-only
        complaintSource.style.pointerEvents = 'none';
        complaintSource.style.backgroundColor = '#e9ecef';
    }


    // --- Original Phone History Logic ---
    let debounceTimer;

    phoneInput.addEventListener('keyup', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const phone = phoneInput.value.trim();
            const source = complaintSource.value;

            // Decide which action to take based on the selected source
            if (source === 'parent') {
                searchParentByPhone(phone);
            } else {
                // For other sources, search for general complaint history
                searchComplaintHistory(phone);
            }
        }, 300); // Debounce for 300ms
    });

    function searchComplaintHistory(phone) {
        const complaintHistoryContainer = document.getElementById('complaint-history');
        if (phone.length !== 11) {
            complaintHistoryContainer.innerHTML = '';
            return;
        }
        complaintHistoryContainer.innerHTML = '<div class="text-muted small"><i class="fas fa-spinner fa-spin"></i> Searching history...</div>';

        fetch(`<?php echo BASE_URL; ?>/api/get_complaint_history.php?phone=${phone}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    complaintHistoryContainer.innerHTML = `<div class="text-danger small">${data.error}</div>`;
                    return;
                }

                if (data.length > 0) {
                    let historyHtml = '<ul class="list-group list-group-flush small">';
                    historyHtml += '<li class="list-group-item list-group-item-secondary fw-bold">Recent Complaint History:</li>';
                    data.forEach(item => {
                        const itemDate = new Date(item.complaint_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                        historyHtml += `<li class="list-group-item"><strong>${itemDate} (${item.complaint_no})</strong>: ${item.description || 'No description'} <span class="badge bg-info float-end">${item.status}</span></li>`;
                    });
                    historyHtml += '</ul>';
                    complaintHistoryContainer.innerHTML = historyHtml;
                } else {
                    complaintHistoryContainer.innerHTML = '<div class="text-muted small">No previous complaint history found for this number.</div>';
                }
            })
            .catch(error => {
                console.error('Error fetching complaint history:', error);
                complaintHistoryContainer.innerHTML = '<div class="text-danger small">Error loading history.</div>';
            });
    }

    function searchParentByPhone(phone) {
        if (phone.length !== 11) {
            complaintByNameInput.value = '';
            sourcePersonIdInput.value = '';
            return;
        }
        fetch(`<?php echo BASE_URL; ?>/api/get_parent_by_phone.php?phone=${phone}`)
            .then(res => res.json())
            .then(data => {
                if (data && data.id) {
                    complaintByNameInput.value = data.father_name;
                    sourcePersonIdInput.value = data.id;
                } else {
                    complaintByNameInput.value = 'Parent not found';
                    sourcePersonIdInput.value = '';
                }
            });
    }

});

</script>

<?php require_once ROOT_PATH . '/footer.php'; ?>