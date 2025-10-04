<?php
// messages.php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receiver_id'])) {
    $receiver_id = $_POST['receiver_id'];
    $content = trim($_POST['content']);
    if ($content) {
        try {
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $receiver_id, $content]);
            echo "<script>window.location.href='messages.php?user_id=$receiver_id';</script>";
            exit;
        } catch (PDOException $e) {
            $error = "Error sending message: " . $e->getMessage();
        }
    } else {
        $error = "Message content cannot be empty.";
    }
}

// Fetch existing conversations
$stmt = $pdo->prepare("
    SELECT DISTINCT receiver_id, users.first_name, users.last_name 
    FROM messages 
    JOIN users ON messages.receiver_id = users.id 
    WHERE sender_id = ? 
    UNION 
    SELECT DISTINCT sender_id, users.first_name, users.last_name 
    FROM messages 
    JOIN users ON messages.sender_id = users.id 
    WHERE receiver_id = ?
");
$stmt->execute([$user_id, $user_id]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all users for starting new conversations (excluding current user)
$stmt = $pdo->prepare("
    SELECT id, first_name, last_name 
    FROM users 
    WHERE id != ? 
    ORDER BY first_name
");
$stmt->execute([$user_id]);
$all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch messages for selected user
$selected_user_id = $_GET['user_id'] ?? null;
$messages = [];
if ($selected_user_id) {
    $stmt = $pdo->prepare("
        SELECT messages.*, users.first_name, users.last_name 
        FROM messages 
        JOIN users ON messages.sender_id = users.id 
        WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) 
        ORDER BY created_at
    ");
    $stmt->execute([$user_id, $selected_user_id, $selected_user_id, $user_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - LinkedIn Clone</title>
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
            max-width: 800px;
            margin: 20px auto;
            display: flex;
            gap: 20px;
            padding: 0 20px;
        }

        .conversations {
            width: 30%;
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            animation: fadeIn 0.5s ease;
        }

        .messages {
            width: 70%;
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            max-height: 600px;
            overflow-y: auto;
        }

        .conversation, .new-conversation {
            padding: 10px;
            border-bottom: 1px solid #eee;
            transition: background 0.3s ease;
        }

        .conversation:hover, .new-conversation:hover {
            background: rgba(0, 115, 177, 0.1);
        }

        .conversation a, .new-conversation a {
            color: var(--primary);
            text-decoration: none;
            position: relative;
        }

        .conversation a::before {
            content: '💬';
            margin-right: 10px;
        }

        .new-conversation a::before {
            content: '➕';
            margin-right: 10px;
        }

        .message {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 8px;
            max-width: 80%;
            position: relative;
            animation: slideMessage 0.3s ease;
        }

        @keyframes slideMessage {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .message.sent {
            background: var(--primary);
            color: white;
            margin-left: 20%;
            border-top-right-radius: 0;
        }

        .message.received {
            background: #f5f7fa;
            margin-right: 20%;
            border-top-left-radius: 0;
        }

        .message p {
            margin: 0;
        }

        .message small {
            font-size: 12px;
            opacity: 0.7;
        }

        textarea {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            resize: none;
            transition: border-color 0.3s ease;
        }

        textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 5px rgba(0, 115, 177, 0.5);
            outline: none;
        }

        button {
            background: var(--primary);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            box-shadow: var(--neumorphic-shadow);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        button::before {
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

        button:hover::before {
            width: 200px;
            height: 200px;
        }

        button:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }

        .error {
            color: #ff6b6b;
            margin-bottom: 10px;
            animation: shake 0.3s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        h3 {
            color: var(--primary);
            position: relative;
            margin-bottom: 20px;
        }

        h3::after {
            content: '';
            position: absolute;
            width: 50px;
            height: 3px;
            background: var(--primary);
            bottom: -5px;
            left: 0;
        }

        .no-data {
            font-style: italic;
            color: #666;
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
                flex-direction: column;
            }
            .conversations, .messages {
                width: 100%;
            }
            .messages {
                max-height: 400px;
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
        <div class="conversations">
            <h3>Conversations</h3>
            <?php if (empty($conversations)): ?>
                <p class="no-data">No conversations yet</p>
            <?php else: ?>
                <?php foreach ($conversations as $conv): ?>
                    <div class="conversation">
                        <a href="javascript:redirectTo('messages.php?user_id=<?php echo $conv['receiver_id']; ?>')">
                            <?php echo htmlspecialchars($conv['first_name'] . ' ' . $conv['last_name']); ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <h3>Start New Conversation</h3>
            <?php if (empty($all_users)): ?>
                <p class="no-data">No users available</p>
            <?php else: ?>
                <?php foreach ($all_users as $user): ?>
                    <div class="new-conversation">
                        <a href="javascript:redirectTo('messages.php?user_id=<?php echo $user['id']; ?>')">
                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="messages">
            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
            <?php if ($selected_user_id): ?>
                <h3>Messages with <?php
                    $stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
                    $stmt->execute([$selected_user_id]);
                    $selected_user = $stmt->fetch();
                    echo htmlspecialchars($selected_user['first_name'] . ' ' . $selected_user['last_name']);
                ?></h3>
                <?php if (empty($messages)): ?>
                    <p class="no-data">No messages yet. Start the conversation!</p>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <div class="message <?php echo $msg['sender_id'] == $user_id ? 'sent' : 'received'; ?>">
                            <p><strong><?php echo htmlspecialchars($msg['first_name'] . ' ' . $msg['last_name']); ?>:</strong> <?php echo htmlspecialchars($msg['content']); ?></p>
                            <p><small><?php echo $msg['created_at']; ?></small></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="receiver_id" value="<?php echo $selected_user_id; ?>">
                    <textarea name="content" placeholder="Type a message..." required></textarea>
                    <button type="submit">Send</button>
                </form>
            <?php else: ?>
                <p class="no-data">Select a conversation or start a new one to view messages.</p>
            <?php endif; ?>
        </div>
    </div>
    <script>
        function redirectTo(page) {
            window.location.href = page;
        }
    </script>
</body>
</html>
