<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$user = require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['item_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $st = $pdo->prepare('SELECT * FROM items WHERE id = :id AND user_id = :u'); // ownership check
    $st->execute([':id' => $id, ':u' => $user['id']]);
    $item = $st->fetch();
    if (!$item) {
        flash('error', 'You can only manage your own reports.');
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM items WHERE id = :id AND user_id = :u')->execute([':id' => $id, ':u' => $user['id']]);
        delete_image($item['image']);
        flash('success', 'Report deleted.');
    } elseif ($action === 'resolve' && $item['status'] === 'open') {
        $pdo->prepare("UPDATE items SET status = 'resolved' WHERE id = :id AND user_id = :u")->execute([':id' => $id, ':u' => $user['id']]);
        flash('success', 'Report marked as resolved.');
    }
    redirect('my-reports.php');
}
$st = $pdo->prepare("SELECT i.*, (SELECT COUNT(*) FROM claims c WHERE c.item_id = i.id AND c.status = 'pending') AS pending_claims
                     FROM items i WHERE i.user_id = :u ORDER BY i.created_at DESC");
$st->execute([':u' => $user['id']]);
$rows = $st->fetchAll();

$pageTitle = 'My reports';
require __DIR__ . '/includes/header.php';
?>
<div class="section-head"><h1>My reports</h1>
  <span><a class="btn btn-primary" href="<?= url('report-item.php?type=lost') ?>">Report lost</a>
  <a class="btn btn-light" href="<?= url('report-item.php?type=found') ?>">Report found</a></span></div>
<?php if (!$rows): ?>
  <p class="empty">You have not reported anything yet.</p>
<?php else: ?>
<div class="table-wrap"><table>
  <thead><tr><th>Item</th><th>Type</th><th>Date</th><th>Status</th><th>Claims</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td data-label="Item"><a href="<?= url('item-details.php?id=' . (int)$r['id']) ?>"><?= e($r['title']) ?></a><br><small class="muted"><?= e($r['location']) ?></small></td>
      <td data-label="Type"><?= type_badge($r['type']) ?></td>
      <td data-label="Date"><?= e(fmt_date($r['item_date'])) ?></td>
      <td data-label="Status"><?= status_badge($r['status']) ?></td>
      <td data-label="Claims"><?= $r['type'] === 'found' ? (int)$r['pending_claims'] . ' pending' : '-' ?></td>
      <td data-label="Actions" class="row-actions">
        <?php if ($r['status'] === 'open'): ?>
          <a class="btn btn-sm btn-light" href="<?= url('edit-item.php?id=' . (int)$r['id']) ?>">Edit</a>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="item_id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="resolve">
            <button class="btn btn-sm btn-light" type="submit" data-confirm="Mark this report as resolved?">Mark resolved</button></form>
        <?php endif; ?>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="item_id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="delete">
          <button class="btn btn-sm btn-danger" type="submit" data-confirm="Delete this report permanently? Claims on it will be removed too.">Delete</button></form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
