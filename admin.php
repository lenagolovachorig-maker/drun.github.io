<?php
require 'antiddos.php';
require 'config.php';
requireAdmin();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// Удаление пользователя
if (isset($_GET['delete_user'])) {
    $del_id = (int)$_GET['delete_user'];
    if ($del_id != $user_id) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute(array($del_id));
        $message = '✅ Пользователь удалён!';
    }
}

// Удаление записи
if (isset($_GET['delete_entry'])) {
    $del_id = (int)$_GET['delete_entry'];
    $stmt = $pdo->prepare("DELETE FROM entries WHERE id = ?");
    $stmt->execute(array($del_id));
    $message = '✅ Запись удалена!';
}

// Получить всех пользователей
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$all_users = $stmt->fetchAll();

// Получить все записи
$stmt = $pdo->query("SELECT entries.*, users.username, users.is_admin, users.is_owner 
                     FROM entries 
                     JOIN users ON entries.user_id = users.id 
                     ORDER BY entries.created_at DESC");
$all_entries = $stmt->fetchAll();

$total_users = count($all_users);
$total_entries = count($all_entries);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>👔 Админ-панель</title>
    <script src="theme.js"></script>
    <style>
    body {
        font-family: Arial, sans-serif;
        background: linear-gradient(135deg, #2d3436 0%, #000000 100%);
        margin: 0;
        padding: 0;
        min-height: 100vh;
        color: white;
        transition: background 0.5s ease;
    }
    body.dark-theme {
        background: linear-gradient(135deg, #000000 0%, #1a1a2e 100%);
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
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 100px 20px 40px;
    }
    .header {
        background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
        padding: 30px;
        border-radius: 20px;
        margin-bottom: 30px;
        text-align: center;
    }
    .card {
        background: rgba(255,255,255,0.1);
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 30px;
        backdrop-filter: blur(10px);
    }
    .card h2 {
        color: #a29bfe;
        margin-bottom: 20px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    th, td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    th {
        background: rgba(108, 92, 231, 0.5);
        color: #a29bfe;
    }
    .btn {
        padding: 10px 20px;
        background: #6c5ce7;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        display: inline-block;
        margin: 3px;
        border: none;
        cursor: pointer;
        transition: 0.3s;
    }
    .btn:hover {
        background: #5b4cdb;
        transform: translateY(-2px);
    }
    .btn-danger {
        background: #ff7675;
    }
    .btn-success {
        background: #00b894;
    }
    .btn-warning {
        background: #fdcb6e;
        color: #2d3436;
    }
    .alert {
        background: #00b894;
        color: white;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        text-align: center;
    }
    .nav-bar {
        background: rgba(255,255,255,0.1);
        padding: 20px;
        border-radius: 15px;
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    .nav-bar h2 {
        color: #a29bfe;
        margin: 0;
    }
    .badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        display: inline-block;
    }
    .badge-owner {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
    }
    .badge-admin {
        background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
        color: white;
    }
    .stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .stat-card {
        background: rgba(255,255,255,0.1);
        padding: 25px;
        border-radius: 15px;
        text-align: center;
    }
    .stat-number {
        font-size: 48px;
        font-weight: bold;
        color: #a29bfe;
        display: block;
    }
    </style>
</head>
<body>
    <button class="theme-toggle-btn" onclick="toggleTheme()">
        <span id="theme-icon">🌙</span>
    </button>
    
    <div class="container">
        <div class="nav-bar">
            <h2>👔 Админ-панель</h2>
            <div>
                <a href="diary.php" class="btn">← К дневнику</a>
                <a href="security-panel.php" class="btn btn-danger">🛡️ Защита</a>
                <a href="admin-rules.php" class="btn btn-warning">📜 Правила</a>
                <a href="logout.php" class="btn btn-danger">Выйти</a>
            </div>
        </div>
        
        <div class="header">
            <h1>👔 ПАНЕЛЬ АДМИНИСТРАТОРА</h1>
            <p>Управление пользователями и записями</p>
        </div>
        
        <?php if($message): ?>
            <div class="alert"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <div class="stats">
            <div class="stat-card">
                <span class="stat-number"><?php echo $total_users; ?></span>
                <div>Пользователей</div>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $total_entries; ?></span>
                <div>Записей</div>
            </div>
        </div>
        
        <div class="card">
            <h2>👥 Пользователи</h2>
            <table>
                <tr>
                    <th>UID</th>
                    <th>ID</th>
                    <th>Никнейм</th>
                    <th>Роль</th>
                    <th>Дата</th>
                    <th>Действия</th>
                </tr>
                <?php foreach($all_users as $u): ?>
                    <tr>
                        <td><code style="color: #fdcb6e;"><?php echo htmlspecialchars($u['unique_id'] ?? 'N/A'); ?></code></td>
                        <td><?php echo $u['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                        <td>
                            <?php if($u['is_owner']): ?>
                                <span class="badge badge-owner">👑 Владелец</span>
                            <?php elseif($u['is_admin']): ?>
                                <span class="badge badge-admin">👔 Админ</span>
                            <?php else: ?>
                                <span class="badge" style="background: #dfe6e9; color: #2d3436;">👤 Пользователь</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d.m.Y', strtotime($u['created_at'])); ?></td>
                        <td>
                            <?php if($u['id'] != $user_id): ?>
                                <a href="?delete_user=<?php echo $u['id']; ?>" 
                                   class="btn btn-danger" 
                                   onclick="return confirm('Удалить пользователя?')">🗑️</a>
                            <?php else: ?>
                                <span style="color: #00b894;">✓ Вы</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <div class="card">
            <h2>📝 Все записи</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Автор</th>
                    <th>Заголовок</th>
                    <th>Приватность</th>
                    <th>Действия</th>
                </tr>
                <?php foreach($all_entries as $e): ?>
                    <tr>
                        <td><?php echo $e['id']; ?></td>
                        <td><?php echo htmlspecialchars($e['username']); ?></td>
                        <td><?php echo htmlspecialchars($e['title']); ?></td>
                        <td>
                            <?php if($e['is_private']): ?>
                                <span style="color: #ff7675;">🔒 Приватно</span>
                            <?php else: ?>
                                <span style="color: #00b894;">🌍 Публично</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="?delete_entry=<?php echo $e['id']; ?>" 
                               class="btn btn-danger" 
                               onclick="return confirm('Удалить запись?')">🗑️</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>