<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

const CATEGORIES = ['Electronics', 'Books & Stationery', 'Bags', 'ID & Cards', 'Keys',
                    'Clothing & Accessories', 'Wallets & Money', 'Sports', 'Other'];
const STATUS_LABELS = ['open' => 'Open', 'returned' => 'Returned', 'resolved' => 'Resolved'];
const MAX_IMAGE_BYTES = 2097152; // 2 MB

/* ---------- Output helpers ---------- */
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }
function redirect(string $path): never { header('Location: ' . url($path)); exit; }
function fmt_date(?string $d): string { return $d ? date('d M Y', strtotime($d)) : ''; }

function flash(string $type, string $msg): void { $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg]; }
function get_flash(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">'; }
function verify_csrf(): void
{
    $sent  = $_POST['csrf_token'] ?? '';
    $known = $_SESSION['csrf'] ?? '';
    if (!is_string($sent) || $sent === '' || $known === '' || !hash_equals($known, $sent)) {
        http_response_code(419);
        exit('Your form session expired. Go back, refresh the page and try again.');
    }
}

/* ---------- Input cleaning ---------- */
function clean_line(?string $v): string { return trim(preg_replace('/\s+/', ' ', strip_tags((string)$v))); }
function clean_text(?string $v): string { return trim(preg_replace("/[ \t]+/", ' ', str_replace("\r", '', strip_tags((string)$v)))); }
function like_escape(string $v): string { return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $v); }
function valid_date(string $d): bool
{
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}
function field_error(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<span class="field-error">' . e($errors[$key]) . '</span>' : '';
}

/* ---------- Badges & cards ---------- */
function status_badge(string $status): string
{
    return '<span class="badge badge-' . e($status) . '">' . e(STATUS_LABELS[$status] ?? ucfirst($status)) . '</span>';
}
function type_badge(string $type): string
{
    return '<span class="badge badge-' . e($type) . '">' . ($type === 'lost' ? 'Lost' : 'Found') . '</span>';
}
function claim_badge(string $status): string
{
    return '<span class="badge badge-c-' . e($status) . '">' . e(ucfirst($status)) . '</span>';
}
function image_url(?string $img): string
{
    return $img ? url('uploads/' . rawurlencode($img)) : url('assets/images/placeholder.svg');
}
function item_card(array $it, string $extra = ''): string
{
    $h  = '<article class="card item-card">';
    $h .= '<a class="card-img" href="' . url('item-details.php?id=' . (int)$it['id']) . '">';
    $h .= '<img src="' . e(image_url($it['image'])) . '" alt="' . e($it['title']) . '" loading="lazy"></a>';
    $h .= '<div class="card-body"><div class="badges">' . type_badge($it['type']) . status_badge($it['status']) . '</div>';
    $h .= '<h3><a href="' . url('item-details.php?id=' . (int)$it['id']) . '">' . e($it['title']) . '</a></h3>';
    $h .= '<p class="meta">' . e($it['category']) . '</p>';
    $h .= '<p class="meta">' . e($it['location']) . ' &middot; ' . e(fmt_date($it['item_date'])) . '</p>' . $extra . '</div></article>';
    return $h;
}

/* ---------- Image upload ---------- */
/** Returns [filename|null, error|null]. A missing file is not an error (image is optional). */
function handle_image_upload(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) { return [null, null]; }
    if ($file['error'] !== UPLOAD_ERR_OK) { return [null, 'The image could not be uploaded. Try a smaller file.']; }
    if ($file['size'] > MAX_IMAGE_BYTES) { return [null, 'The image is larger than 2 MB. Choose a smaller photo.']; }
    if (!is_uploaded_file($file['tmp_name'])) { return [null, 'Invalid upload.']; }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime]) || @getimagesize($file['tmp_name']) === false) {
        return [null, 'Only JPG, PNG or WEBP images are allowed.'];
    }
    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/../uploads/' . $name)) {
        return [null, 'The image could not be saved. Check that the uploads folder is writable.'];
    }
    return [$name, null];
}
function delete_image(?string $name): void
{
    if ($name && preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $name)) {
        $path = __DIR__ . '/../uploads/' . $name;
        if (is_file($path)) { @unlink($path); }
    }
}

