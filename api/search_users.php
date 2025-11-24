<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

$search = $_GET['search'] ?? '';
$stmt = $conn->prepare("
    SELECT id, username FROM users 
    WHERE username LIKE ? AND id != ?
    LIMIT 10
");
$stmt->execute(["%$search%", $_SESSION['user_id']]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($users);
?>