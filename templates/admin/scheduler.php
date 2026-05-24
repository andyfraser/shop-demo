<?php // templates/admin/scheduler.php
// Expects: $page_title, $active, $tasks, $is_paused, $cron_token, $console_output
?>
<style>
.scheduler-header-card {
    background: linear-gradient(135deg, #16120e 0%, #2e1c12 100%);
    border-radius: 4px;
    padding: 2.2rem;
    color: var(--white);
    margin-bottom: 2rem;
    box-shadow: var(--shadow);
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-left: 5px solid var(--accent);
    position: relative;
    overflow: hidden;
}
.scheduler-header-card::after {
    content: '⏱️';
    position: absolute;
    right: -10px;
    bottom: -20px;
    font-size: 10rem;
    opacity: 0.04;
    pointer-events: none;
}
.scheduler-header-card h2 {
    font-family: var(--font-display);
    font-size: 2rem;
    margin-bottom: 0.6rem;
    font-weight: 700;
    letter-spacing: -0.02em;
}
.scheduler-status {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    font-size: 1rem;
    font-weight: 500;
}
.status-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
    position: relative;
}
.status-active-dot {
    background-color: #2ecc71;
    box-shadow: 0 0 12px #2ecc71;
}
.status-active-dot::after {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    border-radius: 50%;
    border: 2px solid #2ecc71;
    animation: pulse-green 2s infinite ease-in-out;
}
.status-paused-dot {
    background-color: #e74c3c;
    box-shadow: 0 0 12px #e74c3c;
}
.status-paused-dot::after {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    border-radius: 50%;
    border: 2px solid #e74c3c;
    animation: pulse-red 2s infinite ease-in-out;
}
@keyframes pulse-green {
    0% { transform: scale(1); opacity: 1; }
    100% { transform: scale(2.2); opacity: 0; }
}
@keyframes pulse-red {
    0% { transform: scale(1); opacity: 1; }
    100% { transform: scale(2.2); opacity: 0; }
}

.scheduler-console {
    background: #0d0a08;
    border-radius: 4px;
    padding: 1.5rem;
    color: #39ff14;
    font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
    font-size: 0.85rem;
    line-height: 1.6;
    overflow-x: auto;
    margin-bottom: 2rem;
    box-shadow: inset 0 2px 8px rgba(0,0,0,0.8), var(--shadow);
    border: 1px solid rgba(255,255,255,0.06);
    position: relative;
}
.scheduler-console::before {
    content: 'TERMINAL CONSOLE';
    position: absolute;
    top: 0.5rem;
    right: 1rem;
    font-size: 0.65rem;
    letter-spacing: 0.1em;
    color: rgba(57, 255, 20, 0.4);
    font-weight: 600;
}

.cron-setup-card {
    background: var(--white);
    border: 1px solid var(--line);
    border-radius: 4px;
    padding: 1.8rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow);
}
.cron-setup-card h3 {
    font-family: var(--font-display);
    font-size: 1.3rem;
    margin-bottom: 0.8rem;
    color: var(--ink);
}
.cron-url-container {
    display: flex;
    gap: 0.6rem;
    margin: 1.2rem 0;
}
.cron-url-input {
    flex: 1;
    font-family: 'SFMono-Regular', Consolas, monospace;
    background: var(--sand);
    border: 1.5px solid var(--line);
    border-radius: 3px;
    padding: 0.65rem 0.95rem;
    font-size: 0.825rem;
    color: var(--ink-2);
    outline: none;
    transition: border-color var(--trans);
}
.cron-url-input:focus {
    border-color: var(--accent);
}
.cron-instructions {
    font-size: 0.85rem;
    color: var(--ink-2);
    margin-top: 1rem;
    line-height: 1.6;
}
.cron-instructions ul {
    margin-left: 1.5rem;
    margin-top: 0.5rem;
}
.cron-instructions li {
    margin-bottom: 0.35rem;
}

.due-badge {
    animation: subtle-pulse 2s infinite alternate;
    white-space: nowrap;
}
@keyframes subtle-pulse {
    0% { transform: scale(1); }
    100% { transform: scale(1.03); }
}

</style>

<div class="admin-topbar">
    <h1>Task Scheduler</h1>
    <div class="actions">
        <form action="/admin/scheduler/run-due" method="POST" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <button type="submit" class="btn btn-outline" title="Trigger all tasks that are currently due">
                🔄 Run All Due
            </button>
        </form>
    </div>
</div>

