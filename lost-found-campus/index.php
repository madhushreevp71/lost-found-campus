<?php
declare(strict_types=1);
$pageTitle = 'Home';
require __DIR__ . '/includes/header.php';
$pdo = db();
$stats = $pdo->query("SELECT SUM(type='lost' AND status='open') AS lost_open, SUM(type='found' AND status='open') AS found_open,
                      SUM(status<>'open') AS resolved FROM items")->fetch();
$recent = $pdo->query("SELECT * FROM items ORDER BY created_at DESC LIMIT 8")->fetchAll();
?>
<section class="hero">
  <h1>Lost something on campus? Start here.</h1>
  <p>Search items other students have found, report what you lost, and claim what is yours.</p>
  <form class="hero-search" action="<?= url('search-items.php') ?>" method="get">
    <label class="sr-only" for="hq">Search items</label>
    <input id="hq" type="search" name="q" placeholder="Try &quot;black bag&quot;, &quot;calculator&quot; or &quot;keys&quot;">
    <button class="btn btn-primary" type="submit">Search</button>
  </form>
  <div class="hero-actions">
    <a class="btn btn-light" href="<?= url('report-item.php?type=lost') ?>">Report lost item</a>
    <a class="btn btn-light" href="<?= url('report-item.php?type=found') ?>">Report found item</a>
  </div>
</section>

<div class="stats">
  <div class="stat"><strong><?= (int)$stats['lost_open'] ?></strong><span>Lost items still missing</span></div>
  <div class="stat"><strong><?= (int)$stats['found_open'] ?></strong><span>Found items waiting for owners</span></div>
  <div class="stat"><strong><?= (int)$stats['resolved'] ?></strong><span>Items returned or resolved</span></div>
</div>

<div class="section-head"><h2>Recently reported</h2><a href="<?= url('search-items.php') ?>">See all items</a></div>
<?php if ($recent): ?>
  <div class="grid"><?php foreach ($recent as $it) { echo item_card($it); } ?></div>
<?php else: ?>
  <p class="empty">No reports yet. Be the first to <a href="<?= url('report-item.php?type=lost') ?>">report an item</a>.</p>
<?php endif; ?>

<section class="panel steps">
  <h2>How it works</h2>
  <ol>
    <li><strong>Report.</strong> Describe what you lost or found, with a photo if you have one.</li>
    <li><strong>Match.</strong> The portal suggests found items that resemble your lost report.</li>
    <li><strong>Claim.</strong> Explain why an item is yours. An admin reviews the claim and arranges the return.</li>
  </ol>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
