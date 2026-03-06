<?php
$page_title = "Welcome";
require_once 'config.php';
require_once 'functions.php';

// Fetch dynamic content from the database
$stmt_content = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'public_page_%'");
$public_content = $stmt_content->fetchAll(PDO::FETCH_KEY_PAIR);

// Fetch stats for the new section
$total_students = $db->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$total_teachers = $db->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'")->fetchColumn();
$total_branches = $db->query("SELECT COUNT(*) FROM branches")->fetchColumn();

// Fetch latest 3 news/events
$stmt_news = $db->query("SELECT * FROM news_and_events WHERE status = 'published' ORDER BY created_at DESC LIMIT 3");
$news_items = $stmt_news->fetchAll();

// Fetch latest 8 gallery images
$stmt_gallery = $db->query("SELECT * FROM gallery WHERE is_visible = 1 ORDER BY uploaded_at DESC LIMIT 8");
$gallery_images = $stmt_gallery->fetchAll();

function get_content($key, $default) {
    global $public_content;
    return htmlspecialchars($public_content[$key] ?? $default);
}

require_once 'public_header.php';
?>

<style>
    .hero-section {
        background: linear-gradient(rgba(29, 53, 87, 0.7), rgba(29, 53, 87, 0.7)), url('https://images.unsplash.com/photo-1580582932707-520aed937b7b?auto=format&fit=crop&w=1932');
        background-size: cover;
        background-position: center;
        color: white;
        padding: 100px 0;
        text-align: center;
    }
    .hero-section h1 {
        font-size: 3.5rem;
        font-weight: 700;
    }
    .hero-section p {
        font-size: 1.25rem;
        max-width: 600px;
        margin: 1rem auto;
    }
    .section {
        padding: 60px 0;
    }
    .section-title {
        text-align: center;
        margin-bottom: 50px;
        font-size: 2.5rem;
        font-weight: 600;
        color: #1d3557;
    }
    .feature-icon {
        font-size: 3rem;
        color: #1d3557;
    }
    .gallery-img {
        height: 250px;
        object-fit: cover;
        cursor: pointer;
        transition: transform 0.2s ease-in-out;
    }
    .gallery-img:hover {
        transform: scale(1.05);
    }
</style>

<!-- Hero Section -->
<div class="hero-section">
    <div class="container">
        <h1><?php echo get_content('public_page_hero_title', 'Welcome to ' . SITE_NAME); ?></h1>
        <p><?php echo get_content('public_page_hero_subtitle', 'Excellence in Education, Foundation for the Future.'); ?></p>
        <a href="<?php echo BASE_URL; ?>/auth/login.php" class="btn btn-primary btn-lg mt-3">Portal Login</a>
    </div>
</div>

<!-- Stats Section -->
<section id="stats" class="section bg-light">
    <div class="container">
        <h2 class="section-title">At a Glance</h2>
        <div class="row text-center">
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm border-0"><div class="card-body"><i class="fas fa-user-graduate fa-3x text-primary mb-3"></i><h3 class="card-title"><?php echo $total_students; ?>+</h3><p class="card-text">Students Enrolled</p></div></div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm border-0"><div class="card-body"><i class="fas fa-chalkboard-teacher fa-3x text-success mb-3"></i><h3 class="card-title"><?php echo $total_teachers; ?>+</h3><p class="card-text">Qualified Teachers</p></div></div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm border-0"><div class="card-body"><i class="fas fa-sitemap fa-3x text-info mb-3"></i><h3 class="card-title"><?php echo $total_branches; ?>+</h3><p class="card-text">Branches Nationwide</p></div></div>
            </div>
        </div>
    </div>
</section>
<!-- Features Section -->
<section id="features" class="section">
    <div class="container">
        <h2 class="section-title">Why Choose Us?</h2>
        <div class="row text-center">
            <div class="col-md-4 mb-4">
                <i class="fas fa-chalkboard-teacher feature-icon mb-3"></i>
                <h4>Expert Faculty</h4>
                <p>Our experienced and dedicated teachers are the backbone of our institution.</p>
            </div>
            <div class="col-md-4 mb-4">
                <i class="fas fa-flask feature-icon mb-3"></i>
                <h4>Modern Labs</h4>
                <p>State-of-the-art facilities to foster practical learning and innovation.</p>
            </div>
            <div class="col-md-4 mb-4">
                <i class="fas fa-volleyball-ball feature-icon mb-3"></i>
                <h4>Holistic Development</h4>
                <p>We focus on sports, arts, and character building alongside academics.</p>
            </div>
        </div>
    </div>
</section>

<!-- News & Events Section -->
<section id="news" class="section bg-light">
    <div class="container">
        <h2 class="section-title">Latest News & Events</h2>
        <div class="row">
            <?php if (empty($news_items)): ?>
                <div class="col-12 text-center"><p class="lead">No news or events to display at the moment. Please check back later.</p></div>
            <?php else: foreach ($news_items as $item): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <?php if ($item['image_path']): ?>
                            <img src="<?php echo BASE_URL . '/' . htmlspecialchars($item['image_path']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($item['title']); ?>" style="height: 200px; object-fit: cover;">
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($item['title']); ?></h5>
                            <div class="card-text mb-4">
                                <?php
                                // Display a short excerpt of the content
                                $excerpt = strip_tags($item['content']);
                                echo htmlspecialchars(substr($excerpt, 0, 100)) . (strlen($excerpt) > 100 ? '...' : '');
                                ?>
                            </div>
                            <a href="news_item.php?id=<?php echo $item['id']; ?>" class="btn btn-outline-primary mt-auto">Read More</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</section>

<!-- Gallery Section -->
<section id="gallery" class="section">
    <div class="container">
        <h2 class="section-title">Photo Gallery</h2>
        <?php if (empty($gallery_images)): ?>
            <div class="col-12 text-center"><p class="lead">No gallery images to display at the moment.</p></div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($gallery_images as $image): ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <img src="<?php echo BASE_URL . '/' . htmlspecialchars($image['image_path']); ?>" 
                         class="img-fluid rounded shadow-sm gallery-img" 
                         alt="<?php echo htmlspecialchars($image['title']); ?>"
                         data-bs-toggle="modal" 
                         data-bs-target="#galleryModal"
                         data-bs-img-src="<?php echo BASE_URL . '/' . htmlspecialchars($image['image_path']); ?>"
                         data-bs-img-title="<?php echo htmlspecialchars($image['title']); ?>">
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Gallery Modal (Lightbox) -->
<div class="modal fade" id="galleryModal" tabindex="-1" aria-labelledby="galleryModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="galleryModalLabel">Image Preview</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img src="" class="img-fluid" id="modalImage" alt="Gallery Image">
      </div>
    </div>
  </div>
</div>

<?php require_once 'public_footer.php'; ?>
