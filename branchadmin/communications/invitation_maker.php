<?php
$page_title = "Invitation Card Maker";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$students = [];
$invitation_details = null;
$other_guests = [];
$blank_cards_count = 0;

// --- DATA FETCHING for FILTERS ---
$stmt_classes = $db->prepare("SELECT id, name FROM classes WHERE branch_id = ? ORDER BY numeric_name ASC, name ASC");
$stmt_classes->execute([$branch_id]);
$classes = $stmt_classes->fetchAll();

$class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
$section_id = isset($_POST['section_id']) ? (int)$_POST['section_id'] : 0;

// --- Fetch Templates from DB ---
$stmt_templates = $db->prepare("SELECT id, title, language, content FROM invitation_templates WHERE branch_id = ? ORDER BY title ASC");
$stmt_templates->execute([$branch_id]);
$db_templates = $stmt_templates->fetchAll();
$templates_js = [];
foreach ($db_templates as $tpl) $templates_js[$tpl['id']] = $tpl;

// Handle form submission to generate invitations
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_invitations'])) {
    $invitation_details = [
        'title' => trim($_POST['title']),
        'event_date' => trim($_POST['event_date']),
        'event_time' => trim($_POST['event_time']),
        'venue' => trim($_POST['venue']),
        'message' => trim($_POST['message']),
        'recipient_type' => $_POST['recipient_type'] ?? 'students'
    ];

    if ($invitation_details['recipient_type'] === 'students') {
        if ($class_id && $section_id) {
            $stmt = $db->prepare("
                SELECT 
                    u.full_name as student_name,
                    p.father_name,
                    c.name as class_name,
                    sec.name as section_name
                FROM students s
                JOIN users u ON s.user_id = u.id
                JOIN student_enrollments se ON s.id = se.student_id
                JOIN academic_sessions sess ON se.session_id = sess.id AND sess.is_current = 1
                JOIN classes c ON se.class_id = c.id
                JOIN sections sec ON se.section_id = sec.id
                LEFT JOIN parents p ON s.parent_id = p.id
                WHERE s.branch_id = :branch_id AND se.class_id = :class_id AND se.section_id = :section_id
                ORDER BY u.full_name ASC
            ");
            $stmt->execute([':branch_id' => $branch_id, ':class_id' => $class_id, ':section_id' => $section_id]);
            $students = $stmt->fetchAll();
        } else {
            $_SESSION['error_message'] = "Please select a class and section to generate invitations for students.";
        }
    } elseif ($invitation_details['recipient_type'] === 'others') {
        $guest_names = trim($_POST['guest_names']);
        if (!empty($guest_names)) {
            $other_guests = array_filter(array_map('trim', explode("\n", $guest_names)));
            if (empty($other_guests)) {
                $_SESSION['error_message'] = "Please enter at least one guest name.";
            }
        } else {
            $_SESSION['error_message'] = "Guest names cannot be empty.";
        }
    } elseif ($invitation_details['recipient_type'] === 'blank') {
        $blank_cards_count = (int)($_POST['blank_card_count'] ?? 0);
        if ($blank_cards_count <= 0) {
            $_SESSION['error_message'] = "Please enter a valid number of blank cards to generate.";
            $blank_cards_count = 0;
        } elseif ($blank_cards_count > 200) { // Safety limit
            $_SESSION['error_message'] = "Cannot generate more than 200 blank cards at a time.";
            $blank_cards_count = 0;
        }
    }
}

require_once '../../header.php';
?>

