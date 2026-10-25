<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$user = require_login();

$type = $_POST['type'] ?? $_GET['type'] ?? 'lost';
if (!in_array($type, ['lost', 'found'], true)) { $type = 'lost'; }
$d = ['title' => '', 'category' => '', 'location' => '', 'item_date' => date('Y-m-d'), 'description' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    [$d, $errors] = validate_item_input($_POST);
    $image = null;
    if (!$errors) {
        [$image, $imgErr] = handle_image_upload($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
        if ($imgErr) { $errors['image'] = $imgErr; }
    }
    if (!$errors) {
        $st = db()->prepare('INSERT INTO items (user_id, type, title, category, location, item_date, description, image)
                             VALUES (:u, :t, :ti, :c, :l, :d, :de, :i)');
        $st->execute([':u' => $user['id'], ':t' => $type, ':ti' => $d['title'], ':c' => $d['category'],
                      ':l' => $d['location'], ':d' => $d['item_date'], ':de' => $d['description'], ':i' => $image]);
        $id = (int)db()->lastInsertId();
        flash('success', 'Your ' . $type . ' report has been posted.');
        redirect('item-details.php?id=' . $id);
    }
}
$isEdit = false; $currentImage = null;
$submitLabel = $type === 'lost' ? 'Post lost report' : 'Post found report';
$pageTitle = $type === 'lost' ? 'Report lost item' : 'Report found item';
require __DIR__ . '/includes/header.php';
?>
<h1><?= e($pageTitle) ?></h1>
<p class="muted"><?= $type === 'lost'
    ? 'After you post, the portal checks found reports and suggests possible matches.'
    : 'Thank you for helping. Keep the item safe, for example with the security desk, until it is claimed.' ?></p>
<?php require __DIR__ . '/includes/item-form.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
