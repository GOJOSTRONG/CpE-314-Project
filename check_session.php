<?php
require_once 'config.php';

header('Content-Type: application/json');

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    echo json_encode([
        'logged_in' => true,
        'user' => [
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'display_name' => $_SESSION['display_name'],
            'email' => $_SESSION['email']
        ]
    ]);
} else {
    echo json_encode(['logged_in' => false]);
}
?>