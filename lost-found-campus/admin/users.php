<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$rows = db()->query("SELECT u.id, u.full_name, u.email, u.role, u.created_at,
    (SELECT COUNT(*) FROM items i WHERE i.user_id = u.id) AS reports,
    (SELECT COUNT(*) FROM claims c WHERE c.claimant_id = u.id) AS claims
    FROM users u ORDER BY u.created_at DESC")->fetchAll();

$adminArea = true; $pageTitle = 'Users';
require __DIR__ . '/../includes/header.php';
?>
<h1>Registered users</h1>
<p class="muted"><?= count($rows) ?> account<?= count($rows) === 1 ? '' : 's' ?></p>
<div class="table-wrap"><table>
  <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Reports</th><th>Claims</th><th>Joined</th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?>
    <tr><td data-label="Name"><?= e($r['full_name']) ?></td><td data-label="Email"><?= e($r['email']) ?></td>
      <td data-label="Role"><span class="badge badge-<?= $r['role'] === 'admin' ? 'found' : 'open' ?>"><?= e(ucfirst($r['role'])) ?></span></td>
      <td data-label="Reports"><?= (int)$r['reports'] ?></td><td data-label="Claims"><?= (int)$r['claims'] ?></td>
      <td data-label="Joined"><?= e(fmt_date($r['created_at'])) ?></td></tr>
  <?php endforeach; ?></tbody></table></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
