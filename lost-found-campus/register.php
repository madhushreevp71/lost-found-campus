<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
if (current_user()) { redirect('dashboard.php'); }

$errors = []; $old = ['full_name' => '', 'email' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $old['full_name'] = clean_line($_POST['full_name'] ?? '');
    $old['email']     = strtolower(trim((string)($_POST['email'] ?? '')));
    $pass = (string)($_POST['password'] ?? '');
    $conf = (string)($_POST['confirm_password'] ?? '');

    if (mb_strlen($old['full_name']) < 2 || mb_strlen($old['full_name']) > 100) { $errors['full_name'] = 'Enter your full name (2 to 100 characters).'; }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 150) { $errors['email'] = 'Enter a valid email address.'; }
    if (strlen($pass) < 8 || !preg_match('/[A-Za-z]/', $pass) || !preg_match('/\d/', $pass)) { $errors['password'] = 'Use at least 8 characters with a letter and a number.'; }
    if ($pass !== $conf) { $errors['confirm_password'] = 'Passwords do not match.'; }

    if (!$errors) {
        $st = db()->prepare('SELECT id FROM users WHERE email = :e');
        $st->execute([':e' => $old['email']]);
        if ($st->fetch()) {
            $errors['email'] = 'An account with this email already exists. Try logging in.';
        } else {
            db()->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (:n, :e, :h, 'student')")
                ->execute([':n' => $old['full_name'], ':e' => $old['email'], ':h' => password_hash($pass, PASSWORD_DEFAULT)]);
            flash('success', 'Account created. You can log in now.');
            redirect('login.php');
        }
    }
}
$pageTitle = 'Create account';
require __DIR__ . '/includes/header.php';
?>
<div class="form-card">
  <h1>Create your account</h1>
  <form method="post" novalidate data-validate>
    <?= csrf_field() ?>
    <div class="field"><label for="full_name">Full name</label>
      <input id="full_name" name="full_name" required maxlength="100" value="<?= e($old['full_name']) ?>"><?= field_error($errors, 'full_name') ?></div>
    <div class="field"><label for="email">College email</label>
      <input id="email" type="email" name="email" required maxlength="150" value="<?= e($old['email']) ?>"><?= field_error($errors, 'email') ?></div>
    <div class="field"><label for="password">Password</label>
      <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password">
      <small class="hint">At least 8 characters, with a letter and a number.</small><?= field_error($errors, 'password') ?></div>
    <div class="field"><label for="confirm_password">Confirm password</label>
      <input id="confirm_password" type="password" name="confirm_password" required autocomplete="new-password"><?= field_error($errors, 'confirm_password') ?></div>
    <button class="btn btn-primary btn-block" type="submit">Create account</button>
  </form>
  <p class="muted center">Already registered? <a href="<?= url('login.php') ?>">Log in</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
