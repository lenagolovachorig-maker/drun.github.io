<?php
require 'antiddos.php';
require 'config.php';
requireLogin();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// Отправка сообщения
if (isset($_POST['send_message'])) {
    $recipient_id = (int)$_POST['recipient_id'];
    $subject = trim($_POST['subject']);
    $content = trim($_POST['content']);
    
    if (!empty($subject) && !empty($content) && $recipient_id != $user_id) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, recipient_id, subject, content) VALUES (?, ?, ?, ?)");
        $stmt->execute(array($user_id, $recipient_id, $subject, $content));
        $message = '✅ Сообщение отправлено!';
    }
}

// Получение сообщений
$action = isset($_GET['action']) ? $_GET['action'] : 'inbox';
$messages = array();

if ($action == 'inbox') {
    $stmt = $pdo->prepare("SELECT messages.*, users.username as sender_name 
                           FROM messages 
                           JOIN users ON messages.sender_id = users.id 
                           WHERE messages.recipient_id = ? 
                           ORDER BY messages.created_at DESC");
    $stmt->execute(array($user_id));
    $messages = $stmt->fetchAll();
} elseif ($action == 'sent') {
    $stmt = $pdo->prepare("SELECT messages.*, users.username as recipient_name 
                           FROM messages 
                           JOIN users ON messages.recipient_id = users.id 
                           WHERE messages.sender_id = ? 
                           ORDER BY messages.created_at DESC");
    $stmt->execute(array($user_id));
    $messages = $stmt->fetchAll();
}

// Получение списка пользователей
$stmt = $pdo->query("SELECT id, username, unique_id FROM users WHERE id != $user_id ORDER BY username");
$users = $stmt->fetchAll();

// Считаем непрочитанные
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM messages WHERE recipient_id = ? AND is_read = 0");
$stmt->execute(array($user_id));
$unread_count = $stmt->fetch()['count'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>📬 Сообщения</title>
    <script src="theme.js"></script>
    <style>
    body {
        font-family: Arial, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        margin: 0;
        padding: 0;
        min-height: 100vh;
        transition: background 0.5s ease;
    }
    body.dark-theme {
        background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
    }
    .theme-toggle-btn {
        position: fixed;
        top: 20px;
        right: 20px;
        width: 50px;
        height: 50px;
        background: rgba(255, 255, 255, 0.9);
        border: none;
        border-radius: 50%;
        cursor: pointer;
        font-size: 24px;
        z-index: 1000;
    }
    body.dark-theme .theme-toggle-btn {
        background: rgba(255, 255, 255, 0.15);
    }
    .container {
        max-width: 900px;
        margin: 0 auto;
        padding: 100px 20px 40px;
    }
    .nav-bar {
        background: white;
        padding: 20px;
        border-radius: 15px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    body.dark-theme .nav-bar {
        background: rgba(255,255,255,0.98);
    }
    .nav-bar h2 {
        color: #6c5ce7;
        margin: 0;
    }
    .card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    body.dark-theme .card {
        background: rgba(255,255,255,0.98);
    }
    .message-list {
        max-height: 400px;
        overflow-y: auto;
    }
    .message-item {
        padding: 15px;
        border-bottom: 1px solid #eee;
        cursor: pointer;
        transition: 0.3s;
    }
    body.dark-theme .message-item {
        border-bottom-color: rgba(255,255,255,0.1);
    }
    .message-item:hover {
        background: #f8f9fa;
    }
    body.dark-theme .message-item:hover {
        background: rgba(255,255,255,0.05);
    }
    .message-item.unread {
        background: #e3f2fd;
        font-weight: bold;
    }
    body.dark-theme .message-item.unread {
        background: rgba(102, 126, 234, 0.2);
    }
    .btn-small {
        padding: 10px 20px;
        background: #6c5ce7;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        display: inline-block;
        margin: 5px;
        transition: 0.3s;
    }
    .btn-small:hover {
        background: #5b4cdb;
        transform: translateY(-2px);
    }
    input, textarea, select {
        width: 100%;
        padding: 12px;
        margin: 10px 0;
        border: 2px solid #eee;
        border-radius: 8px;
        box-sizing: border-box;
        font-family: Arial, sans-serif;
    }
    input:focus, textarea:focus, select:focus {
        outline: none;
        border-color: #6c5ce7;
    }
    button[type="submit"] {
        background: #6c5ce7;
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 16px;
        font-weight: bold;
        transition: 0.3s;
    }
    button[type="submit"]:hover {
        background: #5b4cdb;
        transform: translateY(-2px);
    }
    .alert {
        background: #00b894;
        color: white;
        padding: 12px;
        border-radius: 8px;
        text-align: center;
        margin-bottom: 20px;
    }
    .tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }
    .tab {
        padding: 10px 20px;
        background: rgba(255,255,255,0.5);
        border-radius: 8px;
        text-decoration: none;
        color: #6c5ce7;
        font-weight: bold;
        transition: 0.3s;
    }
    body.dark-theme .tab {
        background: rgba(255,255,255,0.1);
    }
    .tab.active {
        background: #6c5ce7;
        color: white;
    }
    .badge {
        background: #ff7675;
        color: white;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 12px;
        margin-left: 5px;
    }
    </style>
</head>
<body>
    <button class="theme-toggle-btn" onclick="toggleTheme()">
        <span id="theme-icon">🌙</span>
    </button>
    
    <div class="container">
        <div class="nav-bar">
            <h2>📬 Личные сообщения</h2>
            <div>
                <a href="diary.php" class="btn-small">← К дневнику</a>
                <a href="profile.php" class="btn-small">👤 Профиль</a>
            </div>
        </div>
        
        <?php if($message): ?>
            <div class="alert"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h3>✉️ Новое сообщение</h3>
            <form method="POST">
                <select name="recipient_id" required>
                    <option value="">Выберите получателя...</option>
                    <?php foreach($users as $u): ?>
                        <option value="<?php echo $u['id']; ?>">
                            <?php echo htmlspecialchars($u['username']); ?> 
                            [<?php echo htmlspecialchars($u['unique_id']); ?>]
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="subject" placeholder="Тема сообщения..." required>
                <textarea name="content" rows="5" placeholder="Текст сообщения..." required></textarea>
                <button type="submit" name="send_message">📤 Отправить</button>
            </form>
        </div>
        
        <div class="card">
            <div class="tabs">
                <a href="?action=inbox" class="tab <?php echo $action=='inbox'?'active':'' ?>">
                    📥 Входящие
                    <?php if($unread_count > 0): ?>
                        <span class="badge"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="?action=sent" class="tab <?php echo $action=='sent'?'active':'' ?>">📤 Отправленные</a>
            </div>
            
            <div class="message-list">
                <?php foreach($messages as $msg): ?>
                    <div class="message-item <?php echo !$msg['is_read'] && $action=='inbox' ? 'unread' : ''; ?>">
                        <strong>
                            <?php echo $action=='inbox' ? htmlspecialchars($msg['sender_name']) : htmlspecialchars($msg['recipient_name']); ?>
                        </strong>
                        <div style="color: #666; font-size: 14px;">
                            <?php echo htmlspecialchars($msg['subject']); ?>
                        </div>
                        <div style="color: #999; font-size: 12px;">
                            <?php echo date('d.m.Y H:i', strtotime($msg['created_at'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if(count($messages) == 0): ?>
                    <div style="text-align: center; color: #999; padding: 40px;">
                        📭 Нет сообщений
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>