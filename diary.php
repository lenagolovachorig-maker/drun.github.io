<?php
require 'antiddos.php';
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// Добавление записи
if (isset($_POST['add_entry'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $tags = trim($_POST['tags']);
    $is_private = isset($_POST['is_private']) ? 1 : 0;

    $stmt = $pdo->prepare("INSERT INTO entries (user_id, title, content, tags, is_private) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(array($user_id, $title, $content, $tags, $is_private));
    $entry_id = $pdo->lastInsertId();
    
    // Загрузка файла
    if (!empty($_FILES['attachment']['name'])) {
        $allowed = ['jpg','jpeg','png','gif','pdf','doc','docx','txt'];
        $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed) && $_FILES['attachment']['size'] < 5242880) {
            $filename = 'file_' . $entry_id . '_' . time() . '.' . $ext;
            $filepath = 'attachments/' . $filename;
            if (!file_exists('attachments')) mkdir('attachments', 0777, true);
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $filepath)) {
                $stmt = $pdo->prepare("INSERT INTO attachments (entry_id, filename, filepath, filesize) VALUES (?, ?, ?, ?)");
                $stmt->execute(array($entry_id, $_FILES['attachment']['name'], $filepath, $_FILES['attachment']['size']));
            }
        }
    }
    $message = '✅ Запись сохранена!';
}

// Удаление записи
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM entries WHERE id = ? AND user_id = ?");
    $stmt->execute(array($id, $user_id));
    header('Location: diary.php');
    exit;
}

// Лайк
if (isset($_GET['like'])) {
    $entry_id = (int)$_GET['like'];
    $stmt = $pdo->prepare("SELECT * FROM likes WHERE user_id = ? AND entry_id = ?");
    $stmt->execute(array($user_id, $entry_id));
    
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND entry_id = ?");
        $stmt->execute(array($user_id, $entry_id));
    } else {
        $stmt = $pdo->prepare("DELETE FROM dislikes WHERE user_id = ? AND entry_id = ?");
        $stmt->execute(array($user_id, $entry_id));
        $stmt = $pdo->prepare("INSERT INTO likes (user_id, entry_id) VALUES (?, ?)");
        $stmt->execute(array($user_id, $entry_id));
    }
    header('Location: diary.php');
    exit;
}

// Дизлайк
if (isset($_GET['dislike'])) {
    $entry_id = (int)$_GET['dislike'];
    $stmt = $pdo->prepare("SELECT * FROM dislikes WHERE user_id = ? AND entry_id = ?");
    $stmt->execute(array($user_id, $entry_id));
    
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("DELETE FROM dislikes WHERE user_id = ? AND entry_id = ?");
        $stmt->execute(array($user_id, $entry_id));
    } else {
        $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND entry_id = ?");
        $stmt->execute(array($user_id, $entry_id));
        $stmt = $pdo->prepare("INSERT INTO dislikes (user_id, entry_id) VALUES (?, ?)");
        $stmt->execute(array($user_id, $entry_id));
    }
    header('Location: diary.php');
    exit;
}

// Добавление комментария
if (isset($_POST['add_comment'])) {
    $entry_id = (int)$_POST['entry_id'];
    $content = trim($_POST['content']);
    
    if (!empty($content)) {
        $stmt = $pdo->prepare("INSERT INTO comments (user_id, entry_id, content) VALUES (?, ?, ?)");
        $stmt->execute(array($user_id, $entry_id, $content));
    }
    header('Location: diary.php#comments');
    exit;
}

// Проверка роли пользователя
$stmt = $pdo->prepare("SELECT is_owner, is_admin FROM users WHERE id = ?");
$stmt->execute(array($user_id));
$user_role = $stmt->fetch();

// Поиск
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT entries.*, users.username, users.avatar, users.unique_id 
        FROM entries 
        JOIN users ON entries.user_id = users.id 
        WHERE entries.user_id = ? OR entries.is_private = 0";
$params = array($user_id);

if ($search) {
    $sql .= " AND (entries.title LIKE ? OR entries.content LIKE ? OR entries.tags LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, array($searchParam, $searchParam, $searchParam));
}

$sql .= " ORDER BY entries.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$entries = $stmt->fetchAll();

// Получаем мои лайки и дизлайки
$stmt = $pdo->prepare("SELECT entry_id FROM likes WHERE user_id = ?");
$stmt->execute(array($user_id));
$my_likes = array_column($stmt->fetchAll(), 'entry_id');

$stmt = $pdo->prepare("SELECT entry_id FROM dislikes WHERE user_id = ?");
$stmt->execute(array($user_id));
$my_dislikes = array_column($stmt->fetchAll(), 'entry_id');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Дневник</title>
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
        box-shadow: 0 6px 20px rgba(0,0,0,0.3);
    }
    body.dark-theme .theme-toggle-btn {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
    }
    .container {
        max-width: 800px;
        margin: 0 auto;
        padding: 100px 20px 40px;
    }
    .nav-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding: 20px;
        background: rgba(255, 255, 255, 0.95);
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    body.dark-theme .nav-bar {
        background: rgba(255, 255, 255, 0.98);
    }
    .nav-bar h2 {
        color: #6c5ce7;
        margin: 0;
    }
    .nav-buttons {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }
    .btn-small {
        padding: 10px 20px;
        background: #6c5ce7;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-size: 14px;
        transition: 0.3s;
        display: inline-block;
    }
    .btn-small:hover {
        background: #5b4cdb;
        transform: translateY(-2px);
    }
    .card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    body.dark-theme .card {
        background: rgba(255, 255, 255, 0.98);
    }
    input, textarea {
        width: 100%;
        padding: 12px;
        margin: 10px 0;
        border: 2px solid #eee;
        border-radius: 8px;
        font-size: 16px;
        box-sizing: border-box;
        font-family: Arial, sans-serif;
    }
    input:focus, textarea:focus {
        outline: none;
        border-color: #6c5ce7;
    }
    button[type="submit"] {
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
    button[type="submit"]:hover {
        background: #5b4cdb;
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
    .entry-meta {
        font-size: 14px;
        color: #999;
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        flex-wrap: wrap;
        gap: 10px;
    }
    .tag {
        background: #a29bfe;
        color: white;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
        margin-right: 5px;
        display: inline-block;
    }
    .comment {
        display: flex;
        gap: 12px;
        margin-bottom: 15px;
        padding: 12px;
        background: rgba(0,0,0,0.02);
        border-radius: 10px;
    }
    body.dark-theme .comment {
        background: rgba(255,255,255,0.05);
    }
    .like-btn {
        background: none;
        border: none;
        cursor: pointer;
        font-size: 20px;
        padding: 5px 10px;
        transition: 0.3s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        color: inherit;
    }
    .like-btn:hover {
        transform: scale(1.2);
    }
    .like-btn.liked {
        color: #ff7675;
    }
    .like-btn.disliked {
        color: #636e72;
    }
    .search-form {
        display: flex;
        gap: 10px;
        padding: 15px;
    }
    .search-form input {
        margin: 0;
        flex: 1;
    }
    .search-form button {
        width: auto;
    }
    .attachment {
        background: #f0f0f0;
        padding: 10px;
        border-radius: 8px;
        margin-top: 10px;
        display: inline-block;
    }
    body.dark-theme .attachment {
        background: #2d3436;
    }
    .attachment a {
        color: #6c5ce7;
        text-decoration: none;
    }
    </style>
    <script>
    function togglePrivate() {
        var checkbox = document.getElementById('privateCheck');
        var label = document.getElementById('privateLabel');
        if(checkbox.checked) {
            label.innerText = '🔒 Запись скрыта';
            label.style.color = '#ff7675';
        } else {
            label.innerText = '🌍 Запись публична';
            label.style.color = '#2d3436';
        }
    }
    </script>
</head>
<body>
    <button class="theme-toggle-btn" onclick="toggleTheme()" title="Сменить тему">
        <span id="theme-icon">🌙</span>
    </button>
    
    <div class="container">
        <div class="nav-bar">
            <h2>Привет, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
            <div class="nav-buttons">
                <a href="messages.php" class="btn-small">📬 Сообщения</a>
                <?php if($user_role['is_owner']): ?>
                    <a href="owner-panel.php" class="btn-small" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">👑 Владелец</a>
                    <a href="security-panel.php" class="btn-small" style="background: #ff7675;">🛡️ Защита</a>
                <?php elseif($user_role['is_admin']): ?>
                    <a href="admin.php" class="btn-small" style="background: #ff7675;">👔 Админка</a>
                    <a href="security-panel.php" class="btn-small" style="background: #ff7675;">🛡️ Защита</a>
                <?php endif; ?>
                <a href="profile.php" class="btn-small">👤 Профиль</a>
                <a href="logout.php" class="btn-small" style="background: #ff7675;">Выйти</a>
            </div>
        </div>

        <div class="card">
            <h3>✏️ Новая запись</h3>
            <?php if($message): ?>
                <div class="alert"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="text" name="title" placeholder="Заголовок..." required>
                <textarea name="content" rows="5" placeholder="О чем думаешь сегодня?" required></textarea>
                <input type="text" name="tags" placeholder="Теги (через запятую): учеба, код, жизнь">
                
                <div style="margin: 10px 0;">
                    <label style="display: block; margin-bottom: 5px; color: #666;">📎 Прикрепить файл:</label>
                    <input type="file" name="attachment" style="width: auto;">
                    <small style="color: #999;">Макс. размер: 5MB (jpg, png, pdf, doc)</small>
                </div>
                
                <div style="margin: 10px 0;">
                    <input type="checkbox" name="is_private" id="privateCheck" onchange="togglePrivate()" style="width: auto;">
                    <label for="privateCheck" id="privateLabel">🌍 Запись публична</label>
                </div>

                <button type="submit" name="add_entry">Сохранить запись</button>
            </form>
        </div>

        <div class="card search-form">
            <form method="GET" style="display: flex; gap: 10px; width: 100%;">
                <input type="text" name="search" placeholder="🔍 Поиск по записям..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn-small">Найти</button>
                <?php if($search): ?>
                <a href="diary.php" class="btn-small">Сброс</a>
                <?php endif; ?>
            </form>
        </div>

        <?php foreach ($entries as $entry): 
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM likes WHERE entry_id = ?");
            $stmt->execute(array($entry['id']));
            $likes_count = $stmt->fetch()['count'];
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM dislikes WHERE entry_id = ?");
            $stmt->execute(array($entry['id']));
            $dislikes_count = $stmt->fetch()['count'];
            
            $stmt = $pdo->prepare("SELECT comments.*, users.username, users.avatar 
                                   FROM comments 
                                   JOIN users ON comments.user_id = users.id 
                                   WHERE comments.entry_id = ? 
                                   ORDER BY comments.created_at ASC");
            $stmt->execute(array($entry['id']));
            $comments = $stmt->fetchAll();
            
            $is_liked = in_array($entry['id'], $my_likes);
            $is_disliked = in_array($entry['id'], $my_dislikes);
            
            $stmt = $pdo->prepare("SELECT * FROM attachments WHERE entry_id = ?");
            $stmt->execute(array($entry['id']));
            $attachments = $stmt->fetchAll();
        ?>
        <div class="card">
            <div class="entry-meta">
                <span>📅 <?php echo date('d.m.Y H:i', strtotime($entry['created_at'])); ?></span>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <?php if($entry['avatar'] && file_exists($entry['avatar'])): ?>
                        <img src="<?php echo htmlspecialchars($entry['avatar']); ?>" 
                             style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 2px solid #6c5ce7;" 
                             alt="">
                    <?php else: ?>
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #6c5ce7, #a29bfe); display: inline-flex; align-items: center; justify-content: center; color: white; font-size: 14px; font-weight: bold;">
                            <?php echo strtoupper(substr($entry['username'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <span>
                        👤 <?php echo htmlspecialchars($entry['username']); ?> 
                        <span style="color: #999; font-size: 11px; font-family: monospace;">
                            [<?php echo htmlspecialchars($entry['unique_id'] ?? 'N/A'); ?>]
                        </span>
                    </span>
                    
                    <?php if($entry['is_private']): ?>
                        <span>🔒</span>
                    <?php endif; ?>
                    
                    <?php if($entry['user_id'] == $user_id): ?>
                        <a href="?delete=<?php echo $entry['id']; ?>" onclick="return confirm('Удалить эту запись?')" style="color: #ff7675; text-decoration: none;">✕</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <h3><?php echo htmlspecialchars($entry['title']); ?></h3>
            <p style="white-space: pre-wrap; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($entry['content'])); ?></p>
            
            <?php if(count($attachments) > 0): ?>
                <div style="margin-top: 15px;">
                    <?php foreach($attachments as $att): ?>
                        <div class="attachment">
                            📎 <a href="<?php echo htmlspecialchars($att['filepath']); ?>" target="_blank">
                                <?php echo htmlspecialchars($att['filename']); ?>
                            </a>
                            <small style="color: #999;">(<?php echo round($att['filesize']/1024, 1); ?> KB)</small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if($entry['tags']): ?>
                <div style="margin-top: 15px;">
                    <?php foreach(explode(',', $entry['tags']) as $tag): ?>
                        <span class="tag">#<?php echo htmlspecialchars(trim($tag)); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee; display: flex; gap: 15px;">
                <a href="?like=<?php echo $entry['id']; ?>" class="like-btn <?php echo $is_liked ? 'liked' : ''; ?>">
                    <?php echo $is_liked ? '❤️' : '🤍'; ?> <span><?php echo $likes_count; ?></span>
                </a>
                
                <a href="?dislike=<?php echo $entry['id']; ?>" class="like-btn <?php echo $is_disliked ? 'disliked' : ''; ?>">
                    <?php echo $is_disliked ? '👎' : '👍'; ?> <span><?php echo $dislikes_count; ?></span>
                </a>
            </div>
            
            <div style="margin-top: 20px;" id="comments">
                <h4>💬 Комментарии (<?php echo count($comments); ?>)</h4>
                
                <?php foreach($comments as $comment): ?>
                    <div class="comment">
                        <div>
                            <?php if($comment['avatar'] && file_exists($comment['avatar'])): ?>
                                <img src="<?php echo htmlspecialchars($comment['avatar']); ?>" 
                                     style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #6c5ce7;" 
                                     alt="">
                            <?php else: ?>
                                <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #6c5ce7, #a29bfe); display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; font-weight: bold;">
                                    <?php echo strtoupper(substr($comment['username'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                                <strong style="color: #6c5ce7;"><?php echo htmlspecialchars($comment['username']); ?></strong>
                                <span style="color: #999; font-size: 12px;">
                                    <?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?>
                                </span>
                            </div>
                            <p style="margin: 0; line-height: 1.5;"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <form method="POST" class="comment-form" style="margin-top: 15px;">
                    <input type="hidden" name="entry_id" value="<?php echo $entry['id']; ?>">
                    <textarea name="content" rows="2" placeholder="Написать комментарий..." required style="margin-bottom: 10px;"></textarea>
                    <button type="submit" name="add_comment" class="btn-small" style="width: auto;">📤 Отправить</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if(count($entries) == 0): ?>
            <div style="text-align: center; color: #b2bec3; padding: 40px;">
                📭 Записей пока нет. Напишите что-нибудь!
            </div>
        <?php endif; ?>
    </div>
</body>
</html>