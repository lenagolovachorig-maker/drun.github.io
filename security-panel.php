<?php
require 'antiddos.php';
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT is_admin, is_owner, username FROM users WHERE id = ?");
$stmt->execute(array($user_id));
$user_role = $stmt->fetch();

if (!$user_role['is_admin'] && !$user_role['is_owner']) {
    die('<h1 style="color: #ff7675; text-align: center; margin-top: 50px;">❌ Доступ только для администрации!</h1>');
}

$is_owner = $user_role['is_owner'];

// Очистка логов
if (isset($_GET['clear_logs']) && $is_owner) {
    $pdo->exec("TRUNCATE TABLE access_logs");
    header('Location: security-panel.php?cleared=1');
    exit;
}

// Сброс блокировок
if (isset($_GET['reset_blocks']) && $is_owner) {
    $pdo->exec("UPDATE users SET failed_attempts = 0, last_attempt = NULL");
    header('Location: security-panel.php?reset=1');
    exit;
}

// Получаем статистику
$stmt = $pdo->query("SELECT COUNT(*) as count FROM access_logs WHERE access_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
$requests_last_hour = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT ip_address, COUNT(*) as count FROM access_logs WHERE access_time > DATE_SUB(NOW(), INTERVAL 1 HOUR) GROUP BY ip_address ORDER BY count DESC LIMIT 10");
$top_ips = $stmt->fetchAll();

$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE failed_attempts >= 5");
$blocked_users = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM access_logs");
$total_logs = $stmt->fetch()['count'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>🛡️ Панель Безопасности</title>
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
        background: linear-gradient(135deg, #ff7675 0%, #d63031 100%);
        padding: 30px;
        border-radius: 20px;
        margin-bottom: 30px;
        text-align: center;
    }
    .header h1 {
        font-size: 36px;
        margin-bottom: 10px;
    }
    .stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .stat-card {
        background: rgba(255,255,255,0.1);
        padding: 25px;
        border-radius: 15px;
        text-align: center;
        backdrop-filter: blur(10px);
    }
    .stat-number {
        font-size: 48px;
        font-weight: bold;
        color: #ff7675;
        display: block;
    }
    .card {
        background: rgba(255,255,255,0.05);
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 30px;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.1);
    }
    .card h2 {
        color: #ff7675;
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
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    th {
        background: rgba(255, 118, 117, 0.3);
        color: #ff7675;
    }
    .btn {
        padding: 12px 25px;
        background: #ff7675;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        display: inline-block;
        margin: 5px;
        transition: 0.3s;
        border: none;
        cursor: pointer;
        font-size: 14px;
        font-weight: bold;
    }
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 118, 117, 0.4);
    }
    .btn-success {
        background: #00b894;
    }
    .btn-warning {
        background: #fdcb6e;
        color: #2d3436;
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
        color: #ff7675;
        margin: 0;
    }
    .alert {
        background: #00b894;
        color: white;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        text-align: center;
    }
    .security-item {
        background: rgba(0, 184, 148, 0.1);
        padding: 15px;
        border-radius: 10px;
        margin: 10px 0;
        border-left: 4px solid #00b894;
    }
    .security-item.warning {
        background: rgba(253, 203, 110, 0.1);
        border-left-color: #fdcb6e;
    }
    code {
        background: rgba(0,0,0,0.3);
        padding: 3px 8px;
        border-radius: 5px;
        font-family: monospace;
        color: #fdcb6e;
    }
    </style>
