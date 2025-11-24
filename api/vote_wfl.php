<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$feed_id = $input['feed_id'];
$vote = $input['vote'];
$user_id = $_SESSION['user_id'];

// Check if user already voted
$stmt = $conn->prepare("SELECT vote_id FROM wfl_votes WHERE trade_id = ? AND user_id = ?");
$stmt->execute([$feed_id, $user_id]);
$existingVote = $stmt->fetch();

if ($existingVote) {
    $stmt = $conn->prepare("UPDATE wfl_votes SET vote = ? WHERE vote_id = ?");
    $stmt->execute([$vote, $existingVote['vote_id']]);
} else {
    $stmt = $conn->prepare("INSERT INTO wfl_votes (trade_id, user_id, vote) VALUES (?, ?, ?)");
    $stmt->execute([$feed_id, $user_id, $vote]);
}

echo json_encode(['success' => true]);
?>