<?php
namespace App\Controllers;

use App\Core\Renderer;
use App\Services\SecurityService;
use App\Services\SettingsService;

class AdminSettingsController {
    private Renderer $renderer;
    private SecurityService $securityService;
    private SettingsService $settingsService;

    public function __construct(Renderer $renderer, SecurityService $securityService, SettingsService $settingsService) {
        $this->renderer = $renderer;
        $this->securityService = $securityService;
        $this->settingsService = $settingsService;
    }

    public function show() {
        $this->renderer->adminRender('settings', [
            'page_title' => 'Settings',
            'active'     => 'settings',
            'settings'   => $this->settingsService->all(),
            'flash_msg'  => flash('msg'),
            'errors'     => [],
        ]);
    }

    public function save() {
        $this->securityService->verifyCsrf();

        $errors = [];

        $site_name      = trim($_POST['site_name'] ?? '');
        $currency       = trim($_POST['currency_symbol'] ?? '');
        $email_from     = trim($_POST['email_from'] ?? '');
        $pass_min       = (int)($_POST['password_min_length'] ?? 0);
        $login_att      = (int)($_POST['login_max_attempts'] ?? 0);
        $login_win      = (int)($_POST['login_window_minutes'] ?? 0);
        $reg_att        = (int)($_POST['register_max_attempts'] ?? 0);
        $reg_win        = (int)($_POST['register_window_minutes'] ?? 0);
        $low_stock      = (int)($_POST['low_stock_threshold'] ?? 0);
        $vat_rate       = (float)($_POST['default_vat_rate'] ?? 0);
        $remember_days  = (int)($_POST['remember_me_days'] ?? 0);
        $nav_max_top      = (int)($_POST['mobile_nav_max_top'] ?? 0);
        $nav_max_combined = (int)($_POST['mobile_nav_max_combined'] ?? 0);

        if ($site_name === '') $errors[] = 'Site name is required.';
        if ($currency === '')  $errors[] = 'Currency symbol is required.';
        if ($email_from === '') $errors[] = 'Email from address is required.';
        if ($pass_min < 1)    $errors[] = 'Password minimum length must be at least 1.';
        if ($login_att < 1)   $errors[] = 'Login max attempts must be at least 1.';
        if ($login_win < 1)   $errors[] = 'Login window must be at least 1 minute.';
        if ($reg_att < 1)     $errors[] = 'Registration max attempts must be at least 1.';
        if ($reg_win < 1)     $errors[] = 'Registration window must be at least 1 minute.';
        if ($low_stock < 0)   $errors[] = 'Low stock threshold must be at least 0.';
        if ($vat_rate < 0)    $errors[] = 'Default VAT rate must be at least 0.';
        if ($remember_days < 1) $errors[] = 'Remember me duration must be at least 1 day.';
        if ($nav_max_top < 1)      $errors[] = 'Mobile nav top-level threshold must be at least 1.';
        if ($nav_max_combined < 1) $errors[] = 'Mobile nav combined threshold must be at least 1.';

        if (!$errors) {
            $this->settingsService->set('site_name',               $site_name);
            $this->settingsService->set('currency_symbol',         $currency);
            $this->settingsService->set('email_from',              $email_from);
            $this->settingsService->set('password_min_length',     (string)$pass_min);
            $this->settingsService->set('login_max_attempts',      (string)$login_att);
            $this->settingsService->set('login_window_minutes',    (string)$login_win);
            $this->settingsService->set('register_max_attempts',   (string)$reg_att);
            $this->settingsService->set('register_window_minutes', (string)$reg_win);
            $this->settingsService->set('low_stock_threshold',     (string)$low_stock);
            $this->settingsService->set('default_vat_rate',        (string)$vat_rate);
            $this->settingsService->set('remember_me_days',        (string)$remember_days);
            $this->settingsService->set('mobile_nav_max_top',      (string)$nav_max_top);
            $this->settingsService->set('mobile_nav_max_combined', (string)$nav_max_combined);

            flash('msg', 'Settings saved.');
            redirect('/admin/settings');
        }

        $this->renderer->adminRender('settings', [
            'page_title' => 'Settings',
            'active'     => 'settings',
            'settings'   => [
                'site_name'               => $site_name,
                'currency_symbol'         => $currency,
                'email_from'              => $email_from,
                'password_min_length'     => (string)$pass_min,
                'login_max_attempts'      => (string)$login_att,
                'login_window_minutes'    => (string)$login_win,
                'register_max_attempts'   => (string)$reg_att,
                'register_window_minutes' => (string)$reg_win,
                'low_stock_threshold'     => (string)$low_stock,
                'default_vat_rate'        => (string)$vat_rate,
                'remember_me_days'        => (string)$remember_days,
                'mobile_nav_max_top'      => (string)$nav_max_top,
                'mobile_nav_max_combined' => (string)$nav_max_combined,
            ],
            'flash_msg'  => null,
            'errors'     => $errors,
        ]);
    }
}