</head>
<body>
    <button class="theme-toggle-btn" onclick="toggleTheme()">
        <span id="theme-icon">🌙</span>
    </button>
    
    <div class="container">
        <div class="nav-bar">
            <h2>🛡️ Панель Безопасности</h2>
            <div>
                <a href="diary.php" class="btn">← К дневнику</a>
                <?php if($is_owner): ?>
                    <a href="owner-panel.php" class="btn btn-success">👑 Владелец</a>
                <?php else: ?>
                    <a href="admin.php" class="btn btn-success">👔 Админка</a>
                <?php endif; ?>
                <a href="admin-rules.php" class="btn btn-warning">📜 Правила</a>
            </div>
        </div>
        
        <div class="header">
            <h1>🛡️ ЗАЩИТА ОТ АТАК</h1>
            <p>Мониторинг и защита проекта в реальном времени</p>
        </div>
        
        <?php if(isset($_GET['cleared'])): ?>
            <div class="alert">✅ Логи очищены!</div>
        <?php endif; ?>
        
        <?php if(isset($_GET['reset'])): ?>
            <div class="alert">✅ Блокировки сброшены!</div>
        <?php endif; ?>
        
        <div class="stats">
            <div class="stat-card">
                <span class="stat-number"><?php echo $requests_last_hour; ?></span>
                <div>Запросов за последний час</div>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo count($top_ips); ?></span>
                <div>Уникальных IP адресов</div>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $blocked_users; ?></span>
                <div>Заблокированных пользователей</div>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $total_logs; ?></span>
                <div>Всего записей в логах</div>
            </div>
        </div>
        
        <div class="card">
            <h2>🔝 Топ IP адресов по запросам (за последний час)</h2>
            <table>
                <tr>
                    <th>IP Адрес</th>
                    <th>Количество запросов</th>
                    <th>Статус</th>
                </tr>
                <?php foreach($top_ips as $ip_data): ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars($ip_data['ip_address']); ?></code></td>
                        <td><?php echo $ip_data['count']; ?></td>
                        <td>
                            <?php if($ip_data['count'] > 100): ?>
                                <span style="color: #ff7675;">⚠️ Подозрительно</span>
                            <?php else: ?>
                                <span style="color: #00b894;">✅ Норма</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if(count($top_ips) == 0): ?>
                    <tr>
                        <td colspan="3" style="text-align: center; color: #999;">Нет данных за последний час</td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
        
        <div class="card">
            <h2>🔒 Активные защиты</h2>
            
            <div class="security-item">
                <strong>✅ Rate Limiting:</strong> 60 запросов в минуту на IP адрес
            </div>
            <div class="security-item">
                <strong>✅ Защита от перебора паролей:</strong> 5 попыток входа, блокировка на 5 минут
            </div>
            <div class="security-item">
                <strong>✅ CSRF защита:</strong> Токены для всех форм
            </div>
            <div class="security-item">
                <strong>✅ XSS защита:</strong> Очистка всех входных данных
            </div>
            <div class="security-item">
                <strong>✅ Логирование:</strong> Все запросы записываются в базу
            </div>
            <div class="security-item warning">
                <strong>⚠️ Рекомендация:</strong> Установите CloudFlare для дополнительной DDoS защиты
            </div>
        </div>
        
        <div class="card">
            <h2>⚙️ Управление безопасностью</h2>
            <div style="text-align: center;">
                <?php if($is_owner): ?>
                    <a href="?clear_logs=1" class="btn" onclick="return confirm('Очистить все логи? Это действие необратимо!')">🗑️ Очистить логи</a>
                    <a href="?reset_blocks=1" class="btn btn-success" onclick="return confirm('Сбросить все блокировки пользователей?')">🔄 Сбросить блокировки</a>
                <?php endif; ?>
                <a href="admin-rules.php" class="btn btn-warning">📜 Правила безопасности</a>
            </div>
        </div>
        
        <div class="card" style="text-align: center; background: rgba(255, 118, 117, 0.1); border-left: 4px solid #ff7675;">
            <h3>🚨 Экстренная информация</h3>
            <p style="color: #dfe6e9; margin: 20px 0;">
                При подозрении на атаку:<br>
                1. Проверьте топ IP адресов выше<br>
                2. При необходимости очистите логи<br>
                3. Обратитесь к хостинг-провайдеру<br>
                4. Временно включите режим обслуживания
            </p>
            <p style="color: #999; font-size: 14px;">
                Последнее обновление: <?php echo date('d.m.Y H:i:s'); ?>
            </p>
        </div>
    </div>
</body>
</html>