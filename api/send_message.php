<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$trade_id = $input['trade_id'];
$message = $input['message'];
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("INSERT INTO chat_messages (trade_id, user_id, message) VALUES (?, ?, ?)");
$stmt->execute([$trade_id, $user_id, $message]);

echo json_encode(['success' => true]);
?>