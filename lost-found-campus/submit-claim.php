<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('search-items.php'); }
$user = require_login();
verify_csrf();

$itemId  = (int)($_POST['item_id'] ?? 0);
$message = clean_text($_POST['message'] ?? '');
$back = 'item-details.php?id=' . $itemId;

$st = db()->prepare('SELECT id, user_id, type, status FROM items WHERE id = :id');
$st->execute([':id' => $itemId]);
$item = $st->fetch();

$fail = function (string $msg) use ($back, $message) {
    $_SESSION['claim_error'] = $msg; $_SESSION['claim_old'] = $message;
    redirect($back);
};
if (!$item) { flash('error', 'That item no longer exists.'); redirect('search-items.php'); }
if ((int)$item['user_id'] === (int)$user['id']) { $fail('You cannot claim an item you reported yourself.'); }
if ($item['type'] !== 'found') { $fail('Only found items can be claimed.'); }
if ($item['status'] !== 'open') { $fail('This item is no longer open for claims.'); }
$len = mb_strlen($message);
if ($len < 20 || $len > 1000) { $fail('Explain your claim in 20 to 1000 characters.'); }

$dup = db()->prepare('SELECT id FROM claims WHERE item_id = :i AND claimant_id = :u');
$dup->execute([':i' => $itemId, ':u' => $user['id']]);
if ($dup->fetch()) { $fail('You have already claimed this item.'); }

db()->prepare("INSERT INTO claims (item_id, claimant_id, message, status) VALUES (:i, :u, :m, 'pending')")
    ->execute([':i' => $itemId, ':u' => $user['id'], ':m' => $message]);
flash('success', 'Claim submitted. An admin will review it soon.');
redirect('my-claims.php');
