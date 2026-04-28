<h1>Return Request Update</h1>
<p>Hello,</p>
<p>We're writing to let you know that your return request for order <strong>#<?= str_pad($return->order_id, 6, '0', STR_PAD_LEFT) ?></strong> has been updated.</p>

<p style="margin: 30px 0;">
    <strong>New status:</strong> 
    <span class="badge <?= $return->status === 'approved' ? 'badge-success' : ($return->status === 'rejected' ? 'badge-danger' : 'badge-info') ?>">
        <?= ucfirst($return->status) ?>
    </span>
</p>

<?php if ($return->status === 'approved'): ?>
    <p>Good news! Your return request has been approved. A refund of <strong><?= money($return->refund_amount) ?></strong> has been processed to your original payment method.</p>
<?php elseif ($return->status === 'rejected'): ?>
    <p>We're sorry, but your return request has been rejected.</p>
    <?php if ($return->reason): ?>
        <p><strong>Reason:</strong> <?= nl2br(htmlspecialchars($return->reason)) ?></p>
    <?php endif; ?>
<?php else: ?>
    <p>Your return request status is now: <?= ucfirst($return->status) ?></p>
<?php endif; ?>

<p>You can see the details of your return and its history by logging into your account.</p>

<p style="text-align: center; margin: 30px 0;">
    <a href="<?= $baseUrl ?>/account/orders/<?= $return->order_id ?>" class="btn">View Order Details</a>
</p>

<p>Best regards,<br>The <?= htmlspecialchars($cleanSiteName) ?> Team</p>
