<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT t.*, u1.username as user1_name, u2.username as user2_name 
    FROM trades t 
    JOIN users u1 ON t.user1_id = u1.id 
    JOIN users u2 ON t.user2_id = u2.id 
    WHERE (t.user1_id = ? OR t.user2_id = ?) AND t.status = 'pending'
    ORDER BY t.created_at DESC LIMIT 1
");
$stmt->execute([$user_id, $user_id]);
$trade = $stmt->fetch(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($trade ?: null);
?>