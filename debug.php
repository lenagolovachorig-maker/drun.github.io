<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Проверка ошибок</h1>";

try {
    echo "<h2>1. Проверка config.php</h2>";
    require 'config.php';
    echo "✅ config.php загружен<br>";
    
    echo "<h2>2. Проверка подключения к БД</h2>";
    $stmt = $pdo->query("SELECT 1");
    echo "✅ База данных подключена<br>";
    
    echo "<h2>3. Проверка таблиц</h2>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "✅ Таблицы: " . implode(', ', $tables) . "<br>";
    
    echo "<h2>4. Проверка сессии</h2>";
    if (session_id() == '') {
        session_start();
    }
    echo "✅ Сессия запущена<br>";
    
    echo "<h2>5. Проверка antiddos.php</h2>";
    require 'antiddos.php';
    echo "✅ antiddos.php загружен<br>";
    
    echo "<h2>6. Проверка функций</h2>";
    if (function_exists('requireLogin')) {
        echo "✅ Функции доступны<br>";
    }
    
    echo "<br><h2>✅ ВСЕ ПРОВЕРКИ ПРОЙДЕНЫ!</h2>";
    echo "<a href='profile.php'>Перейти в профиль</a>";
    
} catch (Exception $e) {
    echo "<br><h2 style='color: red;'>❌ ОШИБКА:</h2>";
    echo "<pre style='background: #ffeaa7; padding: 15px; border-radius: 8px;'>";
    echo htmlspecialchars($e->getMessage());
    echo "<br><br>Файл: " . $e->getFile();
    echo "<br>Строка: " . $e->getLine();
    echo "</pre>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>