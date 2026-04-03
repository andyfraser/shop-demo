<?php // templates/footer.php ?>
</main>

<footer class="site-footer">
  <p>&copy; <?= date('Y') ?> <?= SITE_NAME_PLAIN ?>. A demo e-commerce application.</p>
</footer>

<div id="toast-container" aria-live="polite" aria-atomic="false"
     style="position:fixed;top:80px;right:1.5rem;z-index:500;display:flex;flex-direction:column;gap:.5rem;width:300px;pointer-events:none;"></div>

<?php if (file_exists(__DIR__ . '/../public/js/shop.js')): ?>
<script src="<?= BASE_URL ?>/public/js/shop.js?v=<?= filemtime(__DIR__ . '/../public/js/shop.js') ?>"></script>
<?php endif; ?>

</body>
</html>
