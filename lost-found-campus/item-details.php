<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$user = current_user();
$pdo = db();

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT i.*, u.full_name AS reporter FROM items i JOIN users u ON u.id = i.user_id WHERE i.id = :id');
$st->execute([':id' => $id]);
$item = $st->fetch();
if (!$item) {
    http_response_code(404);
    $pageTitle = 'Not found';
    require __DIR__ . '/includes/header.php';
    echo '<p class="empty">This report does not exist or was removed. <a href="' . url('search-items.php') . '">Search items</a></p>';
    require __DIR__ . '/includes/footer.php';
    exit;
}
$isOwner = $user && (int)$user['id'] === (int)$item['user_id'];
$myClaim = null; $pendingCount = 0;
if ($user && !$isOwner) {
    $c = $pdo->prepare('SELECT * FROM claims WHERE item_id = :i AND claimant_id = :u');
    $c->execute([':i' => $id, ':u' => $user['id']]);
    $myClaim = $c->fetch() ?: null;
}
if ($isOwner && $item['type'] === 'found') {
    $c = $pdo->prepare("SELECT COUNT(*) FROM claims WHERE item_id = :i AND status = 'pending'");
    $c->execute([':i' => $id]);
    $pendingCount = (int)$c->fetchColumn();
}
$matches = ($isOwner && $item['status'] === 'open') ? find_matches($pdo, $item, 5) : [];
$claimErr = $_SESSION['claim_error'] ?? ''; $claimOld = $_SESSION['claim_old'] ?? '';
unset($_SESSION['claim_error'], $_SESSION['claim_old']);

$pageTitle = $item['title'];
require __DIR__ . '/includes/header.php';
?>
<?php if ($claimErr): ?><div class="alert alert-error" role="alert"><?= e($claimErr) ?></div><?php endif; ?>
<p><a href="<?= url('search-items.php') ?>">&larr; Back to search</a></p>
<div class="detail">
  <img class="detail-img" src="<?= e(image_url($item['image'])) ?>" alt="<?= e($item['title']) ?>">
  <div>
    <div class="badges"><?= type_badge($item['type']) . status_badge($item['status']) ?></div>
    <h1><?= e($item['title']) ?></h1>
    <dl class="facts">
      <dt>Category</dt><dd><?= e($item['category']) ?></dd>
      <dt>Location</dt><dd><?= e($item['location']) ?></dd>
      <dt><?= $item['type'] === 'lost' ? 'Date lost' : 'Date found' ?></dt><dd><?= e(fmt_date($item['item_date'])) ?></dd>
      <dt>Reported on</dt><dd><?= e(fmt_date($item['created_at'])) ?></dd>
      <dt>Reported by</dt><dd><?= e($item['reporter']) ?></dd>
    </dl>
    <h2>Description</h2>
    <p class="prewrap"><?= e($item['description']) ?></p>

    <?php if ($isOwner): ?>
      <div class="actions">
        <?php if ($item['status'] === 'open'): ?><a class="btn btn-light" href="<?= url('edit-item.php?id=' . $id) ?>">Edit report</a><?php endif; ?>
        <a class="btn btn-light" href="<?= url('my-reports.php') ?>">Manage in My reports</a>
      </div>
      <?php if ($pendingCount): ?><p class="alert alert-info"><?= $pendingCount ?> claim<?= $pendingCount > 1 ? 's are' : ' is' ?> waiting for admin review.</p><?php endif; ?>
    <?php elseif ($item['type'] === 'found' && $item['status'] === 'open'): ?>
      <section class="panel">
        <h2>Is this yours?</h2>
        <?php if (!$user): ?>
          <p><a href="<?= url('login.php') ?>">Log in</a> or <a href="<?= url('register.php') ?>">create an account</a> to claim this item.</p>
        <?php elseif ($myClaim): ?>
          <p>You already submitted a claim: <?= claim_badge($myClaim['status']) ?>. <a href="<?= url('my-claims.php') ?>">Track it in My claims</a>.</p>
        <?php else: ?>
          <form method="post" action="<?= url('submit-claim.php') ?>" novalidate data-validate>
            <?= csrf_field() ?>
            <input type="hidden" name="item_id" value="<?= $id ?>">
            <div class="field"><label for="message">Why does this item belong to you?</label>
              <textarea id="message" name="message" rows="4" required minlength="20" maxlength="1000" data-counter="1000"
                placeholder="Describe marks, contents, or details only the owner would know."><?= e($claimOld) ?></textarea>
              <small class="hint counter"></small></div>
            <button class="btn btn-primary" type="submit">Submit claim</button>
          </form>
        <?php endif; ?>
      </section>
    <?php elseif ($item['type'] === 'lost' && $item['status'] === 'open'): ?>
      <p class="panel muted">Have you seen this item? <a href="<?= url('report-item.php?type=found') ?>">Report it as found</a> and the owner will see a match.</p>
    <?php endif; ?>
  </div>
</div>
<?= match_panel($matches, $item['type'] === 'lost' ? 'Possible matches among found items' : 'Lost reports that may belong to this item') ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
