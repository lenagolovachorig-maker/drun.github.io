<?php
/**
 * Anti-DDoS и защита
 */

session_start();

function getRealIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

$ip = getRealIP();
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
$page_url = $_SERVER['REQUEST_URI'] ?? 'Unknown';

try {
    require_once 'config.php';
    
    // Логирование
    $stmt = $pdo->prepare("INSERT INTO access_logs (ip_address, user_agent, page_url) VALUES (?, ?, ?)");
    $stmt->execute(array($ip, $user_agent, $page_url));
    
    // Rate Limiting (60 запросов в минуту)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM access_logs WHERE ip_address = ? AND access_time > DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
    $stmt->execute(array($ip));
    $request_count = $stmt->fetch()['count'];
    
    if ($request_count > 60) {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(429);
            die('🚫 Слишком много запросов!');
        }
    }
    
    // Очистка старых логов
    $pdo->exec("DELETE FROM access_logs WHERE access_time < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    
} catch(Exception $e) {
    // Игнорируем ошибки
}

// CSRF токен
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function getCSRFToken() {
    return $_SESSION['csrf_token'];
}

function checkCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function cleanInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
?>