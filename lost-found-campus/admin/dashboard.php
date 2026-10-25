<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$pdo = db();

$s = $pdo->query("SELECT SUM(type='lost') AS lost, SUM(type='found') AS found, SUM(status<>'open') AS resolved FROM items")->fetch();
$pending = (int)$pdo->query("SELECT COUNT(*) FROM claims WHERE status='pending'")->fetchColumn();
$users = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$claims = $pdo->query("SELECT c.id, c.message, c.created_at, i.id AS item_id, i.title, u.full_name
                       FROM claims c JOIN items i ON i.id=c.item_id JOIN users u ON u.id=c.claimant_id
                       WHERE c.status='pending' ORDER BY c.created_at ASC LIMIT 5")->fetchAll();

$adminArea = true; $pageTitle = 'Admin dashboard';
require __DIR__ . '/../includes/header.php';
?>
<h1>Admin dashboard</h1>
<div class="stats">
  <div class="stat"><strong><?= (int)$s['lost'] ?></strong><span>Lost reports</span></div>
  <div class="stat"><strong><?= (int)$s['found'] ?></strong><span>Found reports</span></div>
  <div class="stat"><strong><?= $pending ?></strong><span>Pending claims</span></div>
  <div class="stat"><strong><?= (int)$s['resolved'] ?></strong><span>Resolved items</span></div>
  <div class="stat"><strong><?= $users ?></strong><span>Registered students</span></div>
</div>
<div class="section-head"><h2>Claims waiting for review</h2><a href="<?= url('admin/claims.php?status=pending') ?>">Review all</a></div>
<?php if (!$claims): ?><p class="empty">No pending claims.</p><?php else: ?>
<div class="table-wrap"><table>
  <thead><tr><th>Item</th><th>Claimant</th><th>Explanation</th><th>Submitted</th></tr></thead><tbody>
  <?php foreach ($claims as $c): ?>
    <tr><td data-label="Item"><a href="<?= url('item-details.php?id=' . (int)$c['item_id']) ?>"><?= e($c['title']) ?></a></td>
      <td data-label="Claimant"><?= e($c['full_name']) ?></td>
      <td data-label="Explanation" class="prewrap"><?= e(mb_strimwidth($c['message'], 0, 120, '...')) ?></td>
      <td data-label="Submitted"><?= e(fmt_date($c['created_at'])) ?></td></tr>
  <?php endforeach; ?></tbody></table></div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
