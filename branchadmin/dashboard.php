<?php
$page_title = "Branch Admin Dashboard";
require_once '../config.php';
require_once '../functions.php';

// Security check and role handling
if (isset($_SESSION['view_as_branch_id']) && $_SESSION['role'] === 'superadmin') {
    // Allow superadmin to view this page if they are in "view as" mode
    $branch_id = $_SESSION['view_as_branch_id'];
} else {
    // Normal access for branch admins, check manually to avoid redirect loop.
    if (!is_logged_in() || $_SESSION['role'] !== 'branchadmin') redirect(BASE_URL . '/auth/login.php');
    $branch_id = $_SESSION['branch_id'];
}

if (!$branch_id) {
    die("Error: Branch information not found for this admin. Please contact support.");
}

// Fetch Branch Name
$stmt_branch = $db->prepare("SELECT name FROM branches WHERE id = ?");
$stmt_branch->execute([$branch_id]);
$branch_name = $stmt_branch->fetchColumn();

// Fetch stats for this branch
// Placeholders for now, will be replaced with real queries as modules are built
$stmt_students = $db->prepare("SELECT COUNT(*) FROM users WHERE role = 'student' AND branch_id = ?");
$stmt_students->execute([$branch_id]);
$total_students = $stmt_students->fetchColumn();

$stmt_teachers = $db->prepare("SELECT COUNT(*) FROM users WHERE role = 'teacher' AND branch_id = ?");
$stmt_teachers->execute([$branch_id]);
$total_teachers = $stmt_teachers->fetchColumn();

$stmt_queries = $db->prepare("SELECT COUNT(*) FROM admission_queries WHERE status = 'active' AND branch_id = ?");
$stmt_queries->execute([$branch_id]);
$active_queries = $stmt_queries->fetchColumn();

require_once '../header.php';
?>

<?php require_once ROOT_PATH . '/sidebar_branchadmin.php'; ?>
<?php require_once ROOT_PATH . '/navbar.php'; ?>

<div class="container-fluid px-4">

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div><div class="fs-2 fw-bold"><?php echo $total_students; ?></div><div class="text-white-75 small">Total Students</div></div>
                    <i class="fas fa-user-graduate fa-3x text-white-50"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div><div class="fs-2 fw-bold"><?php echo $total_teachers; ?></div><div class="text-white-75 small">Total Teachers</div></div>
                    <i class="fas fa-chalkboard-teacher fa-3x text-white-50"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div><div class="fs-2 fw-bold"><?php echo $active_queries; ?></div><div class="text-white-75 small">Active Admission Queries</div></div>
                    <i class="fas fa-question-circle fa-3x text-white-50"></i>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="<?php echo BASE_URL; ?>/branchadmin/frontoffice/admission_query.php">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header"><i class="fas fa-chart-bar me-1"></i> Monthly Fee Collection (Last 6 Months)</div>
                        <div class="card-body"><canvas id="feeCollectionChart" width="100%" height="40"></canvas></div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header"><i class="fas fa-chart-pie me-1"></i> Student Gender Distribution</div>
                        <div class="card-body"><canvas id="genderDistributionChart" width="100%" height="80"></canvas></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Fee Collection Chart ---
    fetch('<?php echo BASE_URL; ?>/api/get_monthly_fee_collections.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error fetching chart data:', data.error);
                return;
            }

            const ctx = document.getElementById('feeCollectionChart').getContext('2d');
            const feeCollectionChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Fees Collected (PKR)',
                        data: data.data,
                        backgroundColor: 'rgba(0, 123, 255, 0.5)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'PKR ' + value.toLocaleString();
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += 'PKR ' + context.parsed.y.toLocaleString();
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        })
        .catch(error => console.error('Failed to load chart data:', error));

    // --- Gender Distribution Chart ---
    fetch('<?php echo BASE_URL; ?>/api/get_gender_distribution.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error fetching gender data:', data.error);
                return;
            }

            const ctxGender = document.getElementById('genderDistributionChart').getContext('2d');
            const genderDistributionChart = new Chart(ctxGender, {
                type: 'pie',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Students',
                        data: data.data,
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.7)', // Blue for Male
                            'rgba(255, 99, 132, 0.7)', // Pink for Female
                            'rgba(255, 206, 86, 0.7)'  // Yellow for Other
                        ],
                        borderColor: '#fff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        })
        .catch(error => console.error('Failed to load gender chart data:', error));
});
</script>