/* ---------- Item validation shared by report + edit ---------- */
/** Returns [cleanData, errors]. */
function validate_item_input(array $in): array
{
    $d = [
        'title'       => clean_line($in['title'] ?? ''),
        'category'    => clean_line($in['category'] ?? ''),
        'location'    => clean_line($in['location'] ?? ''),
        'item_date'   => trim((string)($in['item_date'] ?? '')),
        'description' => clean_text($in['description'] ?? ''),
    ];
    $err = [];
    $len = fn(string $s) => mb_strlen($s);
    if ($len($d['title']) < 3 || $len($d['title']) > 120) { $err['title'] = 'Item name must be 3 to 120 characters.'; }
    if (!in_array($d['category'], CATEGORIES, true)) { $err['category'] = 'Choose a category from the list.'; }
    if ($len($d['location']) < 3 || $len($d['location']) > 150) { $err['location'] = 'Location must be 3 to 150 characters.'; }
    if (!valid_date($d['item_date'])) { $err['item_date'] = 'Enter a valid date.'; }
    elseif ($d['item_date'] > date('Y-m-d')) { $err['item_date'] = 'The date cannot be in the future.'; }
    if ($len($d['description']) < 10 || $len($d['description']) > 1000) { $err['description'] = 'Description must be 10 to 1000 characters.'; }
    return [$d, $err];
}

/* ---------- Lost-Found Matching Assistant (rule-based, no AI) ----------
 * Score out of 100:  item name words 50 + same category 30 + location 20.
 * Reasons are listed so students can see exactly why an item was suggested.
 */
function tokens(string $s): array
{
    $stop = ['the', 'a', 'an', 'of', 'and', 'my', 'with', 'near', 'at', 'in', 'on', 'for', 'to'];
    $s = strtolower(preg_replace('/[^a-z0-9]+/i', ' ', $s));
    return array_values(array_unique(array_filter(explode(' ', $s), fn($w) => strlen($w) > 1 && !in_array($w, $stop, true))));
}
function match_score(array $a, array $b): array
{
    $score = 0; $reasons = [];

    $ta = tokens($a['title']); $tb = tokens($b['title']);
    $common = array_values(array_intersect($ta, $tb));
    if ($common) {
        $pts = (int)round(50 * count($common) / min(count($ta), count($tb)));
        $score += $pts;
        $reasons[] = 'Item name shares "' . implode(', ', $common) . '" (+' . $pts . ')';
    }
    if (strcasecmp($a['category'], $b['category']) === 0) {
        $score += 30; $reasons[] = 'Same category: ' . $a['category'] . ' (+30)';
    }
    if (strcasecmp($a['location'], $b['location']) === 0) {
        $score += 20; $reasons[] = 'Same location: ' . $a['location'] . ' (+20)';
    } else {
        $lc = array_intersect(tokens($a['location']), tokens($b['location']));
        if ($lc) { $score += 10; $reasons[] = 'Similar location: "' . implode(', ', $lc) . '" (+10)'; }
    }
    return [$score, $reasons];
}
/** Open items of the opposite type, reported by other users, with score >= 40. */
function find_matches(PDO $pdo, array $item, int $limit = 5): array
{
    $other = $item['type'] === 'lost' ? 'found' : 'lost';
    $st = $pdo->prepare("SELECT * FROM items WHERE type = :t AND status = 'open' AND user_id <> :u ORDER BY created_at DESC LIMIT 300");
    $st->execute([':t' => $other, ':u' => $item['user_id']]);
    $out = [];
    foreach ($st->fetchAll() as $cand) {
        [$score, $reasons] = match_score($item, $cand);
        if ($score >= 40) { $cand['score'] = $score; $cand['reasons'] = $reasons; $out[] = $cand; }
    }
    usort($out, fn($x, $y) => $y['score'] <=> $x['score']);
    return array_slice($out, 0, $limit);
}
function match_panel(array $matches, string $heading): string
{
    if (!$matches) { return ''; }
    $h = '<section class="panel match-panel"><h2>' . e($heading) . '</h2>'
       . '<p class="muted">Suggested by simple rules: item name words, category and location. Check the details yourself before claiming.</p><div class="match-list">';
    foreach ($matches as $m) {
        $h .= '<div class="match"><div class="score" aria-label="Match score ' . (int)$m['score'] . ' out of 100"><strong>' . (int)$m['score'] . '</strong><span>/100</span></div>'
            . '<div><a href="' . url('item-details.php?id=' . (int)$m['id']) . '"><strong>' . e($m['title']) . '</strong></a>'
            . '<div class="meta">' . e($m['location']) . ' &middot; ' . e(fmt_date($m['item_date'])) . '</div><ul class="reasons">';
        foreach ($m['reasons'] as $r) { $h .= '<li>' . e($r) . '</li>'; }
        $h .= '</ul></div></div>';
    }
    return $h . '</div></section>';
}
