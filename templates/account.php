<?php // templates/account.php ?>
<style>
.btn-cancel-order {
  color: var(--danger-ink);
  border-color: var(--danger-ink);
}
.btn-cancel-order:hover {
  background: var(--danger-ink) !important;
  color: var(--white) !important;
  border-color: var(--danger-ink) !important;
}
</style>
<div class="container">
  <h1 class="page-title">My Account</h1>

  <div class="account-layout" style="display:grid; grid-template-columns: 320px 1fr; gap: 2rem;">
    <aside>
      <div class="card" style="margin-bottom: 2rem;">
        <div style="font-family:var(--font-display);font-size:1.2rem;font-weight:600;margin-bottom:.25rem;">
          <?= h($current_user->name) ?>
        </div>
        <div style="color:var(--ink-2);font-size:.85rem;margin-bottom:1rem;"><?= h($current_user->email) ?></div>
        <span class="badge <?= $current_user->isAdmin() ? 'badge-danger' : 'badge-neutral' ?>"
              style="margin-bottom:1.2rem;display:inline-block;">
          <?= ucfirst($current_user->role) ?>
        </span>
        <div style="font-size:.8rem;color:var(--ink-2);">
          Member since <?= date('M Y', strtotime($current_user->created_at)) ?>
        </div>
        <hr style="margin:1.2rem 0;border:none;border-top:1px solid var(--line);">
        <a href="/account/downloads" class="btn btn-outline btn-sm" style="width:100%;justify-content:center;margin-bottom:0.5rem;">📥 Digital Library</a>
        <a href="/wishlist" class="btn btn-outline btn-sm" style="width:100%;justify-content:center;margin-bottom:0.5rem;">❤️ My Wishlist</a>
        <a href="/logout" class="btn btn-outline btn-sm" style="width:100%;justify-content:center;">Sign Out</a>
      </div>

      <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2 style="font-size:1.2rem; margin:0;">Address Book</h2>
        <a href="/account/addresses/new" class="btn btn-outline btn-sm" style="padding: 0.2rem 0.5rem;">+ Add</a>
      </div>

      <?php if (!empty($addresses)): ?>
        <?php foreach ($addresses as $addr): ?>
          <div class="card" style="margin-bottom: 1rem; padding: 1rem; border: 1px solid <?= $addr->isDefault() ? 'var(--accent)' : 'var(--line)' ?>;">
            <?php if ($addr->isDefault()): ?>
              <span class="badge badge-success" style="font-size: 0.65rem; margin-bottom: 0.5rem; display: inline-block;">Default</span>
            <?php endif; ?>
            <div style="font-weight: 600; margin-bottom: 0.25rem; font-size: 1rem; color: var(--accent);"><?= h($addr->label ?? 'Address') ?></div>
            <div style="font-weight: 500; margin-bottom: 0.25rem; font-size: 0.9rem;"><?= h($addr->name) ?></div>
            <div style="font-size: 0.8rem; line-height: 1.4; color: var(--ink-2);">
              <?= nl2br(h($addr->address)) ?><br>
              <?= h($addr->city) ?>, <?= h($addr->postcode) ?><br>
              <?= h($addr->country) ?>
            </div>
            <div style="margin-top: 1rem; display: flex; gap: 0.4rem;">
              <a href="/account/addresses/edit?id=<?= $addr->id ?>" class="btn btn-outline btn-sm" style="flex:1; justify-content: center; font-size: 0.75rem;">Edit</a>
              <form action="/account/addresses/delete" method="post" style="flex:1;" onsubmit="return confirm('Delete this address?')">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $addr->id ?>">
                <button type="submit" class="btn btn-outline btn-sm btn-delete" style="width:100%; justify-content: center; font-size: 0.75rem;">Delete</button>
              </form>
            </div>
            <?php if (!$addr->isDefault()): ?>
              <form action="/account/addresses/default" method="post" style="margin-top: 0.4rem;">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $addr->id ?>">
                <button type="submit" class="btn btn-outline btn-sm" style="width:100%; justify-content: center; font-size: 0.75rem;">Set as Default</button>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="card" style="padding: 1.5rem; text-align: center; color: var(--ink-2); font-size: 0.85rem;">
          No addresses saved yet.
        </div>
      <?php endif; ?>
    </aside>

    <main>
      <h2 style="font-family:var(--font-display);font-size:1.3rem;margin-bottom:1rem;">Order History</h2>

      <?php if (!empty($msg)): ?>
        <div class="alert alert-info" style="margin-bottom:1.5rem;"><?= h($msg) ?></div>
      <?php endif; ?>

      <?php if (!empty($msg_error)): ?>
        <div class="alert alert-danger" style="margin-bottom:1.5rem;"><?= h($msg_error) ?></div>
      <?php endif; ?>

      <?php if (!$orders): ?>
        <div class="card empty-state" style="padding:4rem; text-align: center;">
          <div class="icon" style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
          <h3>No orders yet</h3>
          <p>
            <a href="<?= BASE_URL ?>/" class="btn btn-primary" style="margin-top:.75rem;">Start Shopping</a>
          </p>
        </div>
      <?php else: ?>
        <div class="card" style="padding:0;overflow:hidden;">
          <div class="table-scroll">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Order #</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th><th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($orders as $o): ?>
                  <tr>
                    <td><strong><?= $o->getFormattedId() ?></strong></td>
                    <td><?= date('d M Y', strtotime($o->created_at)) ?></td>
                    <td><?= $o->item_count ?> item<?= $o->item_count != 1 ? 's' : '' ?></td>
                    <td><strong><?= money($o->total) ?></strong></td>
                    <td>
                      <span class="badge <?= $o->getStatusBadgeClass() ?>">
                        <?= ucfirst($o->status) ?>
                      </span>
                    </td>
                    <td style="display:flex;gap:0.5rem;justify-content:flex-end;">
                      <a href="/account/orders/<?= $o->id ?>" class="btn btn-outline btn-sm">View</a>
                      <?php if ($o->canBeCancelled()): ?>
                        <form method="POST" action="/account/cancel-order" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                          <?= csrf_field() ?>
                          <input type="hidden" name="id" value="<?= $o->id ?>">
                          <button type="submit" class="btn btn-outline btn-sm btn-cancel-order">Cancel</button>
                        </form>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>
    </main>
  </div>
</div>
