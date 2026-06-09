<?php // templates/account_address_form.php ?>
<div class="container">
    <?= new \App\View\Components\Breadcrumbs([
        'Home' => '/',
        'My Account' => '/account',
        $page_title
    ]) ?>

    <h1 class="page-title"><?= h($page_title) ?></h1>

    <div class="card" style="max-width: 600px; margin: 0 auto;">
        <form action="/account/addresses/save" method="post">
            <?= csrf_field() ?>
            <?php if (!$is_new): ?>
                <input type="hidden" name="id" value="<?= $address?->id ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="label">Address Label</label>
                <input type="text" name="label" id="label" class="form-control" value="<?= h($address?->label ?? '') ?>" required placeholder="e.g. Home, Work, or Mom's House">
                <small style="color:var(--ink-2);">A simple name to help you identify this address.</small>
            </div>

            <div class="form-group">
                <label for="name">Recipient Full Name</label>
                <input type="text" name="name" id="name" class="form-control" value="<?= h($address?->name ?? '') ?>" required placeholder="e.g. John Doe">
                <small style="color:var(--ink-2);">The name of the person receiving the package.</small>
            </div>

            <div class="form-group">
                <label for="address">Street Address</label>
                <textarea name="address" id="address" class="form-control" rows="3" required><?= h($address?->address ?? '') ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" name="city" id="city" class="form-control" value="<?= h($address?->city ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="postcode">Postcode</label>
                    <input type="text" name="postcode" id="postcode" class="form-control" value="<?= h($address?->postcode ?? '') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="country">Country</label>
                <input type="text" name="country" id="country" class="form-control" value="<?= h($address?->country ?? 'United Kingdom') ?>" required>
            </div>

            <div class="form-group">
                <label class="toggle-row">
                    <input type="checkbox" name="is_default" value="1" <?= (($address && $address->isDefault()) || ($is_new && ($is_first ?? false))) ? 'checked' : '' ?>>
                    <span class="toggle-track"></span>
                    <span class="toggle-label">Set as default address</span>
                </label>
            </div>

            <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary">Save Address</button>
                <a href="/account" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
