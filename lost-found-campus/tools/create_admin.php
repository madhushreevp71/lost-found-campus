<?php
declare(strict_types=1);
// Command-line only. Run from the project folder:  C:\xampp\php\php.exe tools\create_admin.php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Run this script from the command line.'); }
require_once __DIR__ . '/../config/database.php';

function ask(string $label): string { echo $label . ': '; return trim((string)fgets(STDIN)); }

$name  = ask('Full name');
$email = strtolower(ask('Email'));
$pass  = ask('Password (min 8 chars, letter + number; visible while typing)');

if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || strlen($pass) < 8 || !preg_match('/[A-Za-z]/', $pass) || !preg_match('/\d/', $pass)) {
    exit("Invalid input. Nothing was saved.\n");
}
$st = db()->prepare('SELECT id FROM users WHERE email = :e');
$st->execute([':e' => $email]);
if ($row = $st->fetch()) {
    db()->prepare("UPDATE users SET role='admin', full_name=:n, password_hash=:h WHERE id=:id")
        ->execute([':n' => $name, ':h' => password_hash($pass, PASSWORD_DEFAULT), ':id' => $row['id']]);
    exit("Existing account promoted to admin and password updated.\n");
}
db()->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (:n, :e, :h, 'admin')")
    ->execute([':n' => $name, ':e' => $email, ':h' => password_hash($pass, PASSWORD_DEFAULT)]);
echo "Admin account created for $email\n";
