<?php
/**
 * 500 Internal Server Error Template
 */
$page_title = 'Something Went Wrong';
?>

<div class="container" style="text-align: center; padding: 100px 20px;">
    <h1 style="font-size: 6rem; margin-bottom: 0; color: var(--border);">500</h1>
    <h2 style="margin-top: 0; font-family: 'Playfair Display', serif;">Something Went Wrong</h2>
    <p style="color: var(--text-muted); max-width: 500px; margin: 2rem auto;">
        We're sorry, but something went wrong on our end. We've logged the error and will look into it.
    </p>
    <div style="margin-top: 3rem;">
        <a href="<?= BASE_URL ?>/" class="btn btn-primary">Return to Home</a>
    </div>
</div>
