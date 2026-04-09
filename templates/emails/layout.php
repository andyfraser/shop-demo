<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $subject ?></title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f5f0e8;
            font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #1a1410;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border: 1px solid rgba(26,20,16,.12);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 40px;
            margin-bottom: 40px;
        }
        .header {
            padding: 40px 40px 30px;
            text-align: center;
            border-bottom: 1px solid rgba(26,20,16,.08);
        }
        .logo {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 28px;
            font-weight: 700;
            color: #1a1410;
            text-decoration: none;
        }
        .logo span {
            color: #c8622a;
        }
        .content {
            padding: 40px;
            line-height: 1.6;
        }
        .footer {
            padding: 30px 40px;
            background-color: #1a1410;
            color: rgba(255,255,255,0.5);
            text-align: center;
            font-size: 13px;
        }
        .footer a {
            color: rgba(255,255,255,0.75);
            text-decoration: none;
        }
        h1 {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 24px;
            margin-top: 0;
            margin-bottom: 24px;
            color: #1a1410;
        }
        p {
            margin-top: 0;
            margin-bottom: 16px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #c8622a;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 3px;
            font-weight: 600;
            margin-top: 10px;
            margin-bottom: 10px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .table th {
            text-align: left;
            padding: 12px;
            border-bottom: 2px solid rgba(26,20,16,.1);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #4a3f35;
        }
        .table td {
            padding: 12px;
            border-bottom: 1px solid rgba(26,20,16,.08);
            font-size: 14px;
        }
        .total-row {
            font-weight: 700;
            color: #1a1410;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .badge-success { background-color: #d5f5e3; color: #1e8449; }
        .badge-info { background-color: #d6eaf8; color: #1a5276; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <?php
              $logo_parts = explode('|', $siteName, 2);
              $logo_prefix = $logo_parts[0];
              $logo_suffix = $logo_parts[1] ?? '';
            ?>
            <a href="<?= $baseUrl ?>" class="logo"><?= htmlspecialchars($logo_prefix) ?><?php if ($logo_suffix !== ''): ?><span><?= htmlspecialchars($logo_suffix) ?></span><?php endif ?></a>
        </div>
        <div class="content">
            <?= $content ?>
        </div>
        <div class="footer">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($cleanSiteName) ?>. All rights reserved.</p>
            <p><a href="<?= $baseUrl ?>">Visit our store</a></p>
        </div>
    </div>
</body>
</html>
