        </div> <!-- /.page-content-inner -->
    </div> <!-- /.main-content -->

    
    </div>
    <!-- /#wrapper -->

    <footer class="py-2 my-3 border-top">
        <p class="text-center text-muted small mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All Rights Reserved.</p>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const fullscreenBtn = document.getElementById('fullscreen-btn');
        const fullscreenIcon = fullscreenBtn.querySelector('i');

        function toggleFullScreen() {
            if (!document.fullscreenElement && !document.mozFullScreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement) {
                // Enter fullscreen
                if (document.documentElement.requestFullscreen) {
                    document.documentElement.requestFullscreen();
                } else if (document.documentElement.msRequestFullscreen) {
                    document.documentElement.msRequestFullscreen();
                } else if (document.documentElement.mozRequestFullScreen) {
                    document.documentElement.mozRequestFullScreen();
                } else if (document.documentElement.webkitRequestFullscreen) {
                    document.documentElement.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
                }
            } else {
                // Exit fullscreen
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                } else if (document.mozCancelFullScreen) {
                    document.mozCancelFullScreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                }
            }
        }

        fullscreenBtn.addEventListener('click', function(e) {
            e.preventDefault();
            toggleFullScreen();
        });

        // Change icon on fullscreen change
        document.addEventListener('fullscreenchange', () => {
            if (document.fullscreenElement) {
                fullscreenIcon.classList.remove('fa-expand');
                fullscreenIcon.classList.add('fa-compress');
            } else {
                fullscreenIcon.classList.remove('fa-compress');
                fullscreenIcon.classList.add('fa-expand');
            }
        });
    });

    // New Sidebar Toggle Logic
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const sidebarToggle = document.getElementById('sidebarToggle');
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
                
                // Change icon based on state
                const icon = this.querySelector('i');
                if (mainContent.classList.contains('expanded')) {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-align-left');
                } else {
                    icon.classList.remove('fa-align-left');
                    icon.classList.add('fa-bars');
                }
            });
        }
        
        // Auto-collapse on mobile
        function handleResize() {
            if (window.innerWidth <= 768) {
                if (!sidebar.classList.contains('collapsed')) {
                    sidebar.classList.add('collapsed');
                    mainContent.classList.add('expanded');
                    if (sidebarToggle) {
                        const icon = sidebarToggle.querySelector('i');
                        if (icon) { icon.classList.remove('fa-bars'); icon.classList.add('fa-align-left'); }
                    }
                }
            }
        }
        
        // Initial check and listen for resize events
        handleResize();
        window.addEventListener('resize', handleResize);
    });
    </script>
</body>
</html>