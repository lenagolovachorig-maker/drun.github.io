<?php
require 'config.php';

try {
    // Создаем таблицу дизлайков
    $pdo->exec("CREATE TABLE IF NOT EXISTS dislikes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        entry_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_dislike (user_id, entry_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (entry_id) REFERENCES entries(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Добавляем поле для защиты от DDoS
    $pdo->exec("CREATE TABLE IF NOT EXISTS access_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT,
        page_url VARCHAR(255),
        access_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ip (ip_address),
        INDEX idx_time (access_time)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Добавляем поле failed_attempts для защиты от брута
    $pdo->exec("ALTER TABLE users ADD COLUMN failed_attempts INT DEFAULT 0 AFTER unique_id");
    $pdo->exec("ALTER TABLE users ADD COLUMN last_attempt DATETIME NULL AFTER failed_attempts");
    
    echo "✅ Таблицы созданы!<br>";
    echo "✅ Дизлайки добавлены!<br>";
    echo "✅ Anti-DDoS система готова!<br>";
    echo "<a href='diary.php'>На главную</a>";
    
} catch(PDOException $e) {
    echo "❌ Ошибка: " . $e->getMessage();
}
?>