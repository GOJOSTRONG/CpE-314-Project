<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

$trade_id = $_GET['trade_id'];
$user_id = $_SESSION['user_id'];

// Verify user is part of trade
$stmt = $conn->prepare("SELECT trade_id FROM trades WHERE (user1_id = ? OR user2_id = ?) AND trade_id = ?");
$stmt->execute([$user_id, $user_id, $trade_id]);
$trade = $stmt->fetch();

if (!$trade) {
    header('HTTP/1.1 403 Forbidden');
    exit();
}

$stmt = $conn->prepare("
    SELECT cm.*, u.username 
    FROM chat_messages cm 
    JOIN users u ON cm.user_id = u.id 
    WHERE cm.trade_id = ? 
    ORDER BY cm.created_at ASC
");
$stmt->execute([$trade_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mark if message is from current user
foreach ($messages as &$msg) {
    $msg['is_own'] = ($msg['user_id'] == $user_id);
}

header('Content-Type: application/json');
echo json_encode($messages);
?>