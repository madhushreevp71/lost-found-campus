<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $cid = (int)($_POST['claim_id'] ?? 0);
    $decision = $_POST['decision'] ?? '';
    $note = mb_substr(clean_line($_POST['admin_note'] ?? ''), 0, 255);
    $st = $pdo->prepare('SELECT * FROM claims WHERE id = :id');
    $st->execute([':id' => $cid]);
    $claim = $st->fetch();

    if (!$claim || $claim['status'] !== 'pending' || !in_array($decision, ['approve', 'reject'], true)) {
        flash('error', 'That claim cannot be reviewed.');
    } elseif ((int)$claim['claimant_id'] === (int)$admin['id']) {
        flash('error', 'You cannot review your own claim. Ask another admin.');
    } else {
        $pdo->beginTransaction();
        try {
            if ($decision === 'approve') {
                $pdo->prepare("UPDATE claims SET status='approved', admin_note=:n, reviewed_at=NOW() WHERE id=:id")
                    ->execute([':n' => $note ?: null, ':id' => $cid]);
                $pdo->prepare("UPDATE claims SET status='rejected', admin_note='Another claim for this item was approved.', reviewed_at=NOW()
                               WHERE item_id=:i AND id<>:id AND status='pending'")->execute([':i' => $claim['item_id'], ':id' => $cid]);
                $pdo->prepare("UPDATE items SET status='returned' WHERE id=:i")->execute([':i' => $claim['item_id']]);
            } else {
                $pdo->prepare("UPDATE claims SET status='rejected', admin_note=:n, reviewed_at=NOW() WHERE id=:id")
                    ->execute([':n' => $note ?: null, ':id' => $cid]);
            }
            $pdo->commit();
            flash('success', $decision === 'approve' ? 'Claim approved and item marked as returned.' : 'Claim rejected.');
        } catch (Throwable $ex) {
            $pdo->rollBack();
            flash('error', 'The claim could not be updated. Try again.');
        }
    }
    redirect('admin/claims.php?status=' . urlencode((string)($_POST['filter'] ?? 'pending')));
}

$filter = $_GET['status'] ?? 'pending';
if (!in_array($filter, ['pending', 'approved', 'rejected', 'all'], true)) { $filter = 'pending'; }
$sql = "SELECT c.*, i.title, i.type, i.location, u.full_name, u.email FROM claims c
        JOIN items i ON i.id = c.item_id JOIN users u ON u.id = c.claimant_id";
$params = [];
if ($filter !== 'all') { $sql .= ' WHERE c.status = :s'; $params[':s'] = $filter; }
$st = $pdo->prepare($sql . ' ORDER BY c.created_at DESC');
$st->execute($params);
$rows = $st->fetchAll();

$adminArea = true; $pageTitle = 'Claims';
require __DIR__ . '/../includes/header.php';
?>
<h1>Claims</h1>
<nav class="tabs" aria-label="Claim status">
  <?php foreach (['pending', 'approved', 'rejected', 'all'] as $t): ?>
    <a class="<?= $filter === $t ? 'active' : '' ?>" href="<?= url('admin/claims.php?status=' . $t) ?>"><?= e(ucfirst($t)) ?></a>
  <?php endforeach; ?>
</nav>
<?php if (!$rows): ?><p class="empty">No <?= $filter === 'all' ? '' : e($filter) . ' ' ?>claims.</p><?php else: ?>
<div class="table-wrap"><table>
  <thead><tr><th>Item</th><th>Claimant</th><th>Explanation</th><th>Status</th><th>Decision</th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td data-label="Item"><a href="<?= url('item-details.php?id=' . (int)$r['item_id']) ?>"><?= e($r['title']) ?></a><br><small class="muted"><?= e($r['location']) ?></small></td>
      <td data-label="Claimant"><?= e($r['full_name']) ?><br><small class="muted"><?= e($r['email']) ?></small></td>
      <td data-label="Explanation" class="prewrap"><?= e($r['message']) ?><br><small class="muted"><?= e(fmt_date($r['created_at'])) ?></small></td>
      <td data-label="Status"><?= claim_badge($r['status']) ?><?= $r['admin_note'] ? '<br><small class="muted">' . e($r['admin_note']) . '</small>' : '' ?></td>
      <td data-label="Decision">
        <?php if ($r['status'] === 'pending'): ?>
        <form method="post" class="decision"><?= csrf_field() ?>
          <input type="hidden" name="claim_id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="filter" value="<?= e($filter) ?>">
          <label class="sr-only" for="note<?= (int)$r['id'] ?>">Note to student</label>
          <input id="note<?= (int)$r['id'] ?>" name="admin_note" maxlength="255" placeholder="Note to student (optional)">
          <div class="row-actions">
            <button class="btn btn-sm btn-primary" name="decision" value="approve" type="submit" data-confirm="Approve this claim? The item will be marked as returned.">Approve</button>
            <button class="btn btn-sm btn-danger" name="decision" value="reject" type="submit" data-confirm="Reject this claim?">Reject</button>
          </div>
        </form>
        <?php else: ?>Reviewed <?= e(fmt_date($r['reviewed_at'])) ?><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?></tbody></table></div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
