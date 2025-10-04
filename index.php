<?php
// index.php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch posts with like and comment counts
$stmt = $pdo->prepare("
    SELECT posts.*, users.first_name, users.last_name,
           (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) as like_count,
           (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) as user_liked
    FROM posts 
    JOIN users ON posts.user_id = users.id 
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$posts = $stmt->fetchAll();

// Fetch comments for each post
$comments = [];
foreach ($posts as $post) {
    $stmt = $pdo->prepare("
        SELECT comments.*, users.first_name, users.last_name 
        FROM comments 
        JOIN users ON comments.user_id = users.id 
        WHERE comments.post_id = ? 
        ORDER BY comments.created_at
    ");
    $stmt->execute([$post['id']]);
    $comments[$post['id']] = $stmt->fetchAll();
}

// Fetch trending jobs
$stmt = $pdo->query("SELECT * FROM jobs ORDER BY created_at DESC LIMIT 5");
$jobs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LinkedIn Clone - Homepage</title>
    <style>
        :root {
            --primary: #0073b1;
            --secondary: #00a0dc;
            --card-bg: rgba(255, 255, 255, 0.8);
            --shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            --neumorphic-shadow: 5px 5px 10px #d9d9d9, -5px -5px 10px #ffffff;
            --font: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            font-family: var(--font);
            margin: 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
            overflow-x: hidden;
        }

        .navbar {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            margin: 0 20px;
            font-size: 16px;
            position: relative;
            transition: color 0.3s ease;
        }

        .navbar a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            background: white;
            bottom: -5px;
            left: 0;
            transition: width 0.3s ease;
        }

        .navbar a:hover::after {
            width: 100%;
        }

        .container {
            display: grid;
            grid-template-columns: 1fr 2fr 1fr;
            gap: 20px;
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .sidebar, .main-content, .jobs-sidebar {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease;
        }

        .sidebar:hover, .jobs-sidebar:hover {
            transform: translateY(-5px);
        }

        .post {
            background: var(--card-bg);
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 12px;
            box-shadow: var(--neumorphic-shadow);
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .post:hover {
            transform: scale(1.02);
        }

        .post-form textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            resize: none;
            transition: border-color 0.3s ease;
        }

        .post-form textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 5px rgba(0, 115, 177, 0.5);
        }

        .post-form button {
            background: var(--primary);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            box-shadow: var(--neumorphic-shadow);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .post-form button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
        }

        .post-form button:hover::before {
            width: 200px;
            height: 200px;
        }

        .post-form button:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }

        .job {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            transition: background 0.3s ease;
        }

        .job:hover {
            background: rgba(0, 115, 177, 0.1);
        }

        .job h4::before {
            content: '💼';
            margin-right: 10px;
        }

        .post-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .like-button, .comment-button {
            background: none;
            border: 1px solid var(--primary);
            color: var(--primary);
            padding: 8px 16px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .like-button.liked {
            background: var(--primary);
            color: white;
        }

        .like-button:hover, .comment-button:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        .like-button::before, .comment-button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
        }

        .like-button:hover::before, .comment-button:hover::before {
            width: 150px;
            height: 150px;
        }

        .comment-form {
            margin-top: 10px;
            display: flex;
            gap: 10px;
        }

        .comment-form textarea {
            flex: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            resize: none;
            transition: border-color 0.3s ease;
        }

        .comment-form textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 5px rgba(0, 115, 177, 0.5);
            outline: none;
        }

        .comment {
            background: #f5f7fa;
            padding: 10px;
            margin-top: 10px;
            border-radius: 8px;
            animation: slideInComment 0.3s ease;
        }

        @keyframes slideInComment {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .comment p {
            margin: 0;
        }

        .comment small {
            font-size: 12px;
            opacity: 0.7;
        }

        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }

        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }
            .sidebar, .main-content, .jobs-sidebar {
                width: 100%;
            }
            .post-actions {
                flex-direction: column;
            }
            .comment-form {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div>
            <a href="index.php">Home</a>
            <a href="profile.php">Profile</a>
            <a href="connections.php">Connections</a>
            <a href="jobs.php">Jobs</a>
            <a href="messages.php">Messages</a>
        </div>
        <a href="logout.php">Logout</a>
    </div>
    <div class="container">
        <div class="sidebar">
            <h3>Welcome, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
            <p><a href="profile.php">View Profile</a></p>
        </div>
        <div class="main-content">
            <div class="post-form">
                <form action="post_submit.php" method="POST">
                    <textarea name="content" placeholder="Share an update..." required></textarea>
                    <button type="submit">Post</button>
                </form>
            </div>
            <?php foreach ($posts as $post): ?>
                <div class="post">
                    <h4><?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?></h4>
                    <p><?php echo htmlspecialchars($post['content']); ?></p>
                    <p><small><?php echo $post['created_at']; ?></small></p>
                    <div class="post-actions">
                        <form action="like_post.php" method="POST">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <button type="submit" class="like-button <?php echo $post['user_liked'] ? 'liked' : ''; ?>">
                                Like (<?php echo $post['like_count']; ?>)
                            </button>
                        </form>
                        <button class="comment-button" onclick="document.getElementById('comment-form-<?php echo $post['id']; ?>').style.display='flex';">Comment</button>
                    </div>
                    <div class="comment-form" id="comment-form-<?php echo $post['id']; ?>" style="display:none;">
                        <form action="comment_post.php" method="POST">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <textarea name="content" placeholder="Write a comment..." required></textarea>
                            <button type="submit">Post Comment</button>
                        </form>
                    </div>
                    <?php if (!empty($comments[$post['id']])): ?>
                        <?php foreach ($comments[$post['id']] as $comment): ?>
                            <div class="comment">
                                <p><strong><?php echo htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']); ?>:</strong> <?php echo htmlspecialchars($comment['content']); ?></p>
                                <p><small><?php echo $comment['created_at']; ?></small></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="jobs-sidebar">
            <h3>Trending Jobs</h3>
            <?php foreach ($jobs as $job): ?>
                <div class="job">
                    <h4><?php echo htmlspecialchars($job['title']); ?></h4>
                    <p><?php echo htmlspecialchars($job['company']); ?> - <?php echo htmlspecialchars($job['location']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <script>
        function redirectTo(page) {
            window.location.href = page;
        }
    </script>
</body>
</html>
