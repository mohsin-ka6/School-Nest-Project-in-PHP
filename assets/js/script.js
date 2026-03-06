// Wait for the DOM to be fully loaded before running the script
document.addEventListener("DOMContentLoaded", function() {

    // Sidebar toggle functionality
    const menuToggle = document.getElementById("menu-toggle");
    if (menuToggle) {
        menuToggle.addEventListener("click", function(e) {
            e.preventDefault();
            document.getElementById("wrapper").classList.toggle("toggled");
        });
    }
});