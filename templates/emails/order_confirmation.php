<h1>Thank you for your order!</h1>
<p>Hello,</p>
<p>Your order <strong><?= $order->getFormattedId() ?></strong> has been received and is now being processed.</p>

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
            <td>
                <?= h($item['name']) ?>
                <?php if (!empty($item['variant_name'])): ?>
                    <div style="font-size: 0.8em; color: #666; margin-top: 2px;">Option: <?= h($item['variant_name']) ?></div>
                <?php endif; ?>
                <?php if (!empty($item['metadata'])): 
                    $meta = json_decode($item['metadata'], true);
                    if (!empty($meta['recipient_email'])):
                ?>
                    <div style="font-size: 0.8em; color: #666; margin-top: 4px; background: #f9f9f9; padding: 6px; border-radius: 4px; border: 1px solid #eee; display: inline-block;">
                        <strong>Gift Card Details:</strong><br>
                        To: <?= h($meta['recipient_email']) ?>
                        <?php if (!empty($meta['sender_name'])): ?> | From: <?= h($meta['sender_name']) ?><?php endif; ?>
                        <?php if (!empty($meta['message'])): ?><br><em>Note: <?= h($meta['message']) ?></em><?php endif; ?>
                    </div>
                <?php 
                    endif;
                endif; ?>
            </td>
            <td style="text-align: center;"><?= $item['quantity'] ?></td>
            <td style="text-align: right;"><?= money($item['unit_price'] * $item['quantity']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2" style="text-align: right; padding-top: 20px; color: #666;">Subtotal</td>
            <td style="text-align: right; padding-top: 20px;"><?= money($order->total - ($order->delivery_cost ?? 0) + $order->discount_amount + $order->gift_card_amount) ?></td>
        </tr>
        <?php if ($order->discount_amount > 0): ?>
          <?php if (!empty($order->applied_promotions)): ?>
            <?php foreach ($order->applied_promotions as $promo): ?>
              <tr>
                <td colspan="2" style="text-align: right; color: #c8622a;">Discount (<?= h($promo['promotion_name'] ?? $promo['name']) ?>)</td>
                <td style="text-align: right; color: #c8622a;">-<?= money($promo['discount_amount']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="2" style="text-align: right; color: #c8622a;">Discount (<?= h($order->promotion_name ?: $order->applied_promo_name) ?>)</td>
              <td style="text-align: right; color: #c8622a;">-<?= money($order->discount_amount) ?></td>
            </tr>
          <?php endif; ?>
        <?php endif; ?>
        <?php if ($order->delivery_method): ?>
        <tr>
            <td colspan="2" style="text-align: right; color: #666;">Delivery (<?= h($order->delivery_method) ?>)</td>
            <td style="text-align: right;"><?= money($order->delivery_cost) ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($order->gift_card_amount > 0): ?>
        <tr>
            <td colspan="2" style="text-align: right; color: #c8622a;">Paid using gift cards</td>
            <td style="text-align: right; color: #c8622a;">-<?= money($order->gift_card_amount) ?></td>
        </tr>
        <?php endif; ?>
        <tr class="total-row">
            <td colspan="2" style="text-align: right; padding-top: 10px; font-weight: bold; font-size: 1.2em;">Order Total</td>
            <td style="text-align: right; padding-top: 10px; font-weight: bold; font-size: 1.2em;"><?= money($order->total) ?></td>
        </tr>
        <tr>
            <td colspan="3" style="text-align: right; font-size: 0.85em; color: #666; padding-top: 5px;">
                Includes <?= money($order->total_vat_amount) ?> VAT
            </td>
        </tr>
    </tfoot>
</table>

<p><strong>Status:</strong> <span class="badge badge-info"><?= ucfirst($order->status) ?></span></p>

<p>We'll notify you as soon as your order status changes. You can also track your order status in your account dashboard.</p>

<p>Best regards,<br>The <?= htmlspecialchars($cleanSiteName) ?> Team</p>
