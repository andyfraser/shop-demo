<h1>Thank you for your order!</h1>
<p>Hello,</p>
<p>Your order <strong>#<?= $order['id'] ?></strong> has been received and is now being processed.</p>

<h3>Order Summary</h3>
<table class="table">
    <thead>
        <tr>
            <th>Item</th>
            <th style="text-align: center;">Qty</th>
            <th style="text-align: right;">Price</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
            <td><?= h($item['name']) ?></td>
            <td style="text-align: center;"><?= $item['quantity'] ?></td>
            <td style="text-align: right;"><?= money($item['unit_price'] * $item['quantity']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2" style="text-align: right; padding-top: 20px; color: #666;">Subtotal</td>
            <td style="text-align: right; padding-top: 20px;"><?= money($order['total'] - ($order['delivery_cost'] ?? 0)) ?></td>
        </tr>
        <?php if ($order['delivery_method']): ?>
        <tr>
            <td colspan="2" style="text-align: right; color: #666;">Delivery (<?= h($order['delivery_method']) ?>)</td>
            <td style="text-align: right;"><?= money($order['delivery_cost']) ?></td>
        </tr>
        <?php endif; ?>
        <tr class="total-row">
            <td colspan="2" style="text-align: right; padding-top: 10px; font-weight: bold; font-size: 1.2em;">Order Total</td>
            <td style="text-align: right; padding-top: 10px; font-weight: bold; font-size: 1.2em;"><?= money($order['total']) ?></td>
        </tr>
        <tr>
            <td colspan="3" style="text-align: right; font-size: 0.85em; color: #666; padding-top: 5px;">
                Includes <?= money($order['total_vat_amount'] ?? 0) ?> VAT
            </td>
        </tr>
    </tfoot>
</table>

<p><strong>Status:</strong> <span class="badge badge-info"><?= ucfirst($order['status']) ?></span></p>

<p>We'll notify you as soon as your order status changes. You can also track your order status in your account dashboard.</p>

<p>Best regards,<br>The <?= htmlspecialchars($cleanSiteName) ?> Team</p>
