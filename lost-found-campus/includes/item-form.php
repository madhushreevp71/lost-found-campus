<?php
/* Shared by report-item.php and edit-item.php.
 * Expects: $type, $d (field values), $errors, $isEdit, $currentImage, $submitLabel */
?>
<form method="post" enctype="multipart/form-data" novalidate data-validate class="form-card wide">
  <?= csrf_field() ?>
  <input type="hidden" name="type" value="<?= e($type) ?>">
  <div class="field"><label for="title">Item name</label>
    <input id="title" name="title" required minlength="3" maxlength="120" value="<?= e($d['title']) ?>" placeholder="e.g. Black laptop bag">
    <?= field_error($errors, 'title') ?></div>
  <div class="row">
    <div class="field"><label for="category">Category</label>
      <select id="category" name="category" required>
        <option value="">Choose a category</option>
        <?php foreach (CATEGORIES as $c): ?><option value="<?= e($c) ?>" <?= $d['category'] === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
      </select><?= field_error($errors, 'category') ?></div>
    <div class="field"><label for="item_date"><?= $type === 'lost' ? 'Date lost' : 'Date found' ?></label>
      <input id="item_date" type="date" name="item_date" required max="<?= date('Y-m-d') ?>" value="<?= e($d['item_date']) ?>">
      <?= field_error($errors, 'item_date') ?></div>
  </div>
  <div class="field"><label for="location"><?= $type === 'lost' ? 'Where did you lose it?' : 'Where did you find it?' ?></label>
    <input id="location" name="location" required minlength="3" maxlength="150" value="<?= e($d['location']) ?>" placeholder="e.g. Central Library, 2nd floor">
    <?= field_error($errors, 'location') ?></div>
  <div class="field"><label for="description">Description</label>
    <textarea id="description" name="description" rows="5" required minlength="10" maxlength="1000" data-counter="1000"
      placeholder="<?= $type === 'lost' ? 'Colour, brand, marks, what was inside.' : 'Describe it, but keep one detail back so the real owner can prove it.' ?>"><?= e($d['description']) ?></textarea>
    <small class="hint counter"></small><?= field_error($errors, 'description') ?></div>
  <div class="field"><label for="image"><?= $isEdit ? 'Replace photo (optional)' : 'Photo (optional)' ?></label>
    <?php if ($isEdit && $currentImage): ?><img class="thumb" src="<?= e(image_url($currentImage)) ?>" alt="Current photo"><?php endif; ?>
    <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp">
    <small class="hint">JPG, PNG or WEBP, up to 2 MB.</small><?= field_error($errors, 'image') ?></div>
  <button class="btn btn-primary" type="submit"><?= e($submitLabel) ?></button>
</form>