<style>
    .invitation-card {
        border: 2px solid #1d3557;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        background: #fff;
        position: relative;        
        page-break-inside: avoid;
        min-height: 9.5cm; /* Minimum height for 3 cards per A4 page */
        direction: ltr; /* Default direction */
    }
    .invitation-card.rtl {
        direction: rtl;
        font-family: 'Noto Nastaliq Urdu', 'Arial', sans-serif;
    }
    .invitation-card::before {
        content: '';
        position: absolute;
        top: -50px;
        left: -50px;
        width: 150px;
        height: 150px;
        background: #e8f5fd;
        border-radius: 50%;
        opacity: 0.5;
    }
    .card-header-inv {
        text-align: center;
        border-bottom: 1px dashed #ccc;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }
    .card-header-inv h5 {
        color: #1d3557;
        font-weight: bold;
        margin: 0;
    }
    .card-header-inv p {
        margin: 0;
        font-size: 0.9rem;
    }
    .invitation-body p {
        font-size: 0.95rem;
        margin-bottom: 10px;
    }
    .event-details {
        margin-top: 15px;
        font-size: 0.9rem;
    }
    .event-details strong {
        color: #1d3557;
    }
    .print-container {
        display: grid;
        grid-template-columns: 1fr 1fr; /* Two columns for screen view */
        gap: 20px;
    }

    @media print {
        body * {
            visibility: hidden;
        }
        #print-area, #print-area * {
            visibility: visible;
        }
        #print-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .invitation-card {
            width: 19cm; /* A4 width minus margins */
            margin: 0.5cm auto; /* Center with some margin */
            border: 1px solid #ccc;
        }
        .print-container {
            grid-template-columns: 1fr; /* Single column for printing */
            gap: 0;
        }
        .btn { display: none; }
    }
</style>

