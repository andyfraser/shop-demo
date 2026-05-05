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

  <div class="card card-lg">
    <form action="<?= $is_new ? '/admin/attributes/new' : '/admin/attributes/edit' ?>" method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= $attribute_id ?>">

      <div class="form-group">
        <label for="name">Attribute Name *</label>
        <input type="text" id="name" name="name" value="<?= h($attribute['name'] ?? '') ?>" class="form-control" required autofocus>
        <p class="form-hint">e.g. Brand, Color, Material</p>
      </div>

      <div class="mt-4 mb-2">
        <h3 class="section-title border-bottom">
          Attribute Values
        </h3>
        <p class="text-sm text-muted mb-2">
          Existing values can be edited or removed. Add new values in the empty rows.
        </p>

        <table class="w-100" id="values-table" style="border-collapse: collapse;">
          <thead>
            <tr class="border-bottom" style="text-align: left;">
              <th style="width: 40px;"></th>
              <th class="text-xs text-muted font-bold" style="padding: 0.5rem 0;">Value Name</th>
              <th class="text-xs text-muted font-bold" style="padding: 0.5rem 0; width: 100px;">Remove?</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($values as $index => $v): ?>
              <tr draggable="true" class="value-row">
                <td class="drag-handle" title="Drag to reorder" style="padding: 0.5rem 0;">⋮⋮</td>
                <td style="padding: 0.5rem 0; padding-right: 1rem;">
                  <input type="hidden" name="values[<?= $index ?>][id]" value="<?= $v['id'] ?>">
                  <input type="text" name="values[<?= $index ?>][value]" value="<?= h($v['value']) ?>" class="form-control">
                </td>
                <td style="padding: 0.5rem 0;">
                  <label class="flex-center gap-1 text-sm" style="color: var(--accent); cursor: pointer; font-weight: 400;">
                    <input type="checkbox" name="values[<?= $index ?>][delete]" value="1"> Remove
                  </label>
                </td>
              </tr>
            <?php endforeach; ?>
            
            <!-- New values -->
            <?php for ($i = 0; $i < 3; $i++): ?>
              <?php $newIdx = count($values) + $i; ?>
              <tr draggable="true" class="value-row">
                <td class="drag-handle" title="Drag to reorder" style="padding: 0.5rem 0;">⋮⋮</td>
                <td style="padding: 0.5rem 0; padding-right: 1rem;">
                  <input type="text" name="values[<?= $newIdx ?>][value]" value="" class="form-control" placeholder="New value...">
                </td>
                <td style="padding: 0.5rem 0;"></td>
              </tr>
            <?php endfor; ?>
          </tbody>
        </table>
        
        <button type="button" class="btn btn-outline btn-sm mt-1" id="add-value-row">
          + Add More Rows
        </button>
      </div>

      <div class="mt-4 flex gap-2 border-top">
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
                <td class="drag-handle" title="Drag to reorder" style="padding: 0.5rem 0;">⋮⋮</td>
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
