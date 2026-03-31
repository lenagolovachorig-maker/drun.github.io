<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Тест сайта<br>";

require 'config.php';
echo "✅ config.php работает<br>";

$stmt = $pdo->query("SELECT 1");
echo "✅ База данных подключена<br>";

echo "✅ Сайт работает!";
?>