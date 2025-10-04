<?php
// jobs.php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    $title = $_POST['title'];
    $company = $_POST['company'];
    $location = $_POST['location'];
    $description = $_POST['description'];
    $experience_level = $_POST['experience_level'];

    $stmt = $pdo->prepare("INSERT INTO jobs (user_id, title, company, location, description, experience_level) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $title, $company, $location, $description, $experience_level]);
    echo "<script>window.location.href='jobs.php';</script>";
}

if (isset($_GET['apply'])) {
    $job_id = $_GET['apply'];
    $stmt = $pdo->prepare("INSERT INTO job_applications (job_id, user_id) VALUES (?, ?)");
    $stmt->execute([$job_id, $user_id]);
    echo "<script>window.location.href='jobs.php';</script>";
}

$search = $_GET['search'] ?? '';
$location = $_GET['location'] ?? '';
$experience = $_GET['experience'] ?? '';

$query = "SELECT jobs.*, users.first_name, users.last_name FROM jobs JOIN users ON jobs.user_id = users.id WHERE 1=1";
$params = [];
if ($search) {
    $query .= " AND title LIKE ?";
    $params[] = "%$search%";
}
if ($location) {
    $query .= " AND location LIKE ?";
    $params[] = "%$location%";
}
if ($experience) {
    $query .= " AND experience_level = ?";
    $params[] = $experience;
}
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$jobs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jobs - LinkedIn Clone</title>
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
        }

        .job-form, .search-form {
            position: sticky;
            top: 70px;
            background: var(--card-bg);
            padding: 15px;
            border-radius: 8px;
            box-shadow: var(--shadow);
            z-index: 900;
            margin-bottom: 20px;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 5px rgba(0, 115, 177, 0.5);
            outline: none;
        }

        .job {
            background: var(--card-bg);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .job:hover {
            transform: scale(1.02);
            box-shadow: var(--neumorphic-shadow);
        }

        .job h3 {
            margin: 0;
            color: var(--primary);
            position: relative;
        }

        .job h3::before {
            content: '💼';
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
            .job-form, .search-form {
                top: 60px;
            }
            .job {
                padding: 10px;
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
        <h2>Post a Job</h2>
        <form method="POST" class="job-form">
            <input type="text" name="title" placeholder="Job Title" required>
            <input type="text" name="company" placeholder="Company" required>
            <input type="text" name="location" placeholder="Location" required>
            <textarea name="description" placeholder="Job Description"></textarea>
            <select name="experience_level" required>
                <option value="entry">Entry Level</option>
                <option value="mid">Mid Level</option>
                <option value="senior">Senior Level</option>
            </select>
            <button type="submit">Post Job</button>
        </form>
        <h2>Search Jobs</h2>
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Search by title">
            <input type="text" name="location" placeholder="Location">
            <select name="experience">
                <option value="">All Experience Levels</option>
                <option value="entry">Entry Level</option>
                <option value="mid">Mid Level</option>
                <option value="senior">Senior Level</option>
            </select>
            <button type="submit">Search</button>
        </form>
        <h2>Available Jobs</h2>
        <?php foreach ($jobs as $job): ?>
            <div class="job">
                <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                <p><?php echo htmlspecialchars($job['company']); ?> - <?php echo htmlspecialchars($job['location']); ?></p>
                <p><?php echo htmlspecialchars($job['description']); ?></p>
                <p>Posted by: <?php echo htmlspecialchars($job['first_name'] . ' ' . $job['last_name']); ?></p>
                <button onclick="redirectTo('jobs.php?apply=<?php echo $job['id']; ?>')">Apply</button>
            </div>
        <?php endforeach; ?>
    </div>
    <script>
        function redirectTo(page) {
            window.location.href = page;
        }
    </script>
</body>
</html>
