<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
if (is_admin()) { redirect('admin/dashboard.php'); }

$error = ''; $email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $pass  = (string)($_POST['password'] ?? '');
    if ($email === '' || $pass === '') {
        $error = 'Enter your admin email and password.';
    } else {
        $error = attempt_login($email, $pass, true) ?? '';
        if ($error === '') { redirect('admin/dashboard.php'); }
    }
}
$adminArea = true; $pageTitle = 'Admin login';
require __DIR__ . '/../includes/header.php';
?>
<div class="form-card">
  <h1>Admin login</h1>
  <?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
  <form method="post" novalidate data-validate>
    <?= csrf_field() ?>
    <div class="field"><label for="email">Admin email</label><input id="email" type="email" name="email" required value="<?= e($email) ?>"></div>
    <div class="field"><label for="password">Password</label><input id="password" type="password" name="password" required></div>
    <button class="btn btn-primary btn-block" type="submit">Log in</button>
  </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
