<aside class="filters-sidebar">
  <div class="filter-group">
    <h4>Price Range</h4>
    <div class="price-inputs">
      <input type="number" name="price_min" placeholder="Min" value="<?= h($active_filters['price_min'] ?? '') ?>" step="0.01">
      <span>-</span>
      <input type="number" name="price_max" placeholder="Max" value="<?= h($active_filters['price_max'] ?? '') ?>" step="0.01">
    </div>
  </div>

  <?php foreach ($available_filters['attributes'] as $attr): ?>
    <div class="filter-group">
      <h4><?= h($attr['name']) ?></h4>
      <div class="filter-options">
        <?php 
          $total_vals = count($attr['values']);
          $initial_vals = array_slice($attr['values'], 0, 4);
          $extra_vals = array_slice($attr['values'], 4);
        ?>

        <?php foreach ($initial_vals as $val): ?>
          <label class="filter-label">
            <input type="checkbox" name="attr[]" value="<?= $val['id'] ?>" 
              <?= in_array($val['id'], $active_filters['attributes']) ? 'checked' : '' ?>>
            <span class="filter-name"><?= h($val['name']) ?></span>
            <span class="count"><?= $val['count'] ?></span>
          </label>
        <?php endforeach; ?>

        <?php if ($extra_vals): ?>
          <div class="filter-extra" style="display: none;">
            <?php foreach ($extra_vals as $val): ?>
              <label class="filter-label">
                <input type="checkbox" name="attr[]" value="<?= $val['id'] ?>" 
                  <?= in_array($val['id'], $active_filters['attributes']) ? 'checked' : '' ?>>
                <span class="filter-name"><?= h($val['name']) ?></span>
                <span class="count"><?= $val['count'] ?></span>
              </label>
            <?php endforeach; ?>
          </div>
          <button type="button" class="btn-toggle-filters" onclick="const extra = this.previousElementSibling; extra.style.display = extra.style.display === 'none' ? 'block' : 'none'; this.textContent = extra.style.display === 'none' ? 'Show more' : 'Show less';">Show more</button>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
  
  <div class="filter-actions">
    <button type="submit" class="btn btn-primary btn-block btn-sm">Apply Filters</button>
    <a href="<?= parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?><?= !empty($query) ? '?q=' . urlencode($query) : '' ?>" class="btn btn-outline btn-block btn-sm">Clear All</a>
  </div>
</aside>
