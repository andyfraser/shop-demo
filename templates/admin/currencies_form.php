<?php // templates/admin/currencies_form.php ?>

<div class="admin-topbar">
    <h1><?= $is_new ? 'Add Currency' : 'Edit Currency' ?></h1>
    <div class="actions">
        <a href="/admin/currencies" class="btn btn-outline">← Back to List</a>
    </div>
</div>

<div class="content">
    <?php if ($errors): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php
        $get = fn($key) => isset($currency) && $currency ? (is_object($currency) ? ($currency->$key ?? null) : ($currency[$key] ?? null)) : null;
    ?>

    <div class="card card-sm">
        <form method="POST" action="/admin/currencies/save">
            <?= csrf_field() ?>
            <?php if (!$is_new): ?>
                <input type="hidden" name="id" value="<?= $get('id') ?>">
            <?php endif; ?>

            <div class="form-group">
                <label>Currency Code (ISO 4217, e.g. USD, EUR, GBP)</label>
                <input type="text" name="code" class="form-control" maxlength="3" 
                       value="<?= h($get('code') ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label>Name (e.g. US Dollar)</label>
                <input type="text" name="name" class="form-control" 
                       value="<?= h($get('name') ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label>Symbol (e.g. $, €, £)</label>
                <input type="text" name="symbol" class="form-control" 
                       value="<?= h($get('symbol') ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label>Exchange Rate (1 Base = X this currency)</label>
                <input type="number" name="exchange_rate" step="0.0001" min="0" class="form-control" 
                       value="<?= h($get('exchange_rate') ?? '1.0000') ?>" required>
                <div class="form-hint">If this is the Base currency, the rate should be 1.0000</div>
            </div>

            <div class="form-group">
                <label class="toggle-label">
                    <input type="checkbox" name="active" value="1" <?= ($get('active') ?? 1) ? 'checked' : '' ?>>
                    <span class="toggle-track"></span>
                    Active
                </label>
            </div>

            <div class="form-group">
                <label class="toggle-label">
                    <input type="checkbox" name="is_base" value="1" <?= ($get('is_base') ?? 0) ? 'checked' : '' ?>>
                    <span class="toggle-track"></span>
                    Is Base Currency
                </label>
                <div class="form-hint">Setting this as base will unmark any other currency as base.</div>
            </div>

            <div class="form-actions border-top mt-2 pt-1">
                <button type="submit" class="btn btn-primary"><?= $is_new ? 'Create Currency' : 'Update Currency' ?></button>
            </div>
        </form>
    </div>
</div>
