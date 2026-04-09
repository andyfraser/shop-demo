<h1>Verify your email</h1>
<p>Hello <?= htmlspecialchars($name) ?>,</p>
<p>Thank you for registering at <strong><?= htmlspecialchars($cleanSiteName) ?></strong>. We're excited to have you with us!</p>
<p>To get started, please confirm your email address by clicking the button below:</p>
<p style="text-align: center; margin: 30px 0;">
    <a href="<?= $verifyUrl ?>" class="btn">Confirm Email Address</a>
</p>
<p>If the button doesn't work, you can also copy and paste the following link into your browser:</p>
<p style="word-break: break-all; font-size: 13px; color: #4a3f35;"><a href="<?= $verifyUrl ?>"><?= $verifyUrl ?></a></p>
<p>If you didn't create an account, you can safely ignore this email.</p>
<p>Best regards,<br>The <?= htmlspecialchars($cleanSiteName) ?> Team</p>
