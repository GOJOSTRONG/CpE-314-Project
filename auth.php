<?php
// auth.php
require_once 'config.php';

header('Content-Type: application/json');

$conn = getDBConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$action = $_POST['action'] ?? '';

// --- REGISTRATION LOGIC ---
if ($action === 'register') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $questionId = $_POST['security_question_id'] ?? '';
    $answer = trim($_POST['security_answer'] ?? '');

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($questionId) || empty($answer)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
        exit;
    }
    if (ctype_digit($answer)) { // *** SERVER-SIDE VALIDATION FOR NUMBER-ONLY ANSWERS ***
        echo json_encode(['success' => false, 'message' => 'Security answer cannot be only numbers.']);
        exit;
    }
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
        exit;
    }

    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Username or email already taken.']);
            exit;
        }

        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $answer_hash = password_hash($answer, PASSWORD_BCRYPT); // Hash the security answer

        $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, security_question_id, security_answer_hash) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$username, $email, $password_hash, $questionId, $answer_hash])) {
            echo json_encode(['success' => true, 'message' => 'Registration successful! You can now log in.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Registration failed.']);
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred.']);
    }
}

// --- LOGIN LOGIC ---
elseif ($action === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
        exit;
    }

    try {
        $stmt = $conn->prepare("SELECT user_id, username, password_hash FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            echo json_encode(['success' => true, 'message' => 'Login successful!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred.']);
    }
}

// --- FORGOT PASSWORD: STEP 1 (Get Security Question) ---
elseif ($action === 'get_question') {
    $username = trim($_POST['username'] ?? '');
    if (empty($username)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a username.']);
        exit;
    }
    try {
        $stmt = $conn->prepare("
            SELECT q.question_text
            FROM users u
            JOIN security_questions q ON u.security_question_id = q.question_id
            WHERE u.username = ?
        ");
        $stmt->execute([$username]);
        $result = $stmt->fetch();

        if ($result) {
            $_SESSION['reset_username'] = $username; // Store username for next step
            echo json_encode(['success' => true, 'question' => $result['question_text']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Username not found or no security question set.']);
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred.']);
    }
}

// --- FORGOT PASSWORD: STEP 2 (Verify Answer) ---
elseif ($action === 'verify_answer') {
    $answer = trim($_POST['answer'] ?? '');
    $username = $_SESSION['reset_username'] ?? '';

    if (empty($answer) || empty($username)) {
        echo json_encode(['success' => false, 'message' => 'Session expired or answer missing.']);
        exit;
    }

    try {
        $stmt = $conn->prepare("SELECT security_answer_hash FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($answer, $user['security_answer_hash'])) {
            $_SESSION['reset_authorized'] = true; // Authorize password change
            echo json_encode(['success' => true, 'message' => 'Answer correct!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Incorrect answer.']);
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred.']);
    }
}

// --- FORGOT PASSWORD: STEP 3 (Reset Password) ---
elseif ($action === 'reset_password') {
    $password = $_POST['password'] ?? '';
    $username = $_SESSION['reset_username'] ?? '';
    $is_authorized = $_SESSION['reset_authorized'] ?? false;

    if (!$is_authorized || empty($username)) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized request. Please start over.']);
        exit;
    }
    if (empty($password) || strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
        exit;
    }

    try {
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
        
        if ($stmt->execute([$password_hash, $username])) {
            // Clean up session variables
            unset($_SESSION['reset_username']);
            unset($_SESSION['reset_authorized']);
            echo json_encode(['success' => true, 'message' => 'Password has been reset successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update password.']);
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred.']);
    }
}


// --- INVALID ACTION ---
else {
    echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
}

?>