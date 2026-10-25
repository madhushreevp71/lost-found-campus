<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['item_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $st = $pdo->prepare('SELECT id, image FROM items WHERE id = :id');
    $st->execute([':id' => $id]);
    $item = $st->fetch();
    $map = ['returned' => 'returned', 'resolved' => 'resolved', 'reopen' => 'open'];
    if (!$item) {
        flash('error', 'Report not found.');
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM items WHERE id = :id')->execute([':id' => $id]);
        delete_image($item['image']);
        flash('success', 'Report deleted.');
    } elseif (isset($map[$action])) {
        $pdo->prepare('UPDATE items SET status = :s WHERE id = :id')->execute([':s' => $map[$action], ':id' => $id]);
        flash('success', 'Report updated.');
    }
    redirect('admin/items.php?' . http_build_query(array_filter([
        'type' => $_POST['f_type'] ?? '', 'status' => $_POST['f_status'] ?? '', 'q' => $_POST['f_q'] ?? ''])));
}
$ft = in_array($_GET['type'] ?? '', ['lost', 'found'], true) ? $_GET['type'] : '';
$fs = isset(STATUS_LABELS[$_GET['status'] ?? '']) ? $_GET['status'] : '';
$fq = clean_line($_GET['q'] ?? '');
$where = ['1=1']; $p = [];
if ($ft) { $where[] = 'i.type = :t'; $p[':t'] = $ft; }
if ($fs) { $where[] = 'i.status = :s'; $p[':s'] = $fs; }
if ($fq !== '') { $where[] = '(i.title LIKE :q1 OR u.full_name LIKE :q2)'; $p[':q1'] = $p[':q2'] = '%' . like_escape($fq) . '%'; }
$st = $pdo->prepare('SELECT i.*, u.full_name FROM items i JOIN users u ON u.id = i.user_id WHERE ' . implode(' AND ', $where) . ' ORDER BY i.created_at DESC');
$st->execute($p);
$rows = $st->fetchAll();

$adminArea = true; $pageTitle = 'Manage reports';
require __DIR__ . '/../includes/header.php';
?>
<h1>Manage reports</h1>
<form class="filters" method="get">
  <div class="field"><label for="q">Item or reporter</label><input id="q" name="q" value="<?= e($fq) ?>"></div>
  <div class="field"><label for="type">Type</label><select id="type" name="type"><option value="">All</option>
    <option value="lost" <?= $ft === 'lost' ? 'selected' : '' ?>>Lost</option><option value="found" <?= $ft === 'found' ? 'selected' : '' ?>>Found</option></select></div>
  <div class="field"><label for="status">Status</label><select id="status" name="status"><option value="">All</option>
    <?php foreach (STATUS_LABELS as $k => $v): ?><option value="<?= e($k) ?>" <?= $fs === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
  <div class="filter-actions"><button class="btn btn-primary" type="submit">Filter</button><a class="btn btn-light" href="<?= url('admin/items.php') ?>">Clear</a></div>
</form>
<?php if (!$rows): ?><p class="empty">No reports match.</p><?php else: ?>
<div class="table-wrap"><table>
  <thead><tr><th>Item</th><th>Type</th><th>Reporter</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td data-label="Item"><a href="<?= url('item-details.php?id=' . (int)$r['id']) ?>"><?= e($r['title']) ?></a><br><small class="muted"><?= e($r['location']) ?></small></td>
      <td data-label="Type"><?= type_badge($r['type']) ?></td>
      <td data-label="Reporter"><?= e($r['full_name']) ?></td>
      <td data-label="Date"><?= e(fmt_date($r['created_at'])) ?></td>
      <td data-label="Status"><?= status_badge($r['status']) ?></td>
      <td data-label="Actions" class="row-actions">
        <?php
        $acts = $r['status'] === 'open' ? ['returned' => 'Mark returned', 'resolved' => 'Mark resolved'] : ['reopen' => 'Reopen'];
        foreach ($acts as $a => $label): ?>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="item_id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="<?= e($a) ?>">
            <input type="hidden" name="f_type" value="<?= e($ft) ?>"><input type="hidden" name="f_status" value="<?= e($fs) ?>"><input type="hidden" name="f_q" value="<?= e($fq) ?>">
            <button class="btn btn-sm btn-light" type="submit"><?= e($label) ?></button></form>
        <?php endforeach; ?>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="item_id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="delete">
          <input type="hidden" name="f_type" value="<?= e($ft) ?>"><input type="hidden" name="f_status" value="<?= e($fs) ?>"><input type="hidden" name="f_q" value="<?= e($fq) ?>">
          <button class="btn btn-sm btn-danger" type="submit" data-confirm="Delete this report permanently? Its claims will be deleted too.">Delete</button></form>
      </td>
    </tr>
  <?php endforeach; ?></tbody></table></div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
