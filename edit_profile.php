<?php
// edit_profile.php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $job_title = $_POST['job_title'];
    $summary = $_POST['summary'];
    $experience = json_encode($_POST['experience']);
    $education = json_encode($_POST['education']);
    $skills = json_encode($_POST['skills']);

    $profile_picture = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "assets/";
        $profile_picture = $target_dir . basename($_FILES['profile_picture']['name']);
        move_uploaded_file($_FILES['profile_picture']['tmp_name'], $profile_picture);
    }

    $stmt = $pdo->prepare("INSERT INTO profiles (user_id, job_title, summary, profile_picture, experience, education, skills) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE job_title = ?, summary = ?, profile_picture = ?, experience = ?, education = ?, skills = ?");
    $stmt->execute([$user_id, $job_title, $summary, $profile_picture, $experience, $education, $skills, $job_title, $summary, $profile_picture, $experience, $education, $skills]);
    echo "<script>window.location.href='profile.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - LinkedIn Clone</title>
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

        input, textarea {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        input:focus, textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 5px rgba(0, 115, 177, 0.5);
            outline: none;
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

        .dynamic-field {
            margin-bottom: 10px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
        }

        .dynamic-field input {
            margin: 5px 0;
        }

        h3 {
            color: var(--primary);
            position: relative;
            margin-bottom: 15px;
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

        @media (max-width: 768px) {
            .dynamic-field {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Profile</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="job_title" placeholder="Job Title" required>
            <textarea name="summary" placeholder="Summary"></textarea>
            <input type="file" name="profile_picture" accept="image/*">
            <div id="experience-fields">
                <h3>Experience</h3>
                <div class="dynamic-field">
                    <input type="text" name="experience[0][title]" placeholder="Job Title">
                    <input type="text" name="experience[0][company]" placeholder="Company">
                    <input type="text" name="experience[0][duration]" placeholder="Duration">
                </div>
            </div>
            <button type="button" onclick="addField('experience')">Add Experience</button>
            <div id="education-fields">
                <h3>Education</h3>
                <div class="dynamic-field">
                    <input type="text" name="education[0][degree]" placeholder="Degree">
                    <input type="text" name="education[0][school]" placeholder="School">
                    <input type="text" name="education[0][year]" placeholder="Year">
                </div>
            </div>
            <button type="button" onclick="addField('education')">Add Education</button>
            <div id="skills-fields">
                <h3>Skills</h3>
                <div class="dynamic-field">
                    <input type="text" name="skills[0]" placeholder="Skill">
                </div>
            </div>
            <button type="button" onclick="addField('skills')">Add Skill</button>
            <button type="submit">Save Profile</button>
        </form>
    </div>
    <script>
        let expCount = 1, eduCount = 1, skillCount = 1;
        function addField(type) {
            const container = document.getElementById(`${type}-fields`);
            const div = document.createElement('div');
            div.className = 'dynamic-field';
            if (type === 'experience') {
                div.innerHTML = `
                    <input type="text" name="experience[${expCount}][title]" placeholder="Job Title">
                    <input type="text" name="experience[${expCount}][company]" placeholder="Company">
                    <input type="text" name="experience[${expCount}][duration]" placeholder="Duration">
                `;
                expCount++;
            } else if (type === 'education') {
                div.innerHTML = `
                    <input type="text" name="education[${eduCount}][degree]" placeholder="Degree">
                    <input type="text" name="education[${eduCount}][school]" placeholder="School">
                    <input type="text" name="education[${eduCount}][year]" placeholder="Year">
                `;
                eduCount++;
            } else if (type === 'skills') {
                div.innerHTML = `<input type="text" name="skills[${skillCount}]" placeholder="Skill">`;
                skillCount++;
            }
            container.appendChild(div);
        }
        function redirectTo(page) {
            window.location.href = page;
        }
    </script>
</body>
</html>
