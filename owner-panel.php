<?php
require 'antiddos.php';
require 'config.php';
requireOwner();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// Назначить админа
if (isset($_POST['make_admin'])) {
    $target_id = (int)$_POST['user_id'];
    if ($target_id != $user_id) {
        $stmt = $pdo->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
        $stmt->execute(array($target_id));
        $message = '✅ Пользователь назначен АДМИНОМ!';
    }
}

// Снять админа
if (isset($_POST['remove_admin'])) {
    $target_id = (int)$_POST['user_id'];
    if ($target_id != $user_id) {
        $stmt = $pdo->prepare("UPDATE users SET is_admin = 0 WHERE id = ?");
        $stmt->execute(array($target_id));
        $message = '✅ Пользователь снят с должности АДМИНА!';
    }
}

// Удалить пользователя
if (isset($_GET['delete_user'])) {
    $del_id = (int)$_GET['delete_user'];
    if ($del_id != $user_id) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute(array($del_id));
        $message = '✅ Пользователь удалён!';
    }
}

// Удалить запись
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
$total_admins = count(array_filter($all_users, function($u) { return $u['is_admin'] == 1; }));
$total_entries = count($all_entries);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>👑 Панель ВЛАДЕЛЬЦА</title>
    <script src="theme.js"></script>
    <style>
    body {
        font-family: Arial, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 20px;
        margin: 0;
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
        z-index: 1000;
    }
    body.dark-theme .theme-toggle-btn {
        background: rgba(255, 255, 255, 0.15);
    }
    .container {
        max-width: 1400px;
        margin: 0 auto;
    }
    .header {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        padding: 30px;
        border-radius: 20px;
        margin-bottom: 30px;
        text-align: center;
        color: white;
        box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    }
    .header h1 {
        font-size: 36px;
        margin-bottom: 10px;
    }
    .stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 15px;
        text-align: center;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    body.dark-theme .stat-card {
        background: rgba(255,255,255,0.1);
        color: white;
    }
    .stat-number {
        font-size: 48px;
        font-weight: bold;
        color: #6c5ce7;
        display: block;
    }
    .card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }
    body.dark-theme .card {
        background: rgba(255,255,255,0.05);
    }
    .card h2 {
        color: #6c5ce7;
        margin-bottom: 20px;
        font-size: 28px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    th, td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    body.dark-theme th, body.dark-theme td {
        border-bottom-color: rgba(255,255,255,0.1);
    }
    th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-weight: bold;
    }
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
        font-weight: bold;
        text-decoration: none;
        display: inline-block;
        margin: 3px;
        transition: 0.3s;
    }
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .btn-danger {
        background: linear-gradient(135deg, #ff7675 0%, #d63031 100%);
        color: white;
    }
    .btn-success {
        background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
        color: white;
    }
    .btn-warning {
        background: linear-gradient(135deg, #fdcb6e 0%, #f39c12 100%);
        color: #2d3436;
    }
    .alert {
        background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
        color: white;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        text-align: center;
        font-weight: bold;
    }
    .nav-bar {
        background: white;
        padding: 20px;
        border-radius: 15px;
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    body.dark-theme .nav-bar {
        background: rgba(255,255,255,0.1);
    }
    .nav-bar h2 {
        color: #6c5ce7;
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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .badge-user {
        background: #dfe6e9;
        color: #2d3436;
    }
    </style>
</head>
<body>
    <button class="theme-toggle-btn" onclick="toggleTheme()">
        <span id="theme-icon">🌙</span>
    </button>
    
    <div class="container">
        <div class="nav-bar">
            <h2>👑 Панель ВЛАДЕЛЬЦА</h2>
            <div>
                <a href="diary.php" class="btn btn-primary">← К дневнику</a>
                <a href="security-panel.php" class="btn btn-danger">🛡️ Защита</a>
                <a href="admin-rules.php" class="btn btn-warning">📜 Правила</a>
                <a href="logout.php" class="btn btn-danger">Выйти</a>
            </div>
        </div>
        
        <div class="header">
            <h1>👑 ПАНЕЛЬ УПРАВЛЕНИЯ ВЛАДЕЛЬЦА</h1>
            <p style="font-size: 18px; opacity: 0.9;">Добро пожаловать, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
            <p style="font-size: 14px; opacity: 0.9;">У вас есть полный контроль над системой</p>
        </div>
        
        <?php if($message): ?>
            <div class="alert">✨ <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <div class="stats">
            <div class="stat-card">
                <span class="stat-number"><?php echo $total_users; ?></span>
                <div>👥 Пользователей</div>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $total_admins; ?></span>
                <div>👔 Админов</div>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $total_entries; ?></span>
                <div>📝 Записей</div>
            </div>
        </div>
        
        <div class="card">
            <h2>👔 Управление АДМИНАМИ</h2>
            <p style="color: #666; margin-bottom: 20px;">Назначайте или снимайте администраторов</p>
            
            <table>
                <tr>
                    <th>UID</th>
                    <th>ID</th>
                    <th>Пользователь</th>
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
                                <span class="badge badge-owner">👑 ВЛАДЕЛЕЦ</span>
                            <?php elseif($u['is_admin']): ?>
                                <span class="badge badge-admin">👔 АДМИН</span>
                            <?php else: ?>
                                <span class="badge badge-user">👤 Пользователь</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d.m.Y', strtotime($u['created_at'])); ?></td>
                        <td>
                            <?php if($u['id'] != $user_id): ?>
                                <?php if($u['is_admin']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" name="remove_admin" class="btn btn-warning">⬇️ Снять</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" name="make_admin" class="btn btn-success">⬆️ Админ</button>
                                    </form>
                                <?php endif; ?>
                                <a href="?delete_user=<?php echo $u['id']; ?>" 
                                   class="btn btn-danger" 
                                   onclick="return confirm('УДАЛИТЬ пользователя?')">🗑️</a>
                            <?php else: ?>
                                <span style="color: #00b894;">✓ Вы</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <div class="card">
            <h2>📝 ВСЕ записи системы</h2>
            <p style="color: #666; margin-bottom: 20px;">Просмотр и управление всеми записями (включая приватные)</p>
            
            <table>
                <tr>
                    <th>ID</th>
                    <th>Автор</th>
                    <th>Роль</th>
                    <th>Заголовок</th>
                    <th>Приватность</th>
                    <th>Дата</th>
                    <th>Действия</th>
                </tr>
                <?php foreach($all_entries as $e): ?>
                    <tr>
                        <td><?php echo $e['id']; ?></td>
                        <td><?php echo htmlspecialchars($e['username']); ?></td>
                        <td>
                            <?php if($e['is_owner']): ?>
                                <span class="badge badge-owner">👑</span>
                            <?php elseif($e['is_admin']): ?>
                                <span class="badge badge-admin">👔</span>
                            <?php else: ?>
                                <span class="badge badge-user">👤</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($e['title']); ?></td>
                        <td>
                            <?php if($e['is_private']): ?>
                                <span style="color: #ff7675;">🔒 Приватно</span>
                            <?php else: ?>
                                <span style="color: #00b894;">🌍 Публично</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($e['created_at'])); ?></td>
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