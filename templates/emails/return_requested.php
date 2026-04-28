<h1>Return Request Received</h1>
<p>Hello,</p>
<p>We have received your return request for order <strong>#<?= str_pad($return->order_id, 6, '0', STR_PAD_LEFT) ?></strong> (Return Request #<?= $return->id ?>).</p>

<div style="background: #f9f9f9; padding: 20px; border-radius: 5px; margin: 25px 0; border-left: 4px solid var(--accent);">
    <h3 style="margin-top: 0;">How to return your items:</h3>
    <ol>
        <li><strong>Package your items:</strong> Please ensure the products are in their original packaging and securely sealed.</li>
        <li><strong>Include your details:</strong> Put a note inside the package with your Order Number (#<?= str_pad($return->order_id, 6, '0', STR_PAD_LEFT) ?>) and Return ID (#<?= $return->id ?>).</li>
        <li><strong>Ship to us:</strong> Send the package to the following address:
            <p style="margin: 10px 0 0 20px; font-style: italic;">
                <?= htmlspecialchars($cleanSiteName) ?> Returns Dept.<br>
                123 Warehouse Lane<br>
                Manchester<br>
                Lancashire<br>
                M1 1AB
            </p>
        </li>
    </ol>
</div>

<p>Once we receive and inspect your items, we will update the status of your return request. <strong>If your return is approved, a refund will be automatically issued to your original payment method.</strong></p>

<p>You can track the progress of your return request by logging into your account.</p>

<p style="text-align: center; margin: 30px 0;">
    <a href="<?= $baseUrl ?>/account/orders/<?= $return->order_id ?>" class="btn">View Return Details</a>
</p>

<p>Thank you for shopping with us.</p>
<p>Best regards,<br>The <?= htmlspecialchars($cleanSiteName) ?> Team</p>
