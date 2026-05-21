<h1>Your Digital Downloads</h1>
<p>Hello <?= htmlspecialchars($name) ?>,</p>
<p>Thank you for your purchase! Your digital products are ready for download below:</p>

<table class="table" style="width: 100%; border-collapse: collapse; margin: 20px 0;">
    <thead>
        <tr style="background: #f8f9fa;">
            <th style="padding: 10px; border: 1px solid #dee2e6; text-align: left;">Product</th>
            <th style="padding: 10px; border: 1px solid #dee2e6; text-align: center;">Download Link</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($downloads as $d): ?>
            <tr>
                <td style="padding: 10px; border: 1px solid #dee2e6;">
                    <?= htmlspecialchars($d['name']) ?>
                </td>
                <td style="padding: 10px; border: 1px solid #dee2e6; text-align: center;">
                    <a href="<?= $baseUrl ?>/download/<?= urlencode($d['token']) ?>" class="btn" style="padding: 5px 15px;">Download File</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<p>You can also access all your purchases and download links anytime through your customer dashboard.</p>
<p style="text-align: center; margin: 30px 0;">
    <a href="<?= $baseUrl ?>/account/downloads" class="btn">Go to Downloads</a>
</p>
<p>Best regards,<br>The <?= htmlspecialchars($cleanSiteName) ?> Team</p>
