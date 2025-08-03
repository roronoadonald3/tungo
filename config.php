<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
session_regenerate_id(true);

// SQLite configuration
$dsn = 'sqlite:' . __DIR__ . '/data/data.db';
try {
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON;');
} catch (PDOException $e) {
    die('DB Connection failed: ' . $e->getMessage());
}
?>