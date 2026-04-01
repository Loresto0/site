<?php
require_once 'config.php';

// Получаем все посты из базы данных
$stmt = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Портал обучения</title>
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
            <div class="hero">
                <h1>Добро пожаловать в портал обучения!</h1>
                <p>Здесь вы найдете полезные материалы, видеоуроки и статьи для саморазвития</p>
            </div>

            <div class="posts-grid">
                <?php if (count($posts) > 0): ?>
                    <?php foreach ($posts as $post): ?>
                        <article class="post-card">
                            <div class="post-type-badge <?php echo $post['post_type']; ?>">
                                <?php echo $post['post_type'] === 'video' ? '🎬 Видео' : '📝 Текст'; ?>
                            </div>
                            
                            <h2 class="post-title">
                                <a href="post.php?id=<?php echo $post['id']; ?>">
                                    <?php echo h($post['title']); ?>
                                </a>
                            </h2>
                            
                            <div class="post-meta">
                                <span>📅 <?php echo date('d.m.Y H:i', strtotime($post['created_at'])); ?></span>
                            </div>
                            
                            <div class="post-excerpt">
                                <?php 
                                if ($post['post_type'] === 'video' && $post['video_url']) {
                                    echo '<div class="video-thumbnail">🎥 Видеоурок доступен к просмотру</div>';
                                } else {
                                    echo h(truncate(strip_tags($post['content']), 150));
                                }
                                ?>
                            </div>
                            
                            <a href="post.php?id=<?php echo $post['id']; ?>" class="read-more">
                                Читать далее →
                            </a>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-posts">
                        <p>Пока нет постов. <?php if (isAdmin()): ?>Начните добавлять материалы!<?php endif; ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Портал обучения. Все права защищены.</p>
        </div>
    </footer>
</body>
</html>