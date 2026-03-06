<?php
$page_title = "About Us";
require_once 'config.php';
require_once 'functions.php';

// Fetch dynamic content from the database
$stmt_content = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'public_page_%'");
$public_content = $stmt_content->fetchAll(PDO::FETCH_KEY_PAIR);

function get_content($key, $default) {
    global $public_content;
    // For the 'about_us' content, we want to render HTML, so we don't escape it.
    return $public_content[$key] ?? $default;
}

require_once 'public_header.php';
?>

<style>
    .section { padding: 60px 0; }
    .section-title { text-align: center; margin-bottom: 50px; font-size: 2.5rem; font-weight: 600; color: #1d3557; }
</style>

<!-- About Us Section -->
<section id="about" class="section bg-light">
    <div class="container">
        <h2 class="section-title">About Us</h2>
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="lead">
                    <?php echo get_content('public_page_about_us', '<p>Founded with a vision to provide holistic education, our institution stands as a beacon of learning, innovation, and character development. We are committed to nurturing young minds to become future leaders and responsible citizens.</p>'); ?>
                </div>
            </div>
            
        </div>
    </div>
</section>

<?php require_once 'public_footer.php'; ?>