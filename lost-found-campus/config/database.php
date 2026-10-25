<?php
declare(strict_types=1);

/**
 * Database and site settings.
 * Default XAMPP MySQL: user "root", empty password.
 * BASE_URL must match the folder name you copied into C:\xampp\htdocs\
 */
const DB_HOST  = 'localhost';
const DB_NAME  = 'lost_found_portal';
const DB_USER  = 'root';
const DB_PASS  = '';
const BASE_URL = '/lost-found-campus';

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Database connection failed. Start MySQL in the XAMPP Control Panel, import database.sql, and check config/database.php.');
        }
    }
    return $pdo;
}
