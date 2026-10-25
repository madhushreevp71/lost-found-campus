<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
if (current_user()) { redirect('dashboard.php'); }

$error = ''; $email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $pass  = (string)($_POST['password'] ?? '');
    if ($email === '' || $pass === '') {
        $error = 'Enter your email and password.';
    } else {
        $error = attempt_login($email, $pass) ?? '';
        if ($error === '') { redirect(is_admin() ? 'admin/dashboard.php' : 'dashboard.php'); }
    }
}
$pageTitle = 'Log in';
require __DIR__ . '/includes/header.php';
?>
<div class="form-card">
  <h1>Welcome back</h1>
  <?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
  <form method="post" novalidate data-validate>
    <?= csrf_field() ?>
    <div class="field"><label for="email">Email</label>
      <input id="email" type="email" name="email" required value="<?= e($email) ?>" autocomplete="username"></div>
    <div class="field"><label for="password">Password</label>
      <input id="password" type="password" name="password" required autocomplete="current-password"></div>
    <button class="btn btn-primary btn-block" type="submit">Log in</button>
  </form>
  <p class="muted center">New here? <a href="<?= url('register.php') ?>">Create an account</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
