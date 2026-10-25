<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$user = require_login();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$st = db()->prepare('SELECT * FROM items WHERE id = :id');
$st->execute([':id' => $id]);
$item = $st->fetch();
if (!$item) { flash('error', 'That report was not found.'); redirect('my-reports.php'); }
if ((int)$item['user_id'] !== (int)$user['id']) { flash('error', 'You can only edit your own reports.'); redirect('my-reports.php'); }
if ($item['status'] !== 'open') { flash('error', 'Closed reports cannot be edited.'); redirect('my-reports.php'); }

$type = $item['type'];
$d = ['title' => $item['title'], 'category' => $item['category'], 'location' => $item['location'],
      'item_date' => $item['item_date'], 'description' => $item['description']];
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
        $newImage = $image ?? $item['image'];
        db()->prepare('UPDATE items SET title=:ti, category=:c, location=:l, item_date=:d, description=:de, image=:i
                       WHERE id=:id AND user_id=:u')
            ->execute([':ti' => $d['title'], ':c' => $d['category'], ':l' => $d['location'], ':d' => $d['item_date'],
                       ':de' => $d['description'], ':i' => $newImage, ':id' => $id, ':u' => $user['id']]);
        if ($image && $item['image']) { delete_image($item['image']); }
        flash('success', 'Report updated.');
        redirect('item-details.php?id=' . $id);
    }
}
$isEdit = true; $currentImage = $item['image']; $submitLabel = 'Save changes';
$pageTitle = 'Edit report';
require __DIR__ . '/includes/header.php';
?>
<h1>Edit <?= e($type) ?> report</h1>
<?php require __DIR__ . '/includes/item-form.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
