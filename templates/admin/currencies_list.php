<?php // templates/admin/currencies_list.php ?>

<div class="admin-topbar">
    <h1>Currencies</h1>
    <div class="actions">
        <a href="/admin/currencies/new" class="btn btn-primary">+ Add Currency</a>
    </div>
</div>

<div class="content">
    <?php if ($flash_msg): ?>
        <div class="alert alert-success"><?= h($flash_msg) ?></div>
    <?php endif; ?>

    <table class="data-table">
        <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Symbol</th>
                <th>Exchange Rate (to Base)</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($currencies as $c): ?>
                <tr>
                    <td>
                        <strong><?= h($c->code) ?></strong>
                        <?php if ($c->is_base): ?>
                            <span class="badge badge-success ml-1">BASE</span>
                        <?php endif; ?>
                    </td>
                    <td><?= h($c->name) ?></td>
                    <td><?= h($c->symbol) ?></td>
                    <td>1 Base = <?= number_format($c->exchange_rate, 4) ?> <?= h($c->code) ?></td>
                    <td>
                        <span class="badge <?= $c->active ? 'badge-success' : 'badge-neutral' ?>">
                            <?= $c->active ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td class="text-right">
                        <a href="/admin/currencies/edit?id=<?= $c->id ?>" class="btn btn-outline btn-sm">Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
