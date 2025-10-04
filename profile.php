<?php
// profile.php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT users.*, profiles.* FROM users LEFT JOIN profiles ON users.id = profiles.user_id WHERE users.id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - LinkedIn Clone</title>
    <style>
        :root {
            --primary: #0073b1;
            --secondary: #00a0dc;
            --card-bg: #ffffff;
            --shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            --neumorphic-shadow: 5px 5px 10px #d9d9d9, -5px -5px 10px #ffffff;
            --font: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            font-family: var(--font);
            margin: 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
            position: relative;
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
            padding: 20px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            position: relative;
            z-index: 1;
        }

        .profile-header {
            display: flex;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 20px;
            background: linear-gradient(rgba(0, 115, 177, 0.1), transparent);
        }

        .profile-picture img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: var(--neumorphic-shadow);
            transition: transform 0.3s ease;
        }

        .profile-picture img:hover {
            transform: scale(1.1);
        }

        .profile-info {
            margin-left: 20px;
        }

        .profile-info h2 {
            margin: 0;
            font-size: 24px;
            position: relative;
        }

        .profile-info h2::after {
            content: '';
            position: absolute;
            width: 50px;
            height: 3px;
            background: var(--primary);
            bottom: -5px;
            left: 0;
        }

        .section {
            margin: 20px 0;
            animation: fadeInUp 0.5s ease;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section h3 {
            margin-bottom: 10px;
            color: var(--primary);
        }

        .skills span {
            display: inline-block;
            background: var(--primary);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            margin: 5px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .skills span:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 10px rgba(0, 115, 177, 0.3);
        }

        button {
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

        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .profile-info {
                margin-left: 0;
                margin-top: 10px;
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
        <div class="profile-header">
            <div class="profile-picture">
                <img src="<?php echo $profile['profile_picture'] ?: 'assets/default.jpg'; ?>" alt="Profile Picture">
            </div>
            <div class="profile-info">
                <h2><?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></h2>
                <p><?php echo htmlspecialchars($profile['job_title'] ?: 'No job title'); ?></p>
                <button onclick="redirectTo('edit_profile.php')">Edit Profile</button>
            </div>
        </div>
        <div class="section">
            <h3>Summary</h3>
            <p><?php echo htmlspecialchars($profile['summary'] ?: 'No summary provided'); ?></p>
        </div>
        <div class="section">
            <h3>Experience</h3>
            <?php
            $experience = json_decode($profile['experience'] ?: '[]', true);
            foreach ($experience as $exp) {
                echo "<p><strong>" . htmlspecialchars($exp['title']) . "</strong> at " . htmlspecialchars($exp['company']) . "<br>" . htmlspecialchars($exp['duration']) . "</p>";
            }
            ?>
        </div>
        <div class="section">
            <h3>Education</h3>
            <?php
            $education = json_decode($profile['education'] ?: '[]', true);
            foreach ($education as $edu) {
                echo "<p><strong>" . htmlspecialchars($edu['degree']) . "</strong>, " . htmlspecialchars($edu['school']) . "<br>" . htmlspecialchars($edu['year']) . "</p>";
            }
            ?>
        </div>
        <div class="section skills">
            <h3>Skills</h3>
            <?php
            $skills = json_decode($profile['skills'] ?: '[]', true);
            foreach ($skills as $skill) {
                echo "<span>" . htmlspecialchars($skill) . "</span>";
            }
            ?>
        </div>
    </div>
    <script>
        function redirectTo(page) {
            window.location.href = page;
        }
    </script>
</body>
</html>