<?php require_once '../../sidebar_branchadmin.php'; ?>
<?php require_once '../../navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="manage_news.php">Communication</a></li>
        <li class="breadcrumb-item active">Invitation Maker</li>
    </ol>

    <?php display_flash_messages(); ?>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-envelope-open-text me-1"></i> Create Invitations</div>
        <div class="card-body">
            <form action="" method="POST">
                <h5 class="text-primary">1. Event Details</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Select Template</label>
                        <select id="template_select" class="form-select">
                            <option value="">-- Write Custom Message --</option>
                            <?php foreach ($db_templates as $tpl): ?>
                                <option value="<?php echo $tpl['id']; ?>" data-lang="<?php echo $tpl['language']; ?>">
                                    <?php echo htmlspecialchars($tpl['title']) . ' (' . ($tpl['language'] == 'ur' ? 'Urdu' : 'English') . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3"><label class="form-label">Invitation Title*</label><input type="text" name="title" class="form-control" placeholder="e.g., Parent-Teacher Meeting" required></div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Venue*</label><input type="text" name="venue" class="form-control" placeholder="e.g., School Auditorium" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Event Date*</label><input type="date" name="event_date" class="form-control" required></div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Event Time*</label><input type="time" name="event_time" class="form-control" required></div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Invitation Message*</label>
                    <textarea name="message" id="message_textarea" class="form-control" rows="5" placeholder="Select a template or write a custom message..." required></textarea>
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

                <h5 class="text-primary mt-4">2. Select Recipients</h5>
                <div class="mb-3">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="recipient_type" id="recipient_students" value="students" checked>
                        <label class="form-check-label" for="recipient_students">Parents of Students</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="recipient_type" id="recipient_others" value="others">
                        <label class="form-check-label" for="recipient_others">Other Guests</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="recipient_type" id="recipient_blank" value="blank">
                        <label class="form-check-label" for="recipient_blank">Blank Invitations</label>
                    </div>
                </div>

                <div id="student_filters">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Class*</label>
                            <select name="class_id" id="class_id" class="form-select">
                                <option value="">-- Select Class --</option>
                                <?php foreach ($classes as $class) echo "<option value='{$class['id']}'>" . htmlspecialchars($class['name']) . "</option>"; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Section*</label>
                            <select name="section_id" id="section_id" class="form-select">
                                <option value="">-- Select Class First --</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="other_guests_input" style="display: none;">
                    <div class="mb-3">
                        <label for="guest_names" class="form-label">Guest Names*</label>
                        <textarea name="guest_names" id="guest_names" class="form-control" rows="5" placeholder="Enter one guest name per line."></textarea>
                    </div>
                </div>

                <div id="blank_cards_input" style="display: none;">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="blank_card_count" class="form-label">Number of Cards*</label>
                            <input type="number" name="blank_card_count" id="blank_card_count" class="form-control" value="10" min="1" max="200">
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="generate_invitations" class="btn btn-primary"><i class="fas fa-cogs me-1"></i> Generate Invitations</button>
            </form>
        </div>
    </div>

    <?php if ($invitation_details && (!empty($students) || !empty($other_guests) || $blank_cards_count > 0)): ?>
    <div class="card">
        <div class="card-body bg-light" id="print-area">
            <div class="print-container">
                <?php
                    // Combine recipients into a single list for looping
                    $recipients = [];
                    if (!empty($students)) {
                        foreach ($students as $student) {
                            $recipients[] = ['type' => 'student', 'data' => $student];
                        }
                    }
                    if (!empty($other_guests)) {
                        foreach ($other_guests as $guest_name) {
                            $recipients[] = ['type' => 'guest', 'data' => ['name' => $guest_name]];
                        }
                    }
                    if ($blank_cards_count > 0) {
                        for ($i = 0; $i < $blank_cards_count; $i++) {
                            $recipients[] = ['type' => 'blank', 'data' => []];
                        }
                    }
                    $base_placeholders = [
                        '[event_date]' => date('D, F j, Y', strtotime($invitation_details['event_date'])),
                        '[event_time]' => date('g:i A', strtotime($invitation_details['event_time'])),
                        '[venue]' => htmlspecialchars($invitation_details['venue'])
                    ];
                ?>
        <div class="card-header d-flex justify-content-between align-items-center no-print">
            <span><i class="fas fa-print me-1"></i> Generated Invitations (<?php echo count($recipients); ?>)</span>
            <button class="btn btn-success" onclick="window.print()"><i class="fas fa-print me-1"></i> Print All</button>
        </div>
                <?php foreach ($recipients as $recipient): ?>
                <?php
                    $is_urdu = preg_match('/[\x{0600}-\x{06FF}]/u', $invitation_details['message']);
                    $message = $invitation_details['message'];
                    
                    if ($recipient['type'] === 'student') {
                        $placeholders = array_merge($base_placeholders, [
                            '[parent_name]' => htmlspecialchars($recipient['data']['father_name'] ?? 'Parent'),
                            '[student_name]' => htmlspecialchars($recipient['data']['student_name']),
                            '[class_name]' => htmlspecialchars($recipient['data']['class_name']),
                            '[section_name]' => htmlspecialchars($recipient['data']['section_name'])
                        ]);
                    } else { // Guest
                        $guest_name_placeholder = ($recipient['type'] === 'guest') 
                            ? htmlspecialchars($recipient['data']['name']) 
                            : '_________________________';
                        $placeholders = array_merge($base_placeholders, [
                            '[parent_name]' => $guest_name_placeholder,
                            '[student_name]' => '__________' // Fallback for blank cards
                        ]);
                    }
                    $final_message = str_replace(array_keys($placeholders), array_values($placeholders), $message);
                ?>
                <div class="invitation-card <?php echo $is_urdu ? 'rtl' : ''; ?>">
                    <div class="card-header-inv">
                        <h5><?php echo htmlspecialchars($invitation_details['title']); ?></h5>
                        <p><?php echo htmlspecialchars(SITE_NAME . ' - ' . ($_SESSION['branch_name'] ?? 'Your Branch')); ?></p>
                    </div>
                    <div class="invitation-body">
                        <p><?php echo nl2br($final_message); ?></p>
                        <?php if (!$is_urdu): // Show details separately for English for clarity ?>
                        <div class="event-details">
                            <strong>Date:</strong> <?php echo date('D, F j, Y', strtotime($invitation_details['event_date'])); ?><br>
                            <strong>Time:</strong> <?php echo date('g:i A', strtotime($invitation_details['event_time'])); ?><br>
                            <strong>Venue:</strong> <?php echo htmlspecialchars($invitation_details['venue']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Template Logic ---
    const templates = <?php echo json_encode($templates_js); ?>;
    const templateSelect = document.getElementById('template_select');
    const messageTextarea = document.getElementById('message_textarea');    

    templateSelect.addEventListener('change', function() {
        const selectedId = this.value;
        messageTextarea.value = templates[selectedId] ? templates[selectedId].content : '';
        messageTextarea.dir = templates[selectedId] && templates[selectedId].language === 'ur' ? 'rtl' : 'ltr';
    });

    // --- Recipient Type Toggle Logic ---
    const studentFilters = document.getElementById('student_filters');
    const otherGuestsInput = document.getElementById('other_guests_input');
    const blankCardsInput = document.getElementById('blank_cards_input');
    const studentRadio = document.getElementById('recipient_students');
    const otherRadio = document.getElementById('recipient_others');
    const classSelectInput = document.getElementById('class_id');
    const sectionSelectInput = document.getElementById('section_id');
    const guestNamesTextarea = document.getElementById('guest_names');
    const blankCardCountInput = document.getElementById('blank_card_count');

    document.querySelectorAll('input[name="recipient_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'students') {
                studentFilters.style.display = 'block';
                otherGuestsInput.style.display = 'none';
                guestNamesTextarea.required = false;
                blankCardsInput.style.display = 'none';
                blankCardCountInput.required = false;
            } else if (this.value === 'others') {
                studentFilters.style.display = 'none';
                otherGuestsInput.style.display = 'block';
                guestNamesTextarea.required = true;
                blankCardsInput.style.display = 'none';
                blankCardCountInput.required = false;
            } else { // blank
                studentFilters.style.display = 'none';
                otherGuestsInput.style.display = 'none';
                guestNamesTextarea.required = false;
                blankCardsInput.style.display = 'block';
                blankCardCountInput.required = true;
            }
        });
    });

    // --- Placeholder Click Logic ---
    document.querySelectorAll('.placeholder-badge').forEach(badge => {
        badge.style.cursor = 'pointer';
        badge.addEventListener('click', function() {
            const placeholder = this.getAttribute('data-placeholder');
            const cursorPos = messageTextarea.selectionStart;
            const textBefore = messageTextarea.value.substring(0, cursorPos);
            const textAfter = messageTextarea.value.substring(cursorPos);
            messageTextarea.value = textBefore + placeholder + textAfter;
        });
    });
    // --- Class/Section Logic ---
    const classSelect = document.getElementById('class_id');
    const sectionSelect = document.getElementById('section_id');
    const selectedClassId = '<?php echo $class_id; ?>';
    const selectedSectionId = '<?php echo $section_id; ?>';

    function fetchSections(classId, targetSelect, selectedId) {
        if (!classId) {
            targetSelect.innerHTML = '<option value="">-- Select Class First --</option>';
            return;
        }
        targetSelect.innerHTML = '<option value="">Loading...</option>';
        fetch(`<?php echo BASE_URL; ?>/api/get_sections.php?class_id=${classId}`)
            .then(response => response.json())
            .then(data => {
                targetSelect.innerHTML = '<option value="">-- Select Section --</option>';
                data.forEach(section => {
                    const selected = section.id == selectedId ? 'selected' : '';
                    targetSelect.innerHTML += `<option value="${section.id}" ${selected}>${section.name}</option>`;
                });
            });
    }

    classSelect.addEventListener('change', () => fetchSections(classSelect.value, sectionSelect, null));

    // On page load, if a class was previously selected (form submission), populate sections
    if (selectedClassId) {
        fetchSections(selectedClassId, sectionSelect, selectedSectionId);
    }
});
</script>

<?php require_once '../../footer.php'; ?>
