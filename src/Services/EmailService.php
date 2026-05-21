<?php
namespace App\Services;

class EmailService implements EmailServiceInterface {
    public function __construct(
        private SettingsServiceInterface $settings,
        private \Psr\Log\LoggerInterface $logger
    ) {}

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

    public function sendOrderConfirmation(\App\Models\Order $order, array $items): bool {
        $subject = "Order Confirmation " . $order->getFormattedId() . " - " . $this->getCleanSiteName();
        
        $message = $this->renderTemplate('order_confirmation', [
            'order' => $order,
            'items' => $items,
            'subject' => $subject
        ]);

        return $this->sendHtmlEmail($order->customer_email, $subject, $message);
    }

    public function sendStatusUpdateEmail(string $toEmail, \App\Models\Order $order, string $status): bool {
        $formattedId = $order->getFormattedId();
        $subject = "Order Status Updated " . $formattedId . " - " . $this->getCleanSiteName();
        
        $message = $this->renderTemplate('order_status', [
            'order' => $order,
            'status' => $status,
            'subject' => $subject
        ]);

        return $this->sendHtmlEmail($toEmail, $subject, $message);
    }

    public function sendReturnRequestedEmail(\App\Models\ReturnOrder $return, string $toEmail): bool {
        $formattedId = '#' . str_pad((string)$return->id, 6, '0', STR_PAD_LEFT);
        $subject = "Return Request Received " . $formattedId . " - " . $this->getCleanSiteName();
        
        $message = $this->renderTemplate('return_requested', [
            'return' => $return,
            'subject' => $subject
        ]);

        return $this->sendHtmlEmail($toEmail, $subject, $message);
    }

    public function sendReturnUpdateEmail(\App\Models\ReturnOrder $return, string $toEmail): bool {
        $formattedId = '#' . str_pad((string)$return->id, 6, '0', STR_PAD_LEFT);
        $subject = "Return Request " . $formattedId . " Updated - " . $this->getCleanSiteName();
        
        $message = $this->renderTemplate('return_status', [
            'return' => $return,
            'subject' => $subject
        ]);

        return $this->sendHtmlEmail($toEmail, $subject, $message);
    }

    public function sendAbandonedCartEmail(string $toEmail, string $name): bool {
        $subject = "You left something in your cart! - " . $this->getCleanSiteName();
        
        $message = $this->renderTemplate('abandoned_cart', [
            'name' => $name,
            'subject' => $subject,
            'cartUrl' => $this->getBaseUrl() . '/cart'
        ]);

        return $this->sendHtmlEmail($toEmail, $subject, $message);
    }

    public function sendDigitalDownloadsEmail(string $toEmail, string $customerName, array $downloads): bool {
        $subject = "Your Digital Downloads - " . $this->getCleanSiteName();
        $message = $this->renderTemplate('digital_downloads', [
            'name' => $customerName,
            'downloads' => $downloads,
            'subject' => $subject
        ]);
        return $this->sendHtmlEmail($toEmail, $subject, $message);
    }

    public function sendGiftCardEmail(string $toEmail, string $recipientName, string $senderName, string $code, float $amount, ?string $messageContent): bool {
        $subject = "You've received a Gift Card! - " . $this->getCleanSiteName();
        $message = $this->renderTemplate('gift_card', [
            'recipientName' => $recipientName,
            'senderName' => $senderName,
            'code' => $code,
            'amount' => $amount,
            'messageContent' => $messageContent,
            'subject' => $subject
        ]);
        return $this->sendHtmlEmail($toEmail, $subject, $message);
    }

    public function sendLicenseKeyEmail(string $toEmail, string $customerName, array $licenses): bool {
        $subject = "Your Software License Keys - " . $this->getCleanSiteName();
        $message = $this->renderTemplate('license_keys', [
            'name' => $customerName,
            'licenses' => $licenses,
            'subject' => $subject
        ]);
        return $this->sendHtmlEmail($toEmail, $subject, $message);
    }

    public function sendEventTicketEmail(string $toEmail, string $customerName, array $tickets): bool {
        $subject = "Your Event Tickets - " . $this->getCleanSiteName();
        $message = $this->renderTemplate('event_tickets', [
            'name' => $customerName,
            'tickets' => $tickets,
            'subject' => $subject
        ]);
        return $this->sendHtmlEmail($toEmail, $subject, $message);
    }

    private function getBaseUrl(): string {
        if (isset($_SERVER['HTTP_HOST'])) {
            $host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
        } else {
            $host = $this->settings->get('site_url') ?: 'http://localhost';
        }

        $path = defined('BASE_URL') ? BASE_URL : '';
        return rtrim(rtrim($host, '/') . $path, '/');
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

        $success = mail($to, $subject, $message, $headers);
        if ($success) {
            $this->logger->info("Email sent to {to} with subject: {subject}", ['to' => $to, 'subject' => $subject]);
        } else {
            $this->logger->error("Failed to send email to {to} with subject: {subject}", ['to' => $to, 'subject' => $subject]);
        }
        return $success;
    }
}
