</main>

<footer class="bg-dark text-white text-center py-4 mt-auto">
    <div class="container">
        <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All Rights Reserved.</p>
        <p class="mb-0 small">Powered by SchoolNest</p>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var galleryModal = document.getElementById('galleryModal');
    if (galleryModal) {
        galleryModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var imgSrc = button.getAttribute('data-bs-img-src');
            var imgTitle = button.getAttribute('data-bs-img-title');
            var modalImage = galleryModal.querySelector('#modalImage');
            var modalTitle = galleryModal.querySelector('#galleryModalLabel');
            modalImage.src = imgSrc;
            modalTitle.textContent = imgTitle || 'Image Preview';
        });
    }
});
</script>

</body>
</html>