<div class="content">

    <!-- Global Control Header -->
    <div class="scheduler-header-card">
        <div>
            <h2>Scheduler Status</h2>
            <div class="scheduler-status">
                <?php if ($is_paused): ?>
                    <span class="status-dot status-paused-dot"></span>
                    <span style="color: #e74c3c;">PAUSED</span>
                    <span style="opacity: 0.6; font-size: 0.85rem; font-weight: normal; margin-left: 0.5rem;">Tasks will not run automatically.</span>
                <?php else: ?>
                    <span class="status-dot status-active-dot"></span>
                    <span style="color: #2ecc71;">ACTIVE</span>
                    <span style="opacity: 0.8; font-size: 0.85rem; font-weight: normal; margin-left: 0.5rem;">Listening for ticks and due execution.</span>
                <?php endif; ?>
            </div>
        </div>
        <div>
            <form action="/admin/scheduler/toggle" method="POST">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <button type="submit" class="btn <?= $is_paused ? 'btn-primary' : 'btn-danger' ?>">
                    <?= $is_paused ? '▶ Resume Scheduler' : '⏸ Pause Scheduler' ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Terminal Console Output -->
    <?php if (!empty($console_output)): ?>
        <div class="scheduler-console">
            <pre><?= h($console_output) ?></pre>
        </div>
    <?php endif; ?>

    <!-- Scheduled Tasks List -->
    <div style="margin-bottom: 2.5rem;">
        <h3 style="font-family: var(--font-display); font-size: 1.4rem; margin-bottom: 1rem; color: var(--ink);">Discovered CLI Tasks</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Task / Command</th>
                    <th>Description</th>
                    <th>Schedule</th>
                    <th>Last Executed</th>
                    <th>Next Allowed Run</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tasks)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem; color: var(--ink-2);">No active scheduled tasks found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td style="font-weight: 600; color: var(--ink); font-family: monospace; font-size: 0.9rem;">
                                <?= h($task['name']) ?>
                            </td>
                            <td><?= h($task['description']) ?></td>
                            <td><code style="background: var(--sand); padding: 0.15rem 0.35rem; border-radius: 2px; font-size: 0.8rem; font-family: monospace;"><?= h($task['frequency']) ?></code></td>
                            <td>
                                <span title="<?= h($task['last_run_at'] ?? 'Never') ?>">
                                    <?= h($task['last_run_relative']) ?>
                                </span>
                            </td>
                            <td>
                                <span title="<?= h($task['next_due_at']) ?>">
                                    <?= h($task['next_due_relative']) ?>
                                </span>
                            </td>
                            <td style="vertical-align: middle;">
                                <?php if ($task['is_due']): ?>
                                    <span class="badge badge-success due-badge">Due Now</span>
                                <?php else: ?>
                                    <span class="badge badge-neutral">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td style="vertical-align: middle;">
                                <form action="/admin/scheduler/run-task" method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                    <input type="hidden" name="task" value="<?= h($task['name']) ?>">
                                    <button type="submit" class="btn btn-sm btn-primary" <?= !$task['is_due'] ? 'disabled title="Enforced restriction: Task is not due yet." style="opacity: 0.4; cursor: not-allowed;"' : '' ?>>
                                        ⚡ Run Task
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Web Cron / Public Hook Setup Card -->
    <div class="cron-setup-card">
        <h3>🔒 No Crontab? Use Web Cron Integration</h3>
        <p style="font-size: 0.9rem; color: var(--ink-2); line-height: 1.5;">
            If your hosting plan lacks support for standard Unix crontabs, you can trigger your scheduled tasks automatically using external HTTP request pingers (like <a href="https://cron-job.org" target="_blank" style="color: var(--accent); font-weight: 500; text-decoration: underline;">Cron-Job.org</a> or <a href="https://uptimerobot.com" target="_blank" style="color: var(--accent); font-weight: 500; text-decoration: underline;">UptimeRobot</a>).
            Simply set up an external service to ping the secure URL below every minute.
        </p>
        
        <div class="cron-url-container">
            <input type="text" id="cron-url" class="cron-url-input" readonly value="<?= h(BASE_URL . '/api/cron?token=' . $cron_token) ?>">
            <button onclick="copyCronUrl()" id="copy-btn" class="btn btn-primary" style="white-space: nowrap;">
                📋 Copy Link
            </button>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; border-top: 1px solid var(--line); padding-top: 1.2rem;">
            <div class="cron-instructions">
                <strong>Configuring External Services:</strong>
                <ul>
                    <li>Set HTTP Request method to <code>GET</code>.</li>
                    <li>Configure the trigger interval to <code>Every 1 Minute</code>.</li>
                    <li>Ensure it matches the exact secure query token shown above.</li>
                </ul>
            </div>
            <div>
                <form action="/admin/scheduler/regen-token" method="POST" onsubmit="return confirm('Are you sure you want to regenerate the token? Any active external web cron pingers will fail until updated.');">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <button type="submit" class="btn btn-sm btn-danger">
                        ⚠️ Regenerate Token
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

<script>
function copyCronUrl() {
    const input = document.getElementById('cron-url');
    input.select();
    input.setSelectionRange(0, 99999);
    
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(input.value).then(() => {
            showCopiedState();
        }).catch(() => {
            fallbackCopy();
        });
    } else {
        fallbackCopy();
    }

    function fallbackCopy() {
        try {
            const success = document.execCommand('copy');
            if (success) {
                showCopiedState();
            } else {
                alert('Copy failed. Please manually copy the link.');
            }
        } catch (err) {
            alert('Copy failed. Please manually copy the link.');
        }
    }

    function showCopiedState() {
        const btn = document.getElementById('copy-btn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '📋 Copied!';
        btn.style.background = '#27ae60';
        btn.style.borderColor = '#27ae60';
        btn.style.color = '#ffffff';
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.style.background = '';
            btn.style.borderColor = '';
            btn.style.color = '';
        }, 2500);
    }
}
</script>
