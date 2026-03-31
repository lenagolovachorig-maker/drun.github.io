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

// Загрузка аватара
if (isset($_POST['upload_avatar'])) {
    if (!empty($_FILES['avatar']['name'])) {
        $allowed = ['jpg','jpeg','png','gif'];
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed) && $_FILES['avatar']['size'] < 5242880) {
            $new_filename = 'avatar_' . $user_id . '.' . $ext;
            $upload_path = 'avatars/' . $new_filename;
            
            if (!file_exists('avatars')) {
                mkdir('avatars', 0777, true);
            }
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                $stmt->execute(array($upload_path, $user_id));
                $message = '✅ Аватар загружен!';
            } else {
                $message = '❌ Ошибка загрузки';
            }
        } else {
            $message = '❌ Разрешены только JPG, JPEG, PNG, GIF (макс. 5MB)';
        }
    }
}

// Получаем данные пользователя
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute(array($user_id));
$user = $stmt->fetch();

// Статистика пользователя
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM entries WHERE user_id = ?");
$stmt->execute(array($user_id));
$total_entries = $stmt->fetch()['count'];

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE user_id = ?");
$stmt->execute(array($user_id));
$total_comments = $stmt->fetch()['count'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>👤 Профиль</title>
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
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        transition: all 0.3s ease;
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .theme-toggle-btn:hover {
        transform: scale(1.1) rotate(15deg);
    }
    body.dark-theme .theme-toggle-btn {
        background: rgba(255, 255, 255, 0.15);
    }
    .container {
        max-width: 700px;
        margin: 0 auto;
        padding: 100px 20px 40px;
    }
    .card {
        background: white;
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        text-align: center;
    }
    body.dark-theme .card {
        background: rgba(255, 255, 255, 0.98);
    }
    h1 {
        color: #6c5ce7;
        margin-bottom: 30px;
    }
    .avatar-container {
        margin: 30px 0;
    }
    .avatar-circle {
        width: 150px;
        height: 150px;
        border-radius: 50% !important;
        object-fit: cover;
        border: 4px solid #6c5ce7;
        display: block;
        margin: 0 auto;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    .avatar-placeholder-circle {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6c5ce7, #a29bfe);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 64px;
        font-weight: bold;
        border: 4px solid white;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        margin: 0 auto;
    }
    h2 {
        color: #6c5ce7;
        margin: 20px 0 10px;
    }
    .info-text {
        color: #666;
        margin: 10px 0;
    }
    body.dark-theme .info-text {
        color: #aaa;
    }
    .stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin: 30px 0;
    }
    .stat-box {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
    }
    body.dark-theme .stat-box {
        background: rgba(255,255,255,0.1);
    }
    .stat-number {
        font-size: 32px;
        font-weight: bold;
        color: #6c5ce7;
        display: block;
    }
    .stat-label {
        color: #999;
        font-size: 14px;
    }
    .upload-avatar {
        margin-top: 30px;
    }
    .upload-avatar input {
        display: block;
        margin: 15px auto;
        padding: 10px;
        border: 2px solid #eee;
        border-radius: 8px;
        width: 100%;
        max-width: 300px;
    }
    .btn-primary {
        background: #00b894;
        color: white;
        border: none;
        padding: 14px 30px;
        border-radius: 8px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        transition: 0.3s;
    }
    .btn-primary:hover {
        background: #00a884;
        transform: translateY(-2px);
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
    .alert {
        background: #ffeaa7;
        color: #d63031;
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .unique-id {
        color: #6c5ce7;
        font-family: monospace;
        background: #f0f0f0;
        padding: 5px 10px;
        border-radius: 5px;
        font-size: 14px;
    }
    body.dark-theme .unique-id {
        background: rgba(255,255,255,0.1);
    }
    </style>
</head>
<body>
    <button class="theme-toggle-btn" onclick="toggleTheme()">
        <span id="theme-icon">🌙</span>
    </button>
    
    <div class="container">
        <div class="card">
            <h1>👤 Профиль</h1>
            
            <?php if($message): ?>
                <div class="alert"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <div class="avatar-container">
                <?php if($user['avatar'] && file_exists($user['avatar'])): ?>
                    <img src="<?php echo htmlspecialchars($user['avatar']); ?>" 
                         class="avatar-circle" 
                         alt="Аватар">
                <?php else: ?>
                    <div class="avatar-placeholder-circle">
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <h2><?php echo htmlspecialchars($user['username']); ?></h2>
            <p class="info-text">🆔 UID: <span class="unique-id"><?php echo htmlspecialchars($user['unique_id'] ?? 'N/A'); ?></span></p>
            <p class="info-text">📅 Зарегистрирован: <?php echo date('d.m.Y', strtotime($user['created_at'])); ?></p>
            
            <?php if($user['is_owner']): ?>
                <p class="info-text" style="color: #ff7675; font-weight: bold;">👑 ВЛАДЕЛЕЦ ПРОЕКТА</p>
            <?php elseif($user['is_admin']): ?>
                <p class="info-text" style="color: #6c5ce7; font-weight: bold;">👔 АДМИНИСТРАТОР</p>
            <?php endif; ?>
            
            <div class="stats">
                <div class="stat-box">
                    <span class="stat-number"><?php echo $total_entries; ?></span>
                    <div class="stat-label">📝 Записей</div>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?php echo $total_comments; ?></span>
                    <div class="stat-label">💬 Комментариев</div>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?php echo date('d', strtotime($user['created_at'])); ?></span>
                    <div class="stat-label">📅 День регистрации</div>
                </div>
            </div>
            
            <div class="upload-avatar">
                <form method="POST" enctype="multipart/form-data">
                    <input type="file" name="avatar" id="avatar" accept="image/*">
                    <button type="submit" name="upload_avatar" class="btn-primary">💾 Сохранить аватар</button>
                </form>
            </div>
            
            <div style="margin-top: 30px;">
                <a href="diary.php" class="btn-small">← К дневнику</a>
                <a href="messages.php" class="btn-small">📬 Сообщения</a>
                <?php if($user['is_owner']): ?>
                    <a href="owner-panel.php" class="btn-small" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">👑 Владелец</a>
                <?php elseif($user['is_admin']): ?>
                    <a href="admin.php" class="btn-small" style="background: #ff7675;">👔 Админка</a>
                <?php endif; ?>
                <a href="logout.php" class="btn-small" style="background: #ff7675;">Выйти</a>
            </div>
        </div>
    </div>
</body>
</html>