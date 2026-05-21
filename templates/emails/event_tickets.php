<h1>Your Event Tickets</h1>
<p>Hello <?= htmlspecialchars($name) ?>,</p>
<p>Thank you for booking! Here are your ticket / booking references:</p>

<table class="table" style="width: 100%; border-collapse: collapse; margin: 20px 0;">
    <thead>
        <tr style="background: #f8f9fa;">
            <th style="padding: 10px; border: 1px solid #dee2e6; text-align: left;">Product</th>
            <th style="padding: 10px; border: 1px solid #dee2e6; text-align: left;">Ticket Code / Reference</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($tickets as $t): ?>
            <tr>
                <td style="padding: 10px; border: 1px solid #dee2e6;">
                    <?= htmlspecialchars($t['name']) ?>
                </td>
                <td style="padding: 10px; border: 1px solid #dee2e6; font-family: monospace; font-size: 16px; font-weight: bold; background: #e8f4fd; color: #0056b3;">
                    <?= htmlspecialchars($t['code']) ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<p>Please present this email or your customer dashboard references at the venue. Enjoy the event!</p>
<p>Best regards,<br>The <?= htmlspecialchars($cleanSiteName) ?> Team</p>
