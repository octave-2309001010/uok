</main>
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container text-center">
            <span class="text-muted">&copy; <?php echo date('Y'); ?> Personal Budget Tracker. All rights reserved.</span>
            <div class="mt-2">
                <a href="about.php" class="text-muted me-3">About</a>
                <a href="privacy.php" class="text-muted me-3">Privacy Policy</a>
                <a href="terms.php" class="text-muted me-3">Terms of Service</a>
                <a href="contact.php" class="text-muted">Contact</a>
            </div>
        </div>
    </footer>

    <!-- JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
  
    
    <?php if (isset($page_specific_js)): ?>
        <script src="<?php echo $page_specific_js; ?>"></script>
    <?php endif; ?>
</body>
</html>