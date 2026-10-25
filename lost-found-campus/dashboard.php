<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$user = require_login();
$pdo = db();

$st = $pdo->prepare("SELECT SUM(type='lost') AS lost, SUM(type='found') AS found, SUM(status<>'open') AS done FROM items WHERE user_id = :u");
$st->execute([':u' => $user['id']]);
$mine = $st->fetch();
$st = $pdo->prepare("SELECT COUNT(*) FROM claims WHERE claimant_id = :u AND status = 'pending'");
$st->execute([':u' => $user['id']]);
$pendingClaims = (int)$st->fetchColumn();

$st = $pdo->prepare("SELECT * FROM items WHERE user_id = :u AND type = 'lost' AND status = 'open' ORDER BY created_at DESC LIMIT 5");
$st->execute([':u' => $user['id']]);
$matchBlocks = [];
foreach ($st->fetchAll() as $lost) {
    $m = find_matches($pdo, $lost, 3);
    if ($m) { $matchBlocks[] = [$lost, $m]; }
}
$recent = $pdo->query("SELECT * FROM items ORDER BY created_at DESC LIMIT 4")->fetchAll();

$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>
<h1>Hello, <?= e(explode(' ', $user['full_name'])[0]) ?></h1>
<div class="stats">
  <div class="stat"><strong><?= (int)$mine['lost'] ?></strong><span>Lost reports</span></div>
  <div class="stat"><strong><?= (int)$mine['found'] ?></strong><span>Found reports</span></div>
  <div class="stat"><strong><?= $pendingClaims ?></strong><span>Claims awaiting review</span></div>
  <div class="stat"><strong><?= (int)$mine['done'] ?></strong><span>Reports closed</span></div>
</div>

<?php if ($matchBlocks): foreach ($matchBlocks as [$lost, $m]):
    echo match_panel($m, 'Possible matches for your lost "' . $lost['title'] . '"');
endforeach; else: ?>
  <p class="panel muted">No possible matches right now. When someone reports a found item that resembles one of your lost items, it will appear here.</p>
<?php endif; ?>

<div class="section-head"><h2>Latest reports</h2><a href="<?= url('search-items.php') ?>">Search all items</a></div>
<div class="grid"><?php foreach ($recent as $it) { echo item_card($it); } ?></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
