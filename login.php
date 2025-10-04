<?php
// login.php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        echo "<script>window.location.href='index.php';</script>";
    } else {
        $error = "Invalid email or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LinkedIn Clone</title>
    <style>
        :root {
            --primary: #0073b1;
            --secondary: #00a0dc;
            --card-bg: rgba(255, 255, 255, 0.2);
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            --font: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            font-family: var(--font);
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: #fff;
        }

        .login-container {
            background: var(--card-bg);
            backdrop-filter: blur(15px);
            padding: 40px;
            border-radius: 15px;
            box-shadow: var(--shadow);
            width: 400px;
            text-align: center;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: none;
            border-bottom: 2px solid #ccc;
            background: transparent;
            color: #fff;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        input:focus {
            border-color: var(--primary);
            outline: none;
        }

        input::placeholder {
            color: #ddd;
        }

        button {
            background: var(--primary);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(0, 115, 177, 0.4);
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
            width: 300px;
            height: 300px;
        }

        button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 115, 177, 0.6);
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

        a {
            color: var(--primary);
            text-decoration: none;
            position: relative;
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
    <div class="login-container">
        <h2>Login</h2>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <p>Don't have an account? <a href="javascript:redirectTo('signup.php')">Sign Up</a></p>
    </div>
    <script>
        function redirectTo(page) {
            window.location.href = page;
        }
    </script>
</body>
</html>
