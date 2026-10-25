<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$f = [
    'q'         => clean_line($_GET['q'] ?? ''),
    'type'      => $_GET['type'] ?? '',
    'category'  => $_GET['category'] ?? '',
    'location'  => clean_line($_GET['location'] ?? ''),
    'date_from' => $_GET['date_from'] ?? '',
    'date_to'   => $_GET['date_to'] ?? '',
    'status'    => $_GET['status'] ?? '',
];
$where = ['1=1']; $p = [];
if ($f['q'] !== '') {
    $where[] = '(i.title LIKE :q1 OR i.description LIKE :q2)';
    $p[':q1'] = $p[':q2'] = '%' . like_escape($f['q']) . '%';
}
if (in_array($f['type'], ['lost', 'found'], true)) { $where[] = 'i.type = :type'; $p[':type'] = $f['type']; } else { $f['type'] = ''; }
if (in_array($f['category'], CATEGORIES, true)) { $where[] = 'i.category = :cat'; $p[':cat'] = $f['category']; } else { $f['category'] = ''; }
if ($f['location'] !== '') { $where[] = 'i.location LIKE :loc'; $p[':loc'] = '%' . like_escape($f['location']) . '%'; }
if (valid_date((string)$f['date_from'])) { $where[] = 'i.item_date >= :df'; $p[':df'] = $f['date_from']; } else { $f['date_from'] = ''; }
if (valid_date((string)$f['date_to']))   { $where[] = 'i.item_date <= :dt'; $p[':dt'] = $f['date_to']; } else { $f['date_to'] = ''; }
if (isset(STATUS_LABELS[$f['status']])) { $where[] = 'i.status = :st'; $p[':st'] = $f['status']; } else { $f['status'] = ''; }
$w = implode(' AND ', $where);

$perPage = 12;
$cnt = db()->prepare("SELECT COUNT(*) FROM items i WHERE $w");
$cnt->execute($p);
$total = (int)$cnt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page = min(max(1, (int)($_GET['page'] ?? 1)), $pages);
$offset = ($page - 1) * $perPage;

$st = db()->prepare("SELECT i.* FROM items i WHERE $w ORDER BY i.created_at DESC LIMIT $perPage OFFSET $offset");
$st->execute($p);
$items = $st->fetchAll();

$qs = array_filter($f, fn($v) => $v !== '');
$pageTitle = 'Search items';
require __DIR__ . '/includes/header.php';
?>
<h1>Search items</h1>
<form class="filters" method="get" action="<?= url('search-items.php') ?>">
  <div class="field"><label for="q">Item name or keyword</label><input id="q" type="search" name="q" value="<?= e($f['q']) ?>"></div>
  <div class="field"><label for="type">Report type</label>
    <select id="type" name="type"><option value="">Lost and found</option>
      <option value="lost" <?= $f['type'] === 'lost' ? 'selected' : '' ?>>Lost</option>
      <option value="found" <?= $f['type'] === 'found' ? 'selected' : '' ?>>Found</option></select></div>
  <div class="field"><label for="category">Category</label>
    <select id="category" name="category"><option value="">All categories</option>
      <?php foreach (CATEGORIES as $c): ?><option value="<?= e($c) ?>" <?= $f['category'] === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
  <div class="field"><label for="location">Location</label><input id="location" name="location" value="<?= e($f['location']) ?>"></div>
  <div class="field"><label for="date_from">Date from</label><input id="date_from" type="date" name="date_from" value="<?= e($f['date_from']) ?>"></div>
  <div class="field"><label for="date_to">Date to</label><input id="date_to" type="date" name="date_to" value="<?= e($f['date_to']) ?>"></div>
  <div class="field"><label for="status">Status</label>
    <select id="status" name="status"><option value="">Any status</option>
      <?php foreach (STATUS_LABELS as $k => $v): ?><option value="<?= e($k) ?>" <?= $f['status'] === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
  <div class="filter-actions"><button class="btn btn-primary" type="submit">Apply filters</button>
    <a class="btn btn-light" href="<?= url('search-items.php') ?>">Clear</a></div>
</form>

<p class="muted"><?= $total ?> item<?= $total === 1 ? '' : 's' ?> found</p>
<?php if ($items): ?>
  <div class="grid"><?php foreach ($items as $it) { echo item_card($it); } ?></div>
  <?php if ($pages > 1): ?>
  <nav class="pager" aria-label="Pages">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
      <a class="<?= $i === $page ? 'active' : '' ?>" href="<?= url('search-items.php?' . http_build_query($qs + ['page' => $i])) ?>"><?= $i ?></a>
    <?php endfor; ?>
  </nav>
  <?php endif; ?>
<?php else: ?>
  <p class="empty">No items match these filters. Try fewer filters, or <a href="<?= url('report-item.php?type=lost') ?>">report your lost item</a>.</p>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
