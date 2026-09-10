<?php
// includes/footer.php - Global Layout Footer
?>
    </main>

    <!-- Footer Copyright -->
    <footer style="padding: 16px 28px; background: white; border-top: 1px solid var(--border-color); text-align: center; font-size: 13px; color: var(--text-muted);">
        <p>&copy; <?php echo date('Y'); ?> <strong><?php echo $lang['app_name']; ?></strong> - <?php echo $lang['app_subtitle']; ?>. All rights reserved.</p>
    </footer>
</div>
</div>

<!-- Global Helpers JS -->
<script>
function confirmDelete(message) {
    var defaultMsg = '<?php echo addslashes($lang['confirm_delete']); ?>';
    return confirm(message || defaultMsg);
}

// Close sidebar on click outside on mobile
document.addEventListener('click', function(e) {
    var sidebar = document.getElementById('appSidebar');
    var btn = document.querySelector('.mobile-menu-btn');
    if (sidebar && btn && window.innerWidth <= 900) {
        if (!sidebar.contains(e.target) && !btn.contains(e.target) && sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
        }
    }
});
</script>
</body>
</html>
