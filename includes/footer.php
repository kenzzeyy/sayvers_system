    </main>
    
    <!-- Footer -->
    <footer class="footer">
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 1rem;">
            <p>&copy; <?php echo date('Y'); ?> <?php echo ORGANIZATION_NAME; ?>. All rights reserved.</p>
            <p>Developed by John Kenrick O. Alviento</p>
            <p style="font-size: 0.8rem; margin-top: 0.5rem;">
                System Version <?php echo SYSTEM_VERSION; ?>
        
            </p>
        </div>
    </footer>
    
    <!-- JavaScript Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    
    <!-- Additional page-specific scripts -->
    <?php if (isset($additional_scripts)): ?>
        <?php foreach ($additional_scripts as $script): ?>
            <script src="<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Page-specific inline scripts -->
    <?php if (isset($inline_scripts)): ?>
        <script>
            <?php echo $inline_scripts; ?>
        </script>
    <?php endif; ?>
</body>
</html>