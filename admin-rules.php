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
$user = $stmt->fetch();

if (!$user['is_admin'] && !$user['is_owner']) {
    die('<h1 style="color: #ff7675; text-align: center; margin-top: 50px;">❌ ДОСТУП ЗАПРЕЩЁН!<br>Эта страница только для АДМИНИСТРАЦИИ</h1>');
}

$is_owner = $user['is_owner'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>📜 Правила Администрации</title>
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
        padding: 40px;
        border-radius: 20px;
        margin-bottom: 30px;
        text-align: center;
    }
    .header h1 {
        font-size: 36px;
        margin-bottom: 10px;
    }
    .warning {
        background: rgba(255,255,255,0.2);
        padding: 15px;
        border-radius: 10px;
        margin-top: 20px;
        font-weight: bold;
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
        color: #a29bfe;
        margin-bottom: 20px;
        font-size: 28px;
        border-bottom: 2px solid #6c5ce7;
        padding-bottom: 10px;
    }
    .rule-item {
        background: rgba(0,0,0,0.3);
        padding: 20px;
        border-radius: 10px;
        margin: 15px 0;
        border-left: 4px solid #6c5ce7;
    }
    .rule-item.critical {
        border-left-color: #ff7675;
        background: rgba(255, 118, 117, 0.1);
    }
    .rule-item.warning {
        border-left-color: #fdcb6e;
        background: rgba(253, 203, 110, 0.1);
    }
    .rule-item.success {
        border-left-color: #00b894;
        background: rgba(0, 184, 148, 0.1);
    }
    .rule-title {
        font-size: 18px;
        font-weight: bold;
        color: #a29bfe;
        margin-bottom: 10px;
    }
    .rule-content {
        color: #dfe6e9;
        line-height: 1.8;
    }
    .btn {
        padding: 12px 25px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
        font-weight: bold;
        text-decoration: none;
        display: inline-block;
        margin: 5px;
        transition: 0.3s;
    }
    .btn-primary {
        background: #6c5ce7;
        color: white;
    }
    .btn-warning {
        background: #fdcb6e;
        color: #2d3436;
    }
    .nav-bar {
        background: rgba(255,255,255,0.05);
        padding: 20px;
        border-radius: 15px;
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    .secret-notice {
        background: linear-gradient(135deg, #ff7675 0%, #d63031 100%);
        padding: 20px;
        border-radius: 10px;
        text-align: center;
        margin-bottom: 20px;
        font-weight: bold;
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.8; }
    }
    </style>
</head>
<body>
    <button class="theme-toggle-btn" onclick="toggleTheme()">
        <span id="theme-icon">🌙</span>
    </button>
    
    <div class="container">
        <div class="nav-bar">
            <div>
                <h2 style="color: #a29bfe; margin: 0;">📜 Правила Администрации</h2>
                <p style="color: #999; margin: 5px 0 0 0;">
                    <?php echo $is_owner ? '👑 ВЛАДЕЛЕЦ' : '👔 АДМИН'; ?>: 
                    <?php echo htmlspecialchars($user['username']); ?>
                </p>
            </div>
            <div>
                <?php if($is_owner): ?>
                    <a href="owner-panel.php" class="btn btn-primary">👑 Панель Владельца</a>
                <?php else: ?>
                    <a href="admin.php" class="btn btn-primary">👔 Админ-панель</a>
                <?php endif; ?>
                <a href="diary.php" class="btn" style="background: rgba(255,255,255,0.1); color: white;">← К дневнику</a>
            </div>
        </div>
        
        <div class="header">
            <h1>🔐 СЕКРЕТНЫЙ РАЗДЕЛ АДМИНИСТРАЦИИ</h1>
            <p style="font-size: 18px; opacity: 0.9;">Внутренние правила и регламенты для модерации проекта</p>
            <div class="warning">
                ⚠️ ВНИМАНИЕ: Эта страница видна ТОЛЬКО администрации! Не разглашайте информацию!
            </div>
        </div>
        
        <div class="secret-notice">
            🚨 ДОСТУП РАЗРЕШЁН ТОЛЬКО ПОЛЬЗОВАТЕЛЯМ С ПРАВАМИ АДМИНИСТРАТОРА ИЛИ ВЛАДЕЛЬЦА
        </div>
        
        <div class="card">
            <h2>📋 1. ОБЩИЕ ПОЛОЖЕНИЯ</h2>
            
            <div class="rule-item success">
                <div class="rule-title">🎯 Цель администрации</div>
                <div class="rule-content">
                    Поддержание порядка, безопасности и комфортной атмосферы для всех пользователей проекта. 
                    Администрация действует в интересах сообщества и обязана следовать данным правилам.
                </div>
            </div>
            
            <div class="rule-item">
                <div class="rule-title">⚖️ Принципы модерации</div>
                <div class="rule-content">
                    • Справедливость — одинаковые правила для всех<br>
                    • Прозрачность — действия должны быть обоснованы<br>
                    • Беспристрастность — без личных предпочтений<br>
                    • Конфиденциальность — не разглашать данные пользователей
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2>🗑️ 2. УДАЛЕНИЕ ПРОФИЛЕЙ ПОЛЬЗОВАТЕЛЕЙ</h2>
            
            <div class="rule-item critical">
                <div class="rule-title">❗ Основания для удаления профиля:</div>
                <div class="rule-content">
                    <strong>Удаление профиля — КРАЙНЯЯ МЕРА!</strong> Применяется только в случаях:
                    <ul style="margin: 15px 0; padding-left: 20px;">
                        <li>Нарушение законодательства РФ</li>
                        <li>Распространение запрещённого контента</li>
                        <li>Спам и массовая рассылка рекламы</li>
                        <li>Оскорбления администрации после предупреждений</li>
                        <li>Создание фейковых аккаунтов для обхода бана</li>
                        <li>Взлом чужих аккаунтов</li>
                    </ul>
                </div>
            </div>
            
            <div class="rule-item warning">
                <div class="rule-title">⚠️ Процедура удаления:</div>
                <div class="rule-content">
                    <strong>Шаг 1:</strong> Вынести официальное предупреждение пользователю<br>
                    <strong>Шаг 2:</strong> Подождать 24 часа (кроме критических нарушений)<br>
                    <strong>Шаг 3:</strong> Собрать доказательства нарушений<br>
                    <strong>Шаг 4:</strong> Согласовать с ВЛАДЕЛЬЦЕМ (обязательно!)<br>
                    <strong>Шаг 5:</strong> Удалить профиль и сохранить архив доказательств
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2>📝 3. УДАЛЕНИЕ ЗАПИСЕЙ</h2>
            
            <div class="rule-item">
                <div class="rule-title">📌 Основания для удаления записей:</div>
                <div class="rule-content">
                    <strong>Оскорбления:</strong> Удаление + предупреждение<br>
                    <strong>Спам / Реклама:</strong> Удаление + бан 7 дней<br>
                    <strong>Запрещённый контент:</strong> Удаление + перманентный бан<br>
                    <strong>Личная информация других лиц:</strong> Удаление + предупреждение<br>
                    <strong>Порнография / 18+ контент:</strong> Удаление + перманентный бан
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2>🔒 4. РАБОТА С ПРИВАТНЫМИ ЗАПИСЯМИ</h2>
            
            <div class="rule-item critical">
                <div class="rule-title">⛔ ВАЖНО: Приватные записи</div>
                <div class="rule-content">
                    <strong>Администрация МОЖЕТ видеть приватные записи, но НЕ ИМЕЕТ ПРАВА:</strong>
                    <ul style="margin: 15px 0; padding-left: 20px;">
                        <li>Публиковать содержимое приватных записей</li>
                        <li>Передавать информацию третьим лицам</li>
                        <li>Использовать информацию в личных целях</li>
                        <li>Удалять без веских оснований</li>
                    </ul>
                    <strong>Исключение:</strong> При получении официальной жалобы или подозрении на нарушение закона.
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2>🎯 5. ЭТИКА АДМИНИСТРАТОРА</h2>
            
            <div class="rule-item success">
                <div class="rule-title">✅ Заповеди администратора:</div>
                <div class="rule-content">
                    1. Не злоупотребляй властью<br>
                    2. Будь вежлив с пользователями<br>
                    3. Не принимай решения на эмоциях<br>
                    4. Консультируйся с коллегами в сложных случаях<br>
                    5. Признавай свои ошибки<br>
                    6. Не разглашай внутреннюю информацию<br>
                    7. Будь примером для сообщества<br>
                    8. Помни: ты здесь чтобы помогать, а не вредить
                </div>
            </div>
        </div>
        
        <div class="card" style="text-align: center; background: linear-gradient(135deg, rgba(108, 92, 231, 0.2) 0%, rgba(162, 155, 254, 0.2) 100%);">
            <h2>📞 Контакты для связи</h2>
            <p style="color: #dfe6e9; margin: 20px 0;">
                По всем вопросам обращайтесь к ВЛАДЕЛЬЦУ проекта:<br>
                <strong style="color: #a29bfe; font-size: 18px;">👑 LayZ</strong>
            </p>
            <p style="color: #999; font-size: 14px;">
                Последнее обновление правил: <?php echo date('d.m.Y'); ?><br>
                Версия документа: 1.0
            </p>
        </div>
    </div>
</body>
</html>