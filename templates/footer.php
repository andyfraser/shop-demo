<?php // templates/footer.php ?>
</main>

<footer class="site-footer">
  <p>&copy; <?= date('Y') ?> <?= SITE_NAME_PLAIN ?>. A demo e-commerce application.</p>
</footer>

<div id="toast-container" aria-live="polite" aria-atomic="false"
     style="position:fixed;top:80px;right:1.5rem;z-index:500;display:flex;flex-direction:column;gap:.5rem;width:300px;pointer-events:none;"></div>

<?php if (empty($_COOKIE['cookie_consent'])): ?>
<div id="cookie-banner" class="cookie-banner" role="dialog" aria-label="Cookie consent">
  <div class="cookie-banner-inner">
    <p class="cookie-banner-text">
      We use cookies to keep your shopping cart and session active. By continuing to use this site, you accept our use of cookies.
    </p>
    <div class="cookie-banner-actions">
      <button id="cookie-accept" class="btn btn-primary btn-sm">Accept</button>
      <button id="cookie-decline" class="btn btn-outline btn-sm">Decline</button>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if (file_exists(__DIR__ . '/../public/js/shop.js')): ?>
<script src="<?= BASE_URL ?>/js/shop.js?v=<?= filemtime(__DIR__ . '/../public/js/shop.js') ?>"></script>
<?php endif; ?>

</body>
</html>
