<?php // templates/admin/reviews_list.php ?>

<div class="admin-topbar">
  <h1>Product Reviews</h1>
</div>

<div class="content">
  <?php if (isset($flash_msg)): ?>
    <div class="alert alert-success"><?= h($flash_msg) ?></div>
  <?php endif; ?>

  <table class="data-table">
    <thead>
      <tr>
        <th style="vertical-align:top">Date</th>
        <th style="vertical-align:top">Product</th>
        <th style="vertical-align:top">User</th>
        <th style="vertical-align:top">Rating</th>
        <th style="vertical-align:top">Comment</th>
        <th style="vertical-align:top">Status</th>
        <th style="vertical-align:top;text-align:right">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($reviews): ?>
        <?php foreach ($reviews as $r): ?>
          <tr>
            <td style="white-space:nowrap;vertical-align:top;"><?= date('d M Y', strtotime($r->created_at)) ?></td>
            <td style="vertical-align:top;"><strong><?= h($r->product_name) ?></strong></td>
            <td style="vertical-align:top;"><?= h($r->user_name) ?></td>
            <td style="vertical-align:top;">
              <div style="color:var(--gold);font-size:.8rem;">
                <?= $r->getStarRating() ?>
              </div>
            </td>
            <td style="max-width:300px;vertical-align:top;"><?= nl2br(h($r->comment ?? '')) ?></td>
            <td style="vertical-align:top;">
              <span class="badge <?= $r->getStatusBadgeClass() ?>">
                <?= ucfirst($r->status) ?>
              </span>
            </td>
            <td style="text-align:right;vertical-align:top;">
              <form action="/admin/reviews/update-status" method="post" style="display:inline-flex;gap:.25rem;">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $r->id ?>">
                <?php if ($r->status !== 'approved'): ?>
                  <button type="submit" name="status" value="approved" class="btn btn-primary btn-sm" style="background:#28a745;border-color:#28a745;color:#fff;">Approve</button>
                <?php else: ?>
                  <button type="submit" name="status" value="pending" class="btn btn-outline btn-sm">Hide</button>
                <?php endif; ?>
                <?php if ($r->status !== 'rejected'): ?>
                  <button type="submit" name="status" value="rejected" class="btn btn-outline btn-sm" style="color:var(--error);border-color:var(--error);">Reject</button>
                <?php endif; ?>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="7" style="text-align:center;padding:2rem;color:var(--ink-2);">No reviews found.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
