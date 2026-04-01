<?php
require_once 'config.php';

// Обработка выхода
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Обработка входа
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        header('Location: admin.php');
        exit;
    } else {
        $error = "Неверное имя пользователя или пароль";
    }
}

// Обработка создания/редактирования поста
if (isset($_POST['save_post'])) {
    if (!isAdmin()) {
        header('Location: admin.php');
        exit;
    }
    
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $post_type = $_POST['post_type'];
    $video_url = trim($_POST['video_url']);
    
    if (empty($title) || empty($content)) {
        $error = "Заполните заголовок и содержание";
    } else {
        if (isset($_POST['post_id']) && !empty($_POST['post_id'])) {
            // Обновление существующего поста
            $post_id = (int)$_POST['post_id'];
            $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, post_type = ?, video_url = ? WHERE id = ?");
            $stmt->execute([$title, $content, $post_type, $video_url, $post_id]);
            $message = "Пост успешно обновлен!";
        } else {
            // Создание нового поста
            $stmt = $pdo->prepare("INSERT INTO posts (title, content, post_type, video_url) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $content, $post_type, $video_url]);
            $message = "Пост успешно создан!";
        }
        
        // Очищаем форму
        $_POST = array();
    }
}

// Обработка удаления поста
if (isset($_GET['delete']) && isAdmin()) {
    $post_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    header('Location: admin.php?deleted=1');
    exit;
}

// Получаем пост для редактирования
$edit_post = null;
if (isset($_GET['edit']) && isAdmin()) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_post = $stmt->fetch();
}

// Получаем список всех постов
$stmt = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель - Портал обучения</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="container">
                <a href="index.php" class="logo">📚 Портал обучения</a>
                <div class="nav-links">
                    <a href="index.php">Главная</a>
                    <?php if (isAdmin()): ?>
                        <a href="admin.php">Админ-панель</a>
                        <a href="admin.php?logout=1" class="btn-logout">Выйти</a>
                    <?php else: ?>
                        <a href="admin.php" class="btn-login">Войти</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="container">
            <?php if (!isAdmin()): ?>
                <!-- Форма входа -->
                <div class="login-form">
                    <h2>Вход в административную панель</h2>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-error"><?php echo h($error); ?></div>
                    <?php endif; ?>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="username">Имя пользователя:</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Пароль:</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <button type="submit" name="login" class="btn btn-primary">Войти</button>
                    </form>
                </div>
            <?php else: ?>
                <!-- Админ-панель -->
                <div class="admin-panel">
                    <h1>Админ-панель</h1>
                    
                    <?php if (isset($message)): ?>
                        <div class="alert alert-success"><?php echo h($message); ?></div>
                    <?php endif; ?>
                    <?php if (isset($_GET['deleted'])): ?>
                        <div class="alert alert-success">Пост успешно удален!</div>
                    <?php endif; ?>
                    
                    <!-- Форма создания/редактирования поста -->
                    <div class="post-form">
                        <h2><?php echo $edit_post ? 'Редактировать пост' : 'Создать новый пост'; ?></h2>
                        <form method="POST" action="">
                            <?php if ($edit_post): ?>
                                <input type="hidden" name="post_id" value="<?php echo $edit_post['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label for="title">Заголовок:</label>
                                <input type="text" id="title" name="title" required 
                                       value="<?php echo $edit_post ? h($edit_post['title']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="post_type">Тип поста:</label>
                                <select id="post_type" name="post_type" onchange="toggleVideoUrl()">
                                    <option value="text" <?php echo $edit_post && $edit_post['post_type'] === 'text' ? 'selected' : ''; ?>>Текстовый</option>
                                    <option value="video" <?php echo $edit_post && $edit_post['post_type'] === 'video' ? 'selected' : ''; ?>>Видео</option>
                                </select>
                            </div>
                            
                            <div class="form-group" id="video_url_group" style="display: <?php echo $edit_post && $edit_post['post_type'] === 'video' ? 'block' : 'none'; ?>">
                                <label for="video_url">Ссылка на видео (iframe embed):</label>
                                <input type="text" id="video_url" name="video_url" 
                                       placeholder="https://www.youtube.com/embed/..." 
                                       value="<?php echo $edit_post ? h($edit_post['video_url']) : ''; ?>">
                                <small>Вставьте ссылку для встраивания видео (например, из YouTube: https://www.youtube.com/embed/VIDEO_ID)</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="content">Содержание:</label>
                                <textarea id="content" name="content" rows="15" required><?php echo $edit_post ? h($edit_post['content']) : ''; ?></textarea>
                                <small>Поддерживается HTML разметка</small>
                            </div>
                            
                            <button type="submit" name="save_post" class="btn btn-primary">
                                <?php echo $edit_post ? 'Обновить пост' : 'Создать пост'; ?>
                            </button>
                            
                            <?php if ($edit_post): ?>
                                <a href="admin.php" class="btn btn-secondary">Отмена</a>
                            <?php endif; ?>
                        </form>
                    </div>
                    
                    <!-- Список всех постов -->
                    <div class="posts-list">
                        <h2>Все посты</h2>
                        <table class="posts-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Заголовок</th>
                                    <th>Тип</th>
                                    <th>Дата создания</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($posts as $post): ?>
                                <tr>
                                    <td><?php echo $post['id']; ?></td>
                                    <td><?php echo h($post['title']); ?></td>
                                    <td><?php echo $post['post_type'] === 'video' ? 'Видео' : 'Текст'; ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($post['created_at'])); ?></td>
                                    <td class="actions">
                                        <a href="post.php?id=<?php echo $post['id']; ?>" class="btn-sm btn-view" target="_blank">👁️ Просмотр</a>
                                        <a href="admin.php?edit=<?php echo $post['id']; ?>" class="btn-sm btn-edit">✏️ Редактировать</a>
                                        <a href="admin.php?delete=<?php echo $post['id']; ?>" class="btn-sm btn-delete" onclick="return confirm('Удалить этот пост?')">🗑️ Удалить</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <script>
                function toggleVideoUrl() {
                    var postType = document.getElementById('post_type').value;
                    var videoUrlGroup = document.getElementById('video_url_group');
                    if (postType === 'video') {
                        videoUrlGroup.style.display = 'block';
                    } else {
                        videoUrlGroup.style.display = 'none';
                    }
                }
                </script>
            <?php endif; ?>
        </div>
    </main>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Портал обучения. Все права защищены.</p>
        </div>
    </footer>
</body>
</html>