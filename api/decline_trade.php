<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    UPDATE trades 
    SET status = 'declined'
    WHERE (user1_id = ? OR user2_id = ?) AND status = 'pending'
");
$stmt->execute([$user_id, $user_id]);

echo json_encode(['success' => true]);
?>