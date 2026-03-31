<?php
require 'config.php';

try {
    // Добавляем поле для фона профиля
    $pdo->exec("ALTER TABLE users ADD COLUMN background VARCHAR(255) DEFAULT NULL AFTER theme");
    
    // Создаем таблицу для файлов
    $pdo->exec("CREATE TABLE IF NOT EXISTS attachments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        entry_id INT NOT NULL,
        filename VARCHAR(255) NOT NULL,
        filepath VARCHAR(255) NOT NULL,
        filesize INT NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (entry_id) REFERENCES entries(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Создаем таблицу личных сообщений
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        recipient_id INT NOT NULL,
        subject VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Добавляем поля is_admin и is_owner
    $pdo->exec("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0 AFTER background");
    $pdo->exec("ALTER TABLE users ADD COLUMN is_owner TINYINT(1) DEFAULT 0 AFTER is_admin");
    $pdo->exec("ALTER TABLE users ADD COLUMN unique_id VARCHAR(20) UNIQUE AFTER id");
    
    // Назначаем LayZ владельцем
    $stmt = $pdo->prepare("UPDATE users SET is_owner = 1, is_admin = 1 WHERE username = ?");
    $stmt->execute(array('LayZ'));
    
    echo "✅ Все обновления установлены!<br>";
    echo "✅ LayZ назначен ВЛАДЕЛЬЦЕМ!<br>";
    echo "<a href='diary.php'>На главную</a>";
    
} catch(PDOException $e) {
    echo "❌ Ошибка: " . $e->getMessage();
}
?>