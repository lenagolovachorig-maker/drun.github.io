<?php
require 'config.php';

$message = '';
$success = false;

if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $salt = 'my_diary_salt_2026';
    
    // Проверки
    if (strlen($username) < 3) {
        $message = '❌ Логин должен быть не менее 3 символов';
    } elseif (strlen($password) < 6) {
        $message = '❌ Пароль должен быть не менее 6 символов';
    } elseif ($password !== $confirm_password) {
        $message = '❌ Пароли не совпадают';
    } else {
        $hashed_password = sha1($password . $salt);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->execute(array($username, $hashed_password));
            $success = true;
            $message = '✅ Регистрация успешна! Теперь можете войти.';
        } catch (Exception $e) {
            $message = '❌ Пользователь с таким именем уже существует';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Регистрация</title>
    <link rel="stylesheet" href="style.css">
    <style>
    body {
        font-family: Arial, sans-serif;
        background: rgba(0,0,0,0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
    }
    .register-modal {
        background: white;
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        width: 100%;
        max-width: 400px;
        position: relative;
    }
    .close-btn {
        position: absolute;
        top: 15px;
        right: 20px;
        font-size: 28px;
        color: #999;
        cursor: pointer;
        text-decoration: none;
    }
    .close-btn:hover {
        color: #ff7675;
    }
    h1 {
        text-align: center;
        color: #6c5ce7;
        margin-bottom: 30px;
    }
    input {
        width: 100%;
        padding: 12px;
        margin: 10px 0;
        border: 2px solid #eee;
        border-radius: 8px;
        font-size: 16px;
        box-sizing: border-box;
    }
    input:focus {
        outline: none;
        border-color: #6c5ce7;
    }
    .btn-register {
        width: 100%;
        padding: 14px;
        background: #6c5ce7;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        margin-top: 15px;
        transition: 0.3s;
    }
    .btn-register:hover {
        background: #5b4cdb;
    }
    .alert {
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 20px;
        text-align: center;
    }
    .alert-success {
        background: #00b894;
        color: white;
    }
    .alert-error {
        background: #ff7675;
        color: white;
    }
    .login-link {
        text-align: center;
        margin-top: 20px;
        color: #666;
    }
    .login-link a {
        color: #6c5ce7;
        text-decoration: none;
        font-weight: bold;
    }
    .login-link a:hover {
        text-decoration: underline;
    }
    .password-requirements {
        font-size: 12px;
        color: #999;
        margin-top: 5px;
    }
    </style>
</head>
<body>
    <div class="register-modal">
        <a href="javascript:window.close()" class="close-btn">&times;</a>
        
        <h1>📝 Регистрация</h1>
        
        <?php if($message): ?>
            <div class="alert <?php echo $success ? 'alert-success' : 'alert-error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if(!$success): ?>
        <form method="POST">
            <input type="text" name="username" placeholder="👤 Придумайте логин" required minlength="3">
            
            <input type="password" name="password" placeholder="🔒 Пароль" required minlength="6">
            <div class="password-requirements">Минимум 6 символов</div>
            
            <input type="password" name="confirm_password" placeholder="🔒 Подтвердите пароль" required>
            
            <button type="submit" name="register" class="btn-register">🚀 Зарегистрироваться</button>
        </form>
        
        <div class="login-link">
            Уже есть аккаунт? <a href="index.php">Войти</a>
        </div>
        <?php else: ?>
        <div style="text-align: center; margin-top: 30px;">
            <p style="font-size: 18px; margin-bottom: 20px;">🎉 Добро пожаловать!</p>
            <a href="index.php" class="btn-register" style="display: inline-block; text-decoration: none;">🔐 Войти в аккаунт</a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>