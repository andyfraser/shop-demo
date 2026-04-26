<?php
namespace App\Controllers;

use App\Core\Renderer;
use App\Services\SecurityService;
use App\Services\SettingsService;
use App\Models\Settings;

class AdminSettingsController {
    public function __construct(
        private Renderer $renderer,
        private SecurityService $securityService,
        private SettingsService $settingsService,
        private \Psr\Log\LoggerInterface $logger
    ) {}

    public function show() {
        $this->renderer->adminRender('settings', [
            'page_title' => 'Settings',
            'active'     => 'settings',
            'settings'   => $this->settingsService->getSettings(),
            'flash_msg'  => flash('msg'),
            'errors'     => [],
        ]);
    }

    public function save() {
        $this->securityService->verifyCsrf();

        $errors = [];
        $site_name = trim($_POST['site_name'] ?? '');
        $currency  = trim($_POST['currency_symbol'] ?? '');
        $email_from = trim($_POST['email_from'] ?? '');

        if ($site_name === '') $errors[] = 'Site name is required.';
        if ($currency === '')  $errors[] = 'Currency symbol is required.';
        if ($email_from === '') $errors[] = 'Email from address is required.';

        if (!$errors) {
            foreach ($_POST as $key => $val) {
                if ($key !== 'save' && $key !== 'csrf_token') {
                    $this->settingsService->set($key, $val);
                }
            }

            $this->logger->info("Admin updated site settings");
            flash('msg', 'Settings saved.');
            redirect('/admin/settings');
        }

        $settings = new Settings();
        $settings->fill($_POST);

        $this->renderer->adminRender('settings', [
            'page_title' => 'Settings',
            'active'     => 'settings',
            'settings'   => $settings,
            'flash_msg'  => null,
            'errors'     => $errors,
        ]);
    }
}
