<?php
$page_title = "News & Events";
require_once 'config.php';
require_once 'functions.php';

// Fetch all published news/events, ordered by most recent
$stmt_news = $db->query("
    SELECT n.*, b.name as branch_name 
    FROM news_and_events n
    JOIN branches b ON n.branch_id = b.id
    WHERE n.status = 'published' 
    ORDER BY n.created_at DESC
");
$news_items = $stmt_news->fetchAll();

require_once 'public_header.php';
?>

<style>
    .section { padding: 60px 0; }
    .section-title { text-align: center; margin-bottom: 50px; font-size: 2.5rem; font-weight: 600; color: #1d3557; }
</style>

<section id="news" class="section bg-light">
    <div class="container">
        <h2 class="section-title">News & Events</h2>
        <div class="row">
            <?php if (empty($news_items)): ?>
                <div class="col-12 text-center">
                    <p class="lead">No news or events to display at the moment. Please check back later.</p>
                </div>
            <?php else: foreach ($news_items as $item): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <?php if ($item['image_path']): ?>
                            <img src="<?php echo BASE_URL . '/' . htmlspecialchars($item['image_path']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($item['title']); ?>" style="height: 200px; object-fit: cover;">
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($item['title']); ?></h5>
                            <p class="card-text text-muted small">
                                <i class="fas fa-sitemap me-1"></i> <?php echo htmlspecialchars($item['branch_name']); ?>
                                <span class="mx-2">|</span>
                                <i class="fas fa-calendar-alt me-1"></i> <?php echo date('d M, Y', strtotime($item['created_at'])); ?>
                            </p>
                            <div class="card-text mb-4">
                                <?php $excerpt = strip_tags($item['content']); echo htmlspecialchars(substr($excerpt, 0, 100)) . (strlen($excerpt) > 100 ? '...' : ''); ?>
                            </div>
                            <a href="news_item.php?id=<?php echo $item['id']; ?>" class="btn btn-outline-primary mt-auto">Read More</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</section>

<?php require_once 'public_footer.php'; ?>
