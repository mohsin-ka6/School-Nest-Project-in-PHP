<?php
$page_title = "Super Admin Dashboard";

// Include core configuration and function files
require_once '../config.php';
require_once '../functions.php';

// Security check: Ensure user is logged in and is a superadmin.
// We do this manually here to avoid the redirect loop from check_role().
if (!is_logged_in() || $_SESSION['role'] !== 'superadmin') redirect(BASE_URL . '/auth/login.php');

// Fetch stats for dashboard widgets
$stmt_branches = $db->query("SELECT COUNT(*) as total FROM branches");
$total_branches = $stmt_branches->fetchColumn();

$stmt_admins = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'branchadmin'");
$total_admins = $stmt_admins->fetchColumn();

// Fetch total students and teachers across all branches
$stmt_students = $db->query("SELECT COUNT(*) FROM users WHERE role = 'student'");
$total_students = $stmt_students->fetchColumn();

$stmt_teachers = $db->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'");
$total_teachers = $stmt_teachers->fetchColumn();

// Include the header
require_once '../header.php';
?>

<?php
// Include the superadmin-specific sidebar
require_once '../sidebar_superadmin.php';
?>

<?php
// Include the main navigation bar
require_once '../navbar.php';
?>

<div class="container-fluid px-4">

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-white-75 small">Total Branches</div>
                        <div class="fs-2 fw-bold"><?php echo $total_branches; ?></div>
                    </div>
                    <i class="fas fa-code-branch fa-3x text-white-50"></i>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="<?php echo BASE_URL; ?>/superadmin/branches/manage_branches.php">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-white-75 small">Branch Admins</div>
                        <div class="fs-2 fw-bold"><?php echo $total_admins; ?></div>
                    </div>
                    <i class="fas fa-user-shield fa-3x text-white-50"></i>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="<?php echo BASE_URL; ?>/superadmin/admins/view_admins.php">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <!-- Placeholder cards for future modules -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-white-75 small">Total Students</div>
                        <div class="fs-2 fw-bold"><?php echo $total_students; ?></div>
                    </div>
                    <i class="fas fa-user-graduate fa-3x text-white-50"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-white-75 small">Total Teachers</div>
                        <div class="fs-2 fw-bold"><?php echo $total_teachers; ?></div>
                    </div>
                    <i class="fas fa-chalkboard-teacher fa-3x text-white-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-chart-line me-1"></i> Login Activity (Last 30 Days)</div>
                <div class="card-body"><canvas id="loginActivityChart" width="100%" height="40"></canvas></div>
            </div>
        </div>
    </div>
</div>

<?php
// Include the footer
require_once '../footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    fetch('<?php echo BASE_URL; ?>/api/get_login_activity.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error fetching login activity:', data.error);
                return;
            }

            const ctx = document.getElementById('loginActivityChart').getContext('2d');
            const loginActivityChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Successful Logins',
                        data: data.success_data,
                        borderColor: 'rgba(40, 167, 69, 1)',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        fill: true,
                        tension: 0.3
                    }, {
                        label: 'Failed Logins',
                        data: data.fail_data,
                        borderColor: 'rgba(220, 53, 69, 1)',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        })
        .catch(error => console.error('Failed to load login activity data:', error));
});
</script>