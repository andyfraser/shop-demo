<?php
namespace App\Controllers;

use App\Core\Renderer;
use App\Services\SecurityService;
use App\Services\SettingsService;

class AdminSettingsController {
    public function show() {
        Renderer::adminRender('settings', [
            'page_title' => 'Settings',
            'active'     => 'settings',
            'settings'   => SettingsService::all(),
            'flash_msg'  => flash('msg'),
            'errors'     => [],
        ]);
    }

    public function save() {
        SecurityService::verifyCsrf();

        $errors = [];

        $site_name = trim($_POST['site_name'] ?? '');
        $currency  = trim($_POST['currency_symbol'] ?? '');
        $email_from = trim($_POST['email_from'] ?? '');
        $pass_min  = (int)($_POST['password_min_length'] ?? 0);
        $login_att = (int)($_POST['login_max_attempts'] ?? 0);
        $login_win = (int)($_POST['login_window_minutes'] ?? 0);
        $reg_att   = (int)($_POST['register_max_attempts'] ?? 0);
        $reg_win   = (int)($_POST['register_window_minutes'] ?? 0);
        $low_stock      = (int)($_POST['low_stock_threshold'] ?? 0);
        $remember_days  = (int)($_POST['remember_me_days'] ?? 0);

        if ($site_name === '') $errors[] = 'Site name is required.';
        if ($currency === '')  $errors[] = 'Currency symbol is required.';
        if ($email_from === '') $errors[] = 'Email from address is required.';
        if ($pass_min < 1)    $errors[] = 'Password minimum length must be at least 1.';
        if ($login_att < 1)   $errors[] = 'Login max attempts must be at least 1.';
        if ($login_win < 1)   $errors[] = 'Login window must be at least 1 minute.';
        if ($reg_att < 1)     $errors[] = 'Registration max attempts must be at least 1.';
        if ($reg_win < 1)     $errors[] = 'Registration window must be at least 1 minute.';
        if ($low_stock < 0)   $errors[] = 'Low stock threshold must be at least 0.';
        if ($remember_days < 1) $errors[] = 'Remember me duration must be at least 1 day.';

        if (!$errors) {
            SettingsService::set('site_name',               $site_name);
            SettingsService::set('currency_symbol',         $currency);
            SettingsService::set('email_from',              $email_from);
            SettingsService::set('password_min_length',     (string)$pass_min);
            SettingsService::set('login_max_attempts',      (string)$login_att);
            SettingsService::set('login_window_minutes',    (string)$login_win);
            SettingsService::set('register_max_attempts',   (string)$reg_att);
            SettingsService::set('register_window_minutes', (string)$reg_win);
            SettingsService::set('low_stock_threshold',     (string)$low_stock);
            SettingsService::set('remember_me_days',        (string)$remember_days);

            flash('msg', 'Settings saved.');
            redirect('/admin/settings');
        }

        Renderer::adminRender('settings', [
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
                'remember_me_days'        => (string)$remember_days,
            ],
            'flash_msg'  => null,
            'errors'     => $errors,
        ]);
    }
}
