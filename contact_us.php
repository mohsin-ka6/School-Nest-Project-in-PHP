<?php
$page_title = "Contact Us";
require_once 'config.php';
require_once 'functions.php';

// Fetch all public settings
$stmt_settings = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'public_page_%'");
$public_content = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);

// Fetch all branches
$stmt_branches = $db->query("SELECT id, name, address, phone, email FROM branches ORDER BY name ASC");
$all_branches = $stmt_branches->fetchAll();

$main_branch_id = $public_content['public_page_main_branch_id'] ?? null;
$main_branch = null;
$other_branches = [];

// Separate the main branch from the others
foreach ($all_branches as $branch) {
    if ($branch['id'] == $main_branch_id) {
        $main_branch = $branch;
    } else {
        $other_branches[] = $branch;
    }
}

require_once 'public_header.php';
?>

<style>
    .section { padding: 60px 0; }
    .section-title { text-align: center; margin-bottom: 40px; font-size: 2.5rem; font-weight: 600; color: #1d3557; }
    .branch-card {
        border: 1px solid #dee2e6;
        border-radius: .375rem;
        transition: all .3s ease-in-out;
    }
    .branch-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    }
    .branch-card .card-header {
        font-weight: bold;
    }
    .main-branch-card {
        border-color: #0d6efd;
        border-width: 2px;
    }
</style>

<!-- Contact Section -->
<section id="contact" class="section bg-light">
    <div class="container">
        <h2 class="section-title">Get in Touch</h2>
        <p class="lead text-center mb-5">We would love to hear from you. Find our branch details below.</p>

        <?php if ($main_branch): ?>
            <h4 class="mb-3 text-primary"><i class="fas fa-star me-2"></i>Main Branch</h4>
            <div class="card branch-card main-branch-card mb-5">
                <div class="card-header bg-primary text-white"><?php echo htmlspecialchars($main_branch['name']); ?></div>
                <div class="card-body">
                    <p><i class="fas fa-map-marker-alt me-2 text-muted"></i><strong>Address:</strong> <?php echo htmlspecialchars($main_branch['address']); ?></p>
                    <p><i class="fas fa-phone me-2 text-muted"></i><strong>Phone:</strong> <?php echo htmlspecialchars($main_branch['phone']); ?></p>
                    <p><i class="fas fa-envelope me-2 text-muted"></i><strong>Email:</strong> <?php echo htmlspecialchars($main_branch['email']); ?></p>
                </div>
            </div>
            <?php if (!empty($other_branches)): ?>
                <h4 class="mb-3">Other Branches</h4>
            <?php endif; ?>
        <?php endif; ?>

        <div class="row">
            <?php foreach ($other_branches as $branch): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card branch-card h-100">
                    <div class="card-header"><?php echo htmlspecialchars($branch['name']); ?></div>
                    <div class="card-body">
                        <p><i class="fas fa-map-marker-alt me-2 text-muted"></i><?php echo htmlspecialchars($branch['address']); ?></p>
                        <p><i class="fas fa-phone me-2 text-muted"></i><?php echo htmlspecialchars($branch['phone']); ?></p>
                        <p><i class="fas fa-envelope me-2 text-muted"></i><?php echo htmlspecialchars($branch['email']); ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once 'public_footer.php'; ?>