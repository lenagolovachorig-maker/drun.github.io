<?php
require 'antiddos.php';
require 'config.php';

$message = '';

// Защита от перебора паролей
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $salt = 'my_diary_salt_2026';
    $hashed_password = sha1($password . $salt);
    
    // Получаем IP для логирования
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Проверяем пользователя
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute(array($username));
    $user = $stmt->fetch();
    
    if ($user) {
        // Проверяем блокировку после неудачных попыток
        $last_attempt = strtotime($user['last_attempt'] ?? '0000-00-00 00:00:00');
        $now = time();
        
        if ($user['failed_attempts'] >= 5 && ($now - $last_attempt) < 300) {
            $wait_time = 300 - ($now - $last_attempt);
            $message = '🚫 Слишком много неудачных попыток! Подождите ' . ceil($wait_time/60) . ' мин.';
        } elseif ($user['password'] === $hashed_password) {
            // Успешный вход - сбрасываем счетчик
            $stmt = $pdo->prepare("UPDATE users SET failed_attempts = 0, last_attempt = NULL WHERE id = ?");
            $stmt->execute(array($user['id']));
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: diary.php');
            exit;
        } else {
            // Неудачная попытка
            $stmt = $pdo->prepare("UPDATE users SET failed_attempts = failed_attempts + 1, last_attempt = NOW() WHERE id = ?");
            $stmt->execute(array($user['id']));
            $message = '❌ Неверный логин или пароль';
        }
    } else {
        $message = '❌ Неверный логин или пароль';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Личный Дневник</title>
    <script src="theme.js"></script>
    <style>
    body {
        font-family: Arial, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        transition: background 0.5s ease;
    }
    body.dark-theme {
        background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
    }
    .container {
        background: white;
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        width: 100%;
        max-width: 400px;
        animation: slideIn 0.5s ease;
    }
    body.dark-theme .container {
        background: rgba(255, 255, 255, 0.98);
    }
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    h1 {
        text-align: center;
        color: #6c5ce7;
        margin-bottom: 30px;
        font-size: 28px;
    }
    input {
        width: 100%;
        padding: 14px;
        margin: 10px 0;
        border: 2px solid #eee;
        border-radius: 8px;
        font-size: 16px;
        box-sizing: border-box;
        transition: 0.3s;
    }
    input:focus {
        outline: none;
        border-color: #6c5ce7;
    }
    button {
        width: 100%;
        padding: 14px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        margin-top: 15px;
        transition: 0.3s;
    }
    .btn-login {
        background: #6c5ce7;
        color: white;
    }
    .btn-login:hover {
        background: #5b4cdb;
        transform: translateY(-2px);
    }
    .btn-create {
        background: #fdcb6e;
        color: #2d3436;
    }
    .btn-create:hover {
        background: #f39c12;
        transform: translateY(-2px);
    }
    .alert {
        padding: 12px;
        background: #ffeaa7;
        color: #d63031;
        border-radius: 8px;
        margin-bottom: 20px;
        text-align: center;
    }
    .alert-success {
        background: #00b894;
        color: white;
    }
    .divider {
        text-align: center;
        margin: 20px 0;
        color: #999;
        position: relative;
        font-size: 14px;
    }
    .divider::before,
    .divider::after {
        content: '';
        position: absolute;
        top: 50%;
        width: 40%;
        height: 1px;
        background: #eee;
    }
    .divider::before { left: 0; }
    .divider::after { right: 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📔 Мой Личный Дневник</h1>
        
        <?php if($message): ?>
            <div class="alert"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="text" name="username" placeholder="👤 Логин" required autocomplete="username">
            <input type="password" name="password" placeholder="🔒 Пароль" required autocomplete="current-password">
            <button type="submit" name="login" class="btn-login">🔐 Войти</button>
        </form>
        
        <div class="divider">или</div>
        
        <button onclick="openRegister()" class="btn-create">✨ Создать новый аккаунт</button>
    </div>
    
    <script>
    function openRegister() {
        window.open('register.php', 'Регистрация', 'width=450,height=600,resizable=yes,scrollbars=yes');
    }
    </script>
</body>
</html>