<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

$stmt = $conn->prepare("
    SELECT lt.*, u.username 
    FROM live_trades lt 
    JOIN users u ON lt.user_id = u.id 
    WHERE lt.expires_at > NOW() 
    ORDER BY lt.created_at DESC 
    LIMIT 10
");
$stmt->execute();
$liveTrades = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($liveTrades);
?>