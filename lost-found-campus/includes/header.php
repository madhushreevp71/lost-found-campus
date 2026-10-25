<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';

$user       = current_user();
$adminArea  = $adminArea ?? false;
$pageTitle  = $pageTitle ?? 'Lost & Found';
$script     = $_SERVER['SCRIPT_NAME'] ?? '';
$typeParam  = $_GET['type'] ?? '';

if ($adminArea && $user) {
    $links = [
        ['admin/dashboard.php', 'Dashboard'], ['admin/claims.php', 'Claims'],
        ['admin/items.php', 'Reports'], ['admin/users.php', 'Users'], ['index.php', 'Student site'],
    ];
} elseif ($user) {
    $links = [
        ['dashboard.php', 'Dashboard'], ['report-item.php?type=lost', 'Report lost item'],
        ['report-item.php?type=found', 'Report found item'], ['search-items.php', 'Search items'],
        ['my-reports.php', 'My reports'], ['my-claims.php', 'My claims'],
    ];
    if ($user['role'] === 'admin') { $links[] = ['admin/dashboard.php', 'Admin panel']; }
} else {
    $links = [['index.php', 'Home'], ['search-items.php', 'Search items'], ['login.php', 'Log in'],
              ['register.php', 'Create account'], ['admin/login.php', 'Admin login']];
}
function nav_active(string $link, string $script, string $typeParam): bool
{
    $path = explode('?', $link)[0];
    if (substr($script, -strlen($path)) !== $path) { return false; }
    if (strpos($link, 'type=') !== false) { return $link === $path . '?type=' . $typeParam; }
    return true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | Campus Lost &amp; Found</title>
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<header class="topbar">
  <button class="menu-btn" type="button" aria-label="Toggle menu" aria-expanded="false" aria-controls="sidebar">&#9776;</button>
  <span class="topbar-title">Campus Lost &amp; Found</span>
</header>
<div class="shell">
  <aside class="sidebar" id="sidebar">
    <a class="brand" href="<?= url($adminArea ? 'admin/dashboard.php' : 'index.php') ?>">
      <span class="brand-mark" aria-hidden="true">L&amp;F</span>
      <span>Campus<br>Lost &amp; Found<?= $adminArea ? ' <small>Admin</small>' : '' ?></span>
    </a>
    <nav aria-label="Main">
      <?php foreach ($links as [$href, $label]): ?>
        <a href="<?= url($href) ?>" class="<?= nav_active($href, $script, $typeParam) ? 'active' : '' ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <?php if ($user): ?>
      <div class="side-user">
        <div class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($user['full_name'], 0, 1))) ?></div>
        <div><strong><?= e($user['full_name']) ?></strong><br><small><?= e($user['role']) ?></small></div>
      </div>
      <form method="post" action="<?= url('logout.php') ?>">
        <?= csrf_field() ?>
        <button class="btn btn-light btn-block" type="submit">Log out</button>
      </form>
    <?php endif; ?>
  </aside>
  <main id="main" class="content">
    <?php foreach (get_flash() as $f): ?>
      <div class="alert alert-<?= e($f['type']) ?>" role="alert"><?= e($f['msg']) ?></div>
    <?php endforeach; ?>
