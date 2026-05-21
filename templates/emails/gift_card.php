<h1>You've Received a Gift Card!</h1>
<p>Hello <?= htmlspecialchars($recipientName) ?>,</p>
<p>Great news! <strong><?= htmlspecialchars($senderName) ?></strong> has sent you a gift card for <strong><?= money($amount) ?></strong>!</p>

<?php if (!empty($messageContent)): ?>
    <blockquote style="background: #f8f9fa; border-left: 4px solid #007bff; padding: 15px; margin: 20px 0; font-style: italic;">
        "<?= htmlspecialchars($messageContent) ?>"
    </blockquote>
<?php endif; ?>

<div style="background: #e9ecef; border: 1px dashed #ced4da; padding: 20px; text-align: center; margin: 30px 0; border-radius: 5px;">
    <p style="margin: 0; font-size: 14px; color: #6c757d; text-transform: uppercase; letter-spacing: 1px;">Your Gift Card Code</p>
    <h2 style="margin: 10px 0; font-size: 28px; font-family: monospace; color: #212529; letter-spacing: 2px;"><?= htmlspecialchars($code) ?></h2>
    <p style="margin: 0; font-size: 16px; font-weight: bold; color: #28a745;">Value: <?= money($amount) ?></p>
</div>

<p>You can use this gift card code at checkout on any of our items. Any remaining balance will be saved for your future orders.</p>

<p style="text-align: center; margin: 30px 0;">
    <a href="<?= $baseUrl ?>" class="btn">Shop Now</a>
</p>

<p>Best regards,<br>The <?= htmlspecialchars($cleanSiteName) ?> Team</p>
