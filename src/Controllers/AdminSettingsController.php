<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\HtmlResponse;
use App\Core\Responses\RedirectResponse;
use App\Core\Renderer;
use App\Services\SecurityServiceInterface;
use App\Services\SettingsServiceInterface;
use App\Models\Settings;
use App\Core\Cache\CacheInterface;

class AdminSettingsController {
    public function __construct(
        private Renderer $renderer,
        private SecurityServiceInterface $securityService,
        private SettingsServiceInterface $settingsService,
        private \Psr\Log\LoggerInterface $logger,
        private CacheInterface $cache
    ) {}

    public function show(Request $request): Response {
        $flashError = flash('error');
        $errors = $flashError ? [$flashError] : [];
        return new HtmlResponse($this->renderer->adminRender('settings', [
            'page_title' => 'Settings',
            'active'     => 'settings',
            'settings'   => $this->settingsService->getSettings(),
            'flash_msg'  => flash('msg'),
            'errors'     => $errors,
        ]));
    }

    public function save(Request $request): Response {
        $errors = [];
        $post = $request->getPost();
        $site_name = trim($post['site_name'] ?? '');
        $currency  = trim($post['currency_symbol'] ?? '');
        $email_from = trim($post['email_from'] ?? '');

        if ($site_name === '') $errors[] = 'Site name is required.';
        if ($currency === '')  $errors[] = 'Currency symbol is required.';
        if ($email_from === '') $errors[] = 'Email from address is required.';

        if (!$errors) {
            foreach ($post as $key => $val) {
                if ($key !== 'save' && $key !== 'csrf_token') {
                    $this->settingsService->set($key, $val);
                }
            }

            $this->logger->info("Admin updated site settings");
            flash('msg', 'Settings saved.');
            return new RedirectResponse('/admin/settings');
        }

        $settings = new Settings($this->logger);
        $settings->fill($post);

        return new HtmlResponse($this->renderer->adminRender('settings', [
            'page_title' => 'Settings',
            'active'     => 'settings',
            'settings'   => $settings,
            'flash_msg'  => null,
            'errors'     => $errors,
        ]));
    }

    public function clearCache(Request $request): Response {
        if ($this->cache->clear()) {
            $this->logger->info("Admin cleared application cache");
            flash('msg', 'Cache cleared successfully.');
        } else {
            $this->logger->error("Admin failed to clear application cache");
            flash('error', 'Failed to clear cache.');
        }
        return new RedirectResponse('/admin/settings');
    }
}
