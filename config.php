<?php
// Простая конфигурация без лишних функций

$host = 'sql311.infinityfree.com';
$db   = 'if0_41462916_diary';
$user = 'if0_41462916';
$pass = 'QD5DDf4n';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = array(
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
);

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die('Ошибка БД: ' . $e->getMessage());
}

if (session_id() == '') {
    session_start();
}
?>