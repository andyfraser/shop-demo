<?php
namespace App\Services;

class EmailService {
    private static ?EmailService $instance = null;
    private string $fromEmail;
    private string $siteName;

    private function __construct() {
        $this->fromEmail = SettingsService::get('email_from') ?: 'noreply@shop.local';
        $this->siteName = SettingsService::get('site_name') ?: 'Demoshop';
    }

    public static function getInstance(): EmailService {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function sendVerificationEmail(string $toEmail, string $name, string $token): bool {
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
        $verifyUrl = $baseUrl . "/verify-email?token=" . $token;

        $subject = "Verify your email - " . $this->siteName;
        $message = "
            <html>
            <head>
                <title>Verify your email</title>
            </head>
            <body>
                <h1>Hello, " . htmlspecialchars($name) . "!</h1>
                <p>Thank you for registering at " . htmlspecialchars($this->siteName) . ".</p>
                <p>Please click the link below to verify your email address before you can make a purchase:</p>
                <p><a href='" . $verifyUrl . "'>" . $verifyUrl . "</a></p>
                <p>If you didn't create an account, you can safely ignore this email.</p>
            </body>
            </html>
        ";

        return $this->sendHtmlEmail($toEmail, $subject, $message);
    }

    public function sendOrderConfirmation(array $order, array $items): bool {
        $subject = "Order Confirmation #" . $order['id'] . " - " . $this->siteName;
        
        $itemsHtml = "";
        foreach ($items as $item) {
            $itemsHtml .= "<li>" . htmlspecialchars($item['name']) . " x " . $item['quantity'] . " - £" . number_format($item['unit_price'] * $item['quantity'], 2) . "</li>";
        }

        $message = "
            <html>
            <head>
                <title>Order Confirmation</title>
            </head>
            <body>
                <h1>Thank you for your order!</h1>
                <p>Order ID: #" . $order['id'] . "</p>
                <p>Total: £" . number_format($order['total'], 2) . "</p>
                <p>Status: " . ucfirst($order['status']) . "</p>
                <h3>Items:</h3>
                <ul>" . $itemsHtml . "</ul>
                <p>We'll notify you when your order status changes.</p>
            </body>
            </html>
        ";

        return $this->sendHtmlEmail($order['customer_email'], $subject, $message);
    }

    public function sendStatusUpdateEmail(string $toEmail, int $orderId, string $status): bool {
        $subject = "Order Status Updated #" . $orderId . " - " . $this->siteName;
        
        $statusText = "";
        if ($status === 'shipped') {
            $statusText = "Your order has been shipped and is on its way!";
        } else if ($status === 'cancelled') {
            $statusText = "Your order has been cancelled.";
        } else {
            $statusText = "Your order status has been updated to: " . ucfirst($status);
        }

        $message = "
            <html>
            <head>
                <title>Order Status Update</title>
            </head>
            <body>
                <h1>Order Status Update</h1>
                <p>Order ID: #" . $orderId . "</p>
                <p>" . $statusText . "</p>
            </body>
            </html>
        ";

        return $this->sendHtmlEmail($toEmail, $subject, $message);
    }

    private function sendHtmlEmail(string $to, string $subject, string $message): bool {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . $this->siteName . " <" . $this->fromEmail . ">" . "\r\n";

        return mail($to, $subject, $message, $headers);
    }
}
