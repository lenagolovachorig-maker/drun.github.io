<?php
require 'config.php';

try {
    $stmt = $pdo->query("SELECT id FROM users WHERE unique_id IS NULL");
    $users = $stmt->fetchAll();
    
    $count = 0;
    foreach ($users as $user) {
        $unique_id = 'UID-' . strtoupper(substr(md5(uniqid()), 0, 6));
        
        while (true) {
            $check = $pdo->prepare("SELECT id FROM users WHERE unique_id = ?");
            $check->execute(array($unique_id));
            if (!$check->fetch()) break;
            $unique_id = 'UID-' . strtoupper(substr(md5(uniqid()), 0, 6));
        }
        
        $stmt = $pdo->prepare("UPDATE users SET unique_id = ? WHERE id = ?");
        $stmt->execute(array($unique_id, $user['id']));
        $count++;
    }
    
    echo "✅ Уникальные ID присвоены!<br>";
    echo "✅ Обработано пользователей: <strong>$count</strong><br>";
    echo "<a href='diary.php'>На главную</a>";
    
} catch(PDOException $e) {
    echo "❌ Ошибка: " . $e->getMessage();
}
?>