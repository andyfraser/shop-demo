<h1>Order Status Update</h1>
<p>Hello,</p>
<p>We're writing to let you know that your order <strong><?= $order->getFormattedId() ?></strong> has been updated.</p>

<p style="margin: 30px 0;">
    <strong>New status:</strong> <span class="badge <?= $status === 'shipped' ? 'badge-success' : 'badge-info' ?>"><?= ucfirst($status) ?></span>
</p>

<?php if ($status === 'shipped'): ?>
    <p>Good news! Your order has been shipped and is on its way to you.</p>
<?php elseif ($status === 'cancelled'): ?>
    <p>We're sorry, but your order has been cancelled.</p>
<?php else: ?>
    <p>Your order status is now: <?= ucfirst($status) ?></p>
<?php endif; ?>

<p>You can see the details of your order and its history by logging into your account.</p>

<p style="text-align: center; margin: 30px 0;">
    <a href="<?= $baseUrl ?>/account/orders/<?= $order->id ?>" class="btn">View Order Details</a>
</p>

<p>Best regards,<br>The <?= htmlspecialchars($cleanSiteName) ?> Team</p>
