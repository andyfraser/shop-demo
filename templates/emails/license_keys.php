<h1>Your Software License Keys</h1>
<p>Hello <?= htmlspecialchars($name) ?>,</p>
<p>Thank you for your purchase! Here are your software license keys:</p>

<table class="table" style="width: 100%; border-collapse: collapse; margin: 20px 0;">
    <thead>
        <tr style="background: #f8f9fa;">
            <th style="padding: 10px; border: 1px solid #dee2e6; text-align: left;">Product</th>
            <th style="padding: 10px; border: 1px solid #dee2e6; text-align: left;">License Key</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($licenses as $l): ?>
            <tr>
                <td style="padding: 10px; border: 1px solid #dee2e6;">
                    <?= htmlspecialchars($l['name']) ?>
                </td>
                <td style="padding: 10px; border: 1px solid #dee2e6; font-family: monospace; font-size: 16px; font-weight: bold; background: #f1f3f5;">
                    <?= htmlspecialchars($l['key']) ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<p>You can also access all your keys anytime by logging into your customer dashboard.</p>
<p>Best regards,<br>The <?= htmlspecialchars($cleanSiteName) ?> Team</p>
