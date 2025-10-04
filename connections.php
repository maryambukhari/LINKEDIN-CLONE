<?php
// connections.php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle connection request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['connect_user_id'])) {
    $connect_user_id = $_POST['connect_user_id'];
    $stmt = $pdo->prepare("INSERT INTO connections (user_id, connected_user_id, status) VALUES (?, ?, 'pending')");
    try {
        $stmt->execute([$user_id, $connect_user_id]);
    } catch (PDOException $e) {
        $error = "Error sending connection request: " . $e->getMessage();
    }
}

// Handle accepting connection
if (isset($_GET['accept'])) {
    $connection_id = $_GET['accept'];
    $stmt = $pdo->prepare("UPDATE connections SET status = 'accepted' WHERE id = ? AND connected_user_id = ?");
    try {
        $stmt->execute([$connection_id, $user_id]);
    } catch (PDOException $e) {
        $error = "Error accepting connection: " . $e->getMessage();
    }
}

// Fetch accepted connections
$stmt = $pdo->prepare("
    SELECT connections.*, users.first_name, users.last_name 
    FROM connections 
    JOIN users ON connections.connected_user_id = users.id 
    WHERE connections.user_id = ? AND status = 'accepted'
    UNION
    SELECT connections.*, users.first_name, users.last_name 
    FROM connections 
    JOIN users ON connections.user_id = users.id 
    WHERE connections.connected_user_id = ? AND status = 'accepted'
");
$stmt->execute([$user_id, $user_id]);
$connections = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch pending requests (sent to the user)
$stmt = $pdo->prepare("
    SELECT connections.*, users.first_name, users.last_name 
    FROM connections 
    JOIN users ON connections.user_id = users.id 
    WHERE connections.connected_user_id = ? AND status = 'pending'
");
$stmt->execute([$user_id]);
$pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch suggested connections (users not yet connected)
$stmt = $pdo->prepare("
    SELECT users.* 
    FROM users 
    WHERE users.id NOT IN (
        SELECT connected_user_id FROM connections WHERE user_id = ? AND status = 'accepted'
        UNION
        SELECT user_id FROM connections WHERE connected_user_id = ? AND status = 'accepted'
        UNION
        SELECT connected_user_id FROM connections WHERE user_id = ? AND status = 'pending'
        UNION
        SELECT user_id FROM connections WHERE connected_user_id = ? AND status = 'pending'
    ) AND users.id != ?
    LIMIT 5
");
$stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id]);
$suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connections - LinkedIn Clone</title>
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
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .connection, .suggestion {
            padding: 15px;
            border-bottom: 1px solid #eee;
            background: var(--card-bg);
            border-radius: 8px;
            margin-bottom: 10px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .connection:hover, .suggestion:hover {
            transform: translateY(-3px);
            box-shadow: var(--neumorphic-shadow);
        }

        .connection p, .suggestion p {
            margin: 0;
            font-size: 16px;
            position: relative;
        }

        .connection p::before, .suggestion p::before {
            content: '👤';
            margin-right: 10px;
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

        h2 {
            color: var(--primary);
            position: relative;
            margin-bottom: 20px;
        }

        h2::after {
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
                padding: 15px;
            }
            .connection, .suggestion {
                padding: 10px;
            }
            button {
                padding: 8px 15px;
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
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <h2>Pending Requests</h2>
        <?php if (empty($pending_requests)): ?>
            <p class="no-data">No pending requests</p>
        <?php else: ?>
            <?php foreach ($pending_requests as $request): ?>
                <div class="connection">
                    <p><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?> wants to connect</p>
                    <button onclick="redirectTo('connections.php?accept=<?php echo $request['id']; ?>')">Accept</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <h2>Your Connections</h2>
        <?php if (empty($connections)): ?>
            <p class="no-data">No connections yet</p>
        <?php else: ?>
            <?php foreach ($connections as $connection): ?>
                <div class="connection">
                    <p><?php echo htmlspecialchars($connection['first_name'] . ' ' . $connection['last_name']); ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <h2>Suggested Connections</h2>
        <?php if (empty($suggestions)): ?>
            <p class="no-data">No suggestions available</p>
        <?php else: ?>
            <?php foreach ($suggestions as $suggestion): ?>
                <div class="suggestion">
                    <p><?php echo htmlspecialchars($suggestion['first_name'] . ' ' . $suggestion['last_name']); ?></p>
                    <form method="POST">
                        <input type="hidden" name="connect_user_id" value="<?php echo $suggestion['id']; ?>">
                        <button type="submit">Connect</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <script>
        function redirectTo(page) {
            window.location.href = page;
        }
    </script>
</body>
</html>
