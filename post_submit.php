<?php
// post_submit.php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
$content = $_POST['content'];

$stmt = $pdo->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
$stmt->execute([$user_id, $content]);
echo "<script>window.location.href='index.php';</script>";
?>
