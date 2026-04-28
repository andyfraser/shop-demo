<?php // templates/admin/attributes_form.php ?>

<div class="admin-topbar">
  <h1><?= $is_new ? 'Add Attribute' : 'Edit Attribute' ?></h1>
  <div class="actions">
    <a href="/admin/attributes" class="btn btn-outline">← Back</a>
  </div>
</div>

<div class="content">
  <?php if ($errors): ?>
    <div class="alert alert-error">
      <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="card" style="max-width:800px;">
    <form action="<?= $is_new ? '/admin/attributes/new' : '/admin/attributes/edit' ?>" method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= $attribute_id ?>">

      <div class="form-group">
        <label for="name">Attribute Name *</label>
        <input type="text" id="name" name="name" value="<?= h($attribute['name'] ?? '') ?>" class="form-control" required autofocus>
        <p style="font-size: 0.75rem; color: var(--ink-2); margin-top: 0.35rem;">e.g. Brand, Color, Material</p>
      </div>

      <div style="margin-top: 2rem; margin-bottom: 1rem;">
        <h3 style="font-family: var(--font-display); font-size: 1.2rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--line); padding-bottom: 0.5rem;">
          Attribute Values
        </h3>
        <p style="font-size: 0.85rem; color: var(--ink-2); margin-bottom: 1rem;">
          Existing values can be edited or removed. Add new values in the empty rows.
        </p>

        <table class="table" id="values-table" style="width: 100%; border-collapse: collapse;">
          <thead>
            <tr style="text-align: left; border-bottom: 1.5px solid var(--line);">
              <th style="width: 40px;"></th>
              <th style="padding: 0.5rem 0; font-size: 0.8rem; font-weight: 600; color: var(--ink-2);">Value Name</th>
              <th style="padding: 0.5rem 0; font-size: 0.8rem; font-weight: 600; color: var(--ink-2); width: 100px;">Remove?</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($values as $index => $v): ?>
              <tr draggable="true" class="value-row">
                <td class="drag-handle" title="Drag to reorder" style="cursor: grab; color: var(--ink-3); padding: 0.5rem 0;">⋮⋮</td>
                <td style="padding: 0.5rem 0; padding-right: 1rem;">
                  <input type="hidden" name="values[<?= $index ?>][id]" value="<?= $v['id'] ?>">
                  <input type="text" name="values[<?= $index ?>][value]" value="<?= h($v['value']) ?>" class="form-control">
                </td>
                <td style="padding: 0.5rem 0;">
                  <label style="color: var(--accent); cursor: pointer; font-size: 0.8rem; display: flex; align-items: center; gap: 0.25rem; font-weight: 400;">
                    <input type="checkbox" name="values[<?= $index ?>][delete]" value="1"> Remove
                  </label>
                </td>
              </tr>
            <?php endforeach; ?>
            
            <!-- New values -->
            <?php for ($i = 0; $i < 3; $i++): ?>
              <?php $newIdx = count($values) + $i; ?>
              <tr draggable="true" class="value-row">
                <td class="drag-handle" title="Drag to reorder" style="cursor: grab; color: var(--ink-3); padding: 0.5rem 0;">⋮⋮</td>
                <td style="padding: 0.5rem 0; padding-right: 1rem;">
                  <input type="text" name="values[<?= $newIdx ?>][value]" value="" class="form-control" placeholder="New value...">
                </td>
                <td style="padding: 0.5rem 0;"></td>
              </tr>
            <?php endfor; ?>
          </tbody>
        </table>
        
        <button type="button" class="btn btn-outline btn-sm" id="add-value-row" style="margin-top: 0.75rem;">
          + Add More Rows
        </button>
      </div>

      <div style="margin-top: 2rem; display: flex; gap: 0.75rem; border-top: 1px solid var(--line); padding-top: 1.5rem;">
        <button type="submit" class="btn btn-primary">Save Attribute</button>
        <a href="/admin/attributes" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('add-value-row');
    const tbody = document.querySelector('#values-table tbody');
    let index = <?= count($values) + 3 ?>;

    btn.addEventListener('click', function() {
        for (let i = 0; i < 3; i++) {
            const tr = document.createElement('tr');
            tr.className = 'value-row';
            tr.draggable = true;
            tr.innerHTML = `
                <td class="drag-handle" title="Drag to reorder" style="cursor: grab; color: var(--ink-3); padding: 0.5rem 0;">⋮⋮</td>
                <td style="padding: 0.5rem 0; padding-right: 1rem;">
                    <input type="text" name="values[${index}][value]" class="form-control" placeholder="New value...">
                </td>
                <td style="padding: 0.5rem 0;"></td>
            `;
            tbody.appendChild(tr);
            addDragEvents(tr);
            index++;
        }
    });

    let dragSrcEl = null;

    function addDragEvents(row) {
        row.addEventListener('dragstart', function(e) {
            dragSrcEl = this;
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/html', this.innerHTML);
            this.classList.add('dragging');
        });

        row.addEventListener('dragover', function(e) {
            if (e.preventDefault) e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            
            if (this !== dragSrcEl) {
                const rect = this.getBoundingClientRect();
                const midpoint = rect.top + rect.height / 2;
                if (e.clientY < midpoint) {
                    this.parentNode.insertBefore(dragSrcEl, this);
                } else {
                    this.parentNode.insertBefore(dragSrcEl, this.nextSibling);
                }
            }
            return false;
        });

        row.addEventListener('dragend', function() {
            const rows = tbody.querySelectorAll('tr');
            rows.forEach(r => {
                r.classList.remove('dragging');
            });
        });
    }

    document.querySelectorAll('.value-row').forEach(addDragEvents);
});
</script>
