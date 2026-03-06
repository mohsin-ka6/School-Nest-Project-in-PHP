<?php
require_once 'config.php';
require_once 'functions.php';

$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$item_id) {
    redirect('index.php');
}

// Fetch the specific news/event item
// Only fetch if it's published to prevent direct access to draft items
$stmt = $db->prepare("SELECT * FROM news_and_events WHERE id = ? AND status = 'published'");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    // If no item found, redirect to the homepage or show a 404
    $_SESSION['error_message'] = "The requested news or event item was not found.";
    redirect('index.php');
}

$page_title = $item['title'];
require_once 'public_header.php';
?>

<style>
    .news-header {
        padding: 40px 0;
        background-color: #f8f9fa;
    }
    .news-content {
        padding: 60px 0;
    }
    .news-image {
        width: 100%;
        max-height: 450px;
        object-fit: cover;
        border-radius: .375rem;
        margin-bottom: 30px;
    }
    .news-meta {
        color: #6c757d;
        margin-bottom: 20px;
    }
</style>

<div class="news-header">
    <div class="container">
        <h1><?php echo htmlspecialchars($item['title']); ?></h1>
        <div class="news-meta">
            <span><i class="fas fa-calendar-alt me-2"></i>Posted on <?php echo date('F j, Y', strtotime($item['created_at'])); ?></span>
            <?php if ($item['type'] == 'event' && $item['event_date']): ?>
                <span class="ms-3 badge bg-success"><i class="fas fa-star me-2"></i>Event Date: <?php echo date('F j, Y', strtotime($item['event_date'])); ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="news-content">
    <div class="container">
        <?php if ($item['image_path']): ?>
            <img src="<?php echo BASE_URL . '/' . htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="news-image shadow-sm">
        <?php endif; ?>
        <div class="lead"><?php echo $item['content']; // Content is from TinyMCE, so no escaping ?></div>
    </div>
</div>

<?php require_once 'public_footer.php'; ?>
