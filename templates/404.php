<?php
/**
 * 404 Not Found Template
 */
$page_title = 'Page Not Found';
?>

<div class="container" style="text-align: center; padding: 100px 20px;">
    <h1 style="font-size: 6rem; margin-bottom: 0; color: var(--border);">404</h1>
    <h2 style="margin-top: 0; font-family: 'Playfair Display', serif;">Page Not Found</h2>
    <p style="color: var(--text-muted); max-width: 500px; margin: 2rem auto;">
        Sorry, the page you are looking for doesn't exist or has been moved.
    </p>
    <div style="margin-top: 3rem;">
        <a href="<?= BASE_URL ?>/" class="btn btn-primary">Return to Home</a>
    </div>
</div>
