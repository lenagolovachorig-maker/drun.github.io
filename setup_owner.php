<?php
require 'config.php';

try {
    $stmt = $pdo->prepare("UPDATE users SET is_owner = 1, is_admin = 1 WHERE username = ?");
    $stmt->execute(array('LayZ'));
    
    echo "✅ LayZ назначен ВЛАДЕЛЬЦЕМ!<br>";
    echo "<a href='diary.php'>На главную</a>";
    
} catch(PDOException $e) {
    echo "❌ Ошибка: " . $e->getMessage();
}
?>