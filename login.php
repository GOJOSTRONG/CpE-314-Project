<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

// Validation
if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Username and password are required']);
    exit;
}

// Get user's IP address
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// Database operations
$conn = getDBConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Check for too many failed login attempts (5 attempts in last 15 minutes)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as attempt_count 
        FROM login_attempts 
        WHERE username = ? 
        AND successful = FALSE 
        AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
    ");
    $stmt->execute([$username]);
    $result = $stmt->fetch();
    
    if ($result['attempt_count'] >= 5) {
        echo json_encode([
            'success' => false, 
            'message' => 'Too many failed login attempts. Please try again in 15 minutes.'
        ]);
        exit;
    }

    // Fetch user data
    $stmt = $conn->prepare("
        SELECT u.user_id, u.username, u.email, u.password_hash, u.is_active,
               p.display_name, p.roblox_username
        FROM users u
        LEFT JOIN user_profiles p ON u.user_id = p.user_id
        WHERE u.username = ? OR u.email = ?
    ");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if (!$user) {
        // Log failed attempt
        $stmt = $conn->prepare("
            INSERT INTO login_attempts (username, ip_address, successful) 
            VALUES (?, ?, FALSE)
        ");
        $stmt->execute([$username, $ip_address]);
        
        echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
        exit;
    }

    // Check if account is active
    if (!$user['is_active']) {
        echo json_encode(['success' => false, 'message' => 'Account is deactivated. Please contact support.']);
        exit;
    }

    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        // Log failed attempt
        $stmt = $conn->prepare("
            INSERT INTO login_attempts (username, ip_address, successful) 
            VALUES (?, ?, FALSE)
        ");
        $stmt->execute([$username, $ip_address]);
        
        echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
        exit;
    }

    // Successful login
    // Log successful attempt
    $stmt = $conn->prepare("
        INSERT INTO login_attempts (username, ip_address, successful) 
        VALUES (?, ?, TRUE)
    ");
    $stmt->execute([$username, $ip_address]);

    // Update last login time
    $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
    $stmt->execute([$user['user_id']]);

    // Set session variables
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['display_name'] = $user['display_name'] ?? $user['username'];
    $_SESSION['logged_in'] = true;

    // Regenerate session ID for security
    session_regenerate_id(true);

    echo json_encode([
        'success' => true,
        'message' => 'Login successful!',
        'user' => [
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'display_name' => $user['display_name'] ?? $user['username'],
            'email' => $user['email']
        ]
    ]);

} catch(PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Login failed. Please try again.']);
}
?>