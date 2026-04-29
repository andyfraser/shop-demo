<?php // templates/emails/abandoned_cart.php ?>
<h1>Hi <?= h($name) ?>,</h1>
<p>We noticed you left some items in your shopping cart. We've saved them for you!</p>
<p>Don't miss out on your favorite items. Click the button below to return to your cart and complete your purchase.</p>
<div style="text-align: center; margin: 30px 0;">
    <a href="<?= h($cartUrl) ?>" style="background-color: #222; color: #fff; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold;">Return to My Cart</a>
</div>
<p>If you have any questions, feel free to reply to this email. We're here to help!</p>
<p>Best regards,<br>The <?= h($cleanSiteName) ?> Team</p>
