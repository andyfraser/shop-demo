<?php
namespace App\Services;

class EmailService {
    public function __construct(private SettingsService $settings) {}

    private function getFromEmail(): string {
        return $this->settings->get('email_from') ?: 'noreply@shop.local';
    }

    private function getSiteName(): string {
        return $this->settings->get('site_name') ?: 'Demoshop';
    }

    private function getCleanSiteName(): string {
        return str_replace('|', '', $this->getSiteName());
    }

    public function sendVerificationEmail(string $toEmail, string $name, string $token): bool {
        $baseUrl = $this->getBaseUrl();
        $verifyUrl = $baseUrl . "/verify-email?token=" . $token;

        $subject = "Verify your email - " . $this->getCleanSiteName();
        $message = $this->renderTemplate('verification', [
            'name' => $name,
            'verifyUrl' => $verifyUrl,
            'subject' => $subject
        ]);

        return $this->sendHtmlEmail($toEmail, $subject, $message);
    }

    public function sendOrderConfirmation(array $order, array $items): bool {
        $subject = "Order Confirmation #" . $order['id'] . " - " . $this->getCleanSiteName();
        
        $message = $this->renderTemplate('order_confirmation', [
            'order' => $order,
            'items' => $items,
            'subject' => $subject
        ]);

        return $this->sendHtmlEmail($order['customer_email'], $subject, $message);
    }

    public function sendStatusUpdateEmail(string $toEmail, int $orderId, string $status): bool {
        $subject = "Order Status Updated #" . $orderId . " - " . $this->getCleanSiteName();
        
        $message = $this->renderTemplate('order_status', [
            'orderId' => $orderId,
            'status' => $status,
            'subject' => $subject
        ]);

        return $this->sendHtmlEmail($toEmail, $subject, $message);
    }

    private function getBaseUrl(): string {
        if (isset($_SERVER['HTTP_HOST'])) {
            return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
        }
        return $this->settings->get('site_url') ?: 'http://localhost';
    }

    private function renderTemplate(string $template, array $vars = []): string {
        $vars['siteName'] = $this->getSiteName();
        $vars['cleanSiteName'] = $this->getCleanSiteName();
        $vars['baseUrl'] = $this->getBaseUrl();
        
        extract($vars);
        
        ob_start();
        $templateFile = __DIR__ . '/../../templates/emails/' . $template . '.php';
        if (file_exists($templateFile)) {
            require $templateFile;
        } else {
            echo "Template not found: {$template}";
        }
        $content = ob_get_clean();
        
        ob_start();
        require __DIR__ . '/../../templates/emails/layout.php';
        return ob_get_clean();
    }

    private function sendHtmlEmail(string $to, string $subject, string $message): bool {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . $this->getCleanSiteName() . " <" . $this->getFromEmail() . ">" . "\r\n";

        return mail($to, $subject, $message, $headers);
    }
}
