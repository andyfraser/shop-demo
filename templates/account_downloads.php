<?php // templates/account_downloads.php ?>
<div class="container">
  <h1 class="page-title">My Digital Library</h1>

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
        <a href="/account" class="btn btn-outline btn-sm" style="width:100%;justify-content:center;margin-bottom:0.5rem;">📦 Order History</a>
        <a href="/account/downloads" class="btn btn-primary btn-sm" style="width:100%;justify-content:center;margin-bottom:0.5rem;">📥 Digital Library</a>
        <a href="/wishlist" class="btn btn-outline btn-sm" style="width:100%;justify-content:center;margin-bottom:0.5rem;">❤️ My Wishlist</a>
        <a href="/logout" class="btn btn-outline btn-sm" style="width:100%;justify-content:center;">Sign Out</a>
      </div>
    </aside>

    <main>
      <?php if (!empty($msg)): ?>
        <div class="alert alert-info" style="margin-bottom:1.5rem;"><?= h($msg) ?></div>
      <?php endif; ?>

      <?php if (!empty($msg_error)): ?>
        <div class="alert alert-danger" style="margin-bottom:1.5rem;"><?= h($msg_error) ?></div>
      <?php endif; ?>

      <!-- Segmented Library View -->
      <section style="margin-bottom: 3rem;">
        <h2 style="font-family:var(--font-display);font-size:1.4rem;margin-bottom:1.2rem;display:flex;align-items:center;gap:0.5rem;">
          <span>📁</span> Purchased Files
        </h2>
        
        <?php if (empty($downloads)): ?>
          <div class="card" style="padding: 2.5rem; text-align: center; color: var(--ink-2);">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📥</div>
            <div style="font-weight: 600; margin-bottom: 0.25rem;">No files available yet</div>
            <p style="font-size: 0.85rem; margin: 0;">Any digital file downloads you purchase will appear here.</p>
          </div>
        <?php else: ?>
          <div class="card" style="padding: 0; overflow: hidden;">
            <table class="data-table" style="margin: 0; width: 100%;">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Downloads Remaining</th>
                  <th>Expiration</th>
                  <th style="text-align: right;">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($downloads as $d): ?>
                  <?php 
                    $isExpired = $d['expires_at'] !== null && strtotime($d['expires_at']) < time();
                    $limitReached = $d['max_downloads'] !== null && $d['download_count'] >= $d['max_downloads'];
                    $canDownload = !$isExpired && !$limitReached;
                  ?>
                  <tr>
                    <td>
                      <div style="font-weight: 600;"><?= h($d['product_name']) ?></div>
                      <?php if (!empty($d['variant_name'])): ?>
                        <div style="font-size: 0.75rem; color: var(--ink-2);"><?= h($d['variant_name']) ?></div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($d['max_downloads'] === null): ?>
                        <span style="color: var(--success); font-weight: 500;">Unlimited</span>
                      <?php else: ?>
                        <?= ($d['max_downloads'] - $d['download_count']) ?> of <?= $d['max_downloads'] ?> left
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($d['expires_at'] === null): ?>
                        <span style="color: var(--ink-2);">Never expires</span>
                      <?php else: ?>
                        <span style="color: <?= $isExpired ? 'var(--danger-ink)' : 'inherit' ?>;">
                          <?= date('d M Y H:i', strtotime($d['expires_at'])) ?>
                          <?php if ($isExpired): ?> (Expired)<?php endif; ?>
                        </span>
                      <?php endif; ?>
                    </td>
                    <td style="text-align: right;">
                      <?php if ($canDownload): ?>
                        <a href="/download/<?= h($d['download_token']) ?>" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 0.25rem;">
                          <span>⬇️</span> Download
                        </a>
                      <?php else: ?>
                        <button class="btn btn-outline btn-sm" disabled style="opacity: 0.6; cursor: not-allowed;">
                          <?= $limitReached ? 'Limit Reached' : 'Expired' ?>
                        </button>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>

      <section style="margin-bottom: 3rem;">
        <h2 style="font-family:var(--font-display);font-size:1.4rem;margin-bottom:1.2rem;display:flex;align-items:center;gap:0.5rem;">
          <span>🔑</span> Software License Keys
        </h2>
        
        <?php if (empty($licenses)): ?>
          <div class="card" style="padding: 2.5rem; text-align: center; color: var(--ink-2);">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🔑</div>
            <div style="font-weight: 600; margin-bottom: 0.25rem;">No license keys found</div>
            <p style="font-size: 0.85rem; margin: 0;">Purchased software activation and license keys will be displayed here.</p>
          </div>
        <?php else: ?>
          <div class="grid" style="display: grid; grid-template-columns: 1fr; gap: 1rem;">
            <?php foreach ($licenses as $l): ?>
              <div class="card" style="display: flex; justify-content: space-between; align-items: center; padding: 1.25rem;">
                <div>
                  <div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 0.25rem;"><?= h($l['product_name']) ?></div>
                  <div style="font-size: 0.8rem; color: var(--ink-2);">Assigned on <?= date('d M Y', strtotime($l['created_at'])) ?></div>
                </div>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                  <code id="license-key-<?= $l['id'] ?>" style="font-family: monospace; font-size: 1rem; background: var(--bg-2); padding: 0.4rem 0.8rem; border-radius: 4px; border: 1px solid var(--line); color: var(--accent); font-weight: 600; letter-spacing: 0.5px;">
                    <?= h($l['license_key']) ?>
                  </code>
                  <button class="btn btn-outline btn-sm" onclick="copyToClipboard('<?= h($l['license_key']) ?>', this)" style="padding: 0.4rem 0.6rem;">Copy</button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

      <section style="margin-bottom: 3rem;">
        <h2 style="font-family:var(--font-display);font-size:1.4rem;margin-bottom:1.2rem;display:flex;align-items:center;gap:0.5rem;">
          <span>🎟️</span> Event Tickets
        </h2>
        
        <?php if (empty($tickets)): ?>
          <div class="card" style="padding: 2.5rem; text-align: center; color: var(--ink-2);">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🎟️</div>
            <div style="font-weight: 600; margin-bottom: 0.25rem;">No active bookings or tickets</div>
            <p style="font-size: 0.85rem; margin: 0;">Purchased event tickets and bookings will appear here.</p>
          </div>
        <?php else: ?>
          <div class="grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
            <?php foreach ($tickets as $t): ?>
              <div class="card" style="position: relative; overflow: hidden; padding: 0; border: 1px dashed var(--line); border-radius: 8px; background: linear-gradient(135deg, var(--bg) 0%, var(--bg-2) 100%);">
                <!-- Ticket Header -->
                <div style="background: var(--accent); color: var(--bg); padding: 0.75rem 1rem; font-weight: 600; font-size: 0.85rem; letter-spacing: 0.5px; text-transform: uppercase;">
                  ADMIT ONE
                </div>
                
                <!-- Ticket Content -->
                <div style="padding: 1.25rem;">
                  <h3 style="font-size: 1.1rem; margin: 0 0 0.5rem 0; font-family: var(--font-display); font-weight: 600; line-height: 1.3; color: var(--ink);"><?= h($t['product_name']) ?></h3>
                  <div style="font-size: 0.75rem; color: var(--ink-2); margin-bottom: 1.25rem;">Booked: <?= date('d M Y', strtotime($t['created_at'])) ?></div>
                  
                  <div style="text-align: center; background: var(--bg); padding: 0.75rem; border-radius: 6px; border: 1px solid var(--line); margin-top: 0.5rem;">
                    <div style="font-size: 0.65rem; color: var(--ink-2); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.25rem;">Booking Reference</div>
                    <code style="font-size: 1.1rem; font-weight: 700; color: var(--accent); font-family: monospace; letter-spacing: 1px;">
                      <?= h($t['ticket_code']) ?>
                    </code>
                  </div>
                </div>
                
                <!-- Decorative Ticket Notches -->
                <div style="position: absolute; left: -10px; top: 50%; transform: translateY(-50%); width: 20px; height: 20px; background: var(--bg); border-radius: 50%; border-right: 1px dashed var(--line);"></div>
                <div style="position: absolute; right: -10px; top: 50%; transform: translateY(-50%); width: 20px; height: 20px; background: var(--bg); border-radius: 50%; border-left: 1px dashed var(--line);"></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
    </main>
  </div>
</div>

<script>
function copyToClipboard(text, btn) {
    navigator.clipboard.writeText(text).then(function() {
        const originalText = btn.innerText;
        btn.innerText = 'Copied!';
        btn.style.backgroundColor = 'var(--success)';
        btn.style.color = 'var(--bg)';
        setTimeout(function() {
            btn.innerText = originalText;
            btn.style.backgroundColor = '';
            btn.style.color = '';
        }, 2000);
    }, function(err) {
        console.error('Could not copy text: ', err);
    });
}
</script>
