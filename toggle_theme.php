<?php
session_start();
require 'config.php';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Получаем текущую тему
    $stmt = $pdo->prepare("SELECT theme FROM users WHERE id = ?");
    $stmt->execute(array($user_id));
    $user = $stmt->fetch();
    
    // Переключаем
    $new_theme = ($user['theme'] === 'dark') ? 'light' : 'dark';
    
    // Сохраняем в БД
    $stmt = $pdo->prepare("UPDATE users SET theme = ? WHERE id = ?");
    $stmt->execute(array($new_theme, $user_id));
    
    // Сохраняем в сессии
    $_SESSION['theme'] = $new_theme;
}

header('Location: profile.php');
exit;
?>