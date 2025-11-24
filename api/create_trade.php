<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$partner_id = $input['partner_id'];
$user_offer = json_encode($input['user_offer']);
$partner_offer = json_encode($input['partner_offer']);

$stmt = $conn->prepare("
    INSERT INTO trades (user1_id, user2_id, user1_offer, user2_offer) 
    VALUES (?, ?, ?, ?)
");
$stmt->execute([$_SESSION['user_id'], $partner_id, $user_offer, $partner_offer]);

echo json_encode(['success' => true, 'trade_id' => $conn->lastInsertId()]);
?>