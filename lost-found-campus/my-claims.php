<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$user = require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $cid = (int)($_POST['claim_id'] ?? 0);
    $del = $pdo->prepare("DELETE FROM claims WHERE id = :id AND claimant_id = :u AND status = 'pending'");
    $del->execute([':id' => $cid, ':u' => $user['id']]);
    flash($del->rowCount() ? 'success' : 'error', $del->rowCount() ? 'Claim withdrawn.' : 'Only your own pending claims can be withdrawn.');
    redirect('my-claims.php');
}
$st = $pdo->prepare('SELECT c.*, i.title, i.location, i.status AS item_status FROM claims c JOIN items i ON i.id = c.item_id
                     WHERE c.claimant_id = :u ORDER BY c.created_at DESC');
$st->execute([':u' => $user['id']]);
$rows = $st->fetchAll();

$pageTitle = 'My claims';
require __DIR__ . '/includes/header.php';
?>
<h1>My claims</h1>
<?php if (!$rows): ?>
  <p class="empty">You have not claimed anything. Browse <a href="<?= url('search-items.php?type=found&status=open') ?>">found items</a> to find yours.</p>
<?php else: ?>
<div class="table-wrap"><table>
  <thead><tr><th>Item</th><th>Your explanation</th><th>Submitted</th><th>Status</th><th>Admin note</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td data-label="Item"><a href="<?= url('item-details.php?id=' . (int)$r['item_id']) ?>"><?= e($r['title']) ?></a><br><small class="muted"><?= e($r['location']) ?></small></td>
      <td data-label="Your explanation" class="prewrap"><?= e($r['message']) ?></td>
      <td data-label="Submitted"><?= e(fmt_date($r['created_at'])) ?></td>
      <td data-label="Status"><?= claim_badge($r['status']) ?></td>
      <td data-label="Admin note"><?= $r['admin_note'] ? e($r['admin_note']) : '-' ?></td>
      <td>
        <?php if ($r['status'] === 'pending'): ?>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="claim_id" value="<?= (int)$r['id'] ?>">
          <button class="btn btn-sm btn-light" type="submit" data-confirm="Withdraw this claim?">Withdraw</button></form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
