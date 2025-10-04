<?php
// comment_post.php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = $_POST['post_id'] ?? null;
    $content = trim($_POST['content'] ?? '');

    if (!$post_id || !$content) {
        $error = "Post ID and comment content are required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$post_id, $user_id, $content]);
            echo "<script>window.location.href='index.php';</script>";
            exit;
        } catch (PDOException $e) {
            $error = "Error posting comment: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - LinkedIn Clone</title>
    <style>
        :root {
            --primary: #0073b1;
            --secondary: #00a0dc;
            --card-bg: rgba(255, 255, 255, 0.8);
            --shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            --font: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            font-family: var(--font);
            margin: 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #333;
        }

        .error-container {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            text-align: center;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .error {
            color: #ff6b6b;
            font-size: 18px;
            margin-bottom: 20px;
            animation: shake 0.3s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        a {
            color: var(--primary);
            text-decoration: none;
            font-size: 16px;
            position: relative;
            transition: color 0.3s ease;
        }

        a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            background: var(--primary);
            bottom: -2px;
            left: 0;
            transition: width 0.3s ease;
        }

        a:hover::after {
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <p><a href="index.php">Go back to homepage</a></p>
    </div>
</body>
</html>
