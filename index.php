<?php 
session_start();
require_once 'config.php';
$conn = getDBConnection();
$questions = [];

// Get user stats for dashboard
$userStats = [];
if (isset($_SESSION['user_id'])) {
    try {
        // Get inventory count
        $stmt = $conn->prepare("SELECT COUNT(*) as total_items FROM user_inventory WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $userStats['total_items'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_items'];
        
        // Get active trades count
        $stmt = $conn->prepare("SELECT COUNT(*) as active_trades FROM trades WHERE (user1_id = ? OR user2_id = ?) AND status = 'pending'");
        $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
        $userStats['active_trades'] = $stmt->fetch(PDO::FETCH_ASSOC)['active_trades'];
        
        // Get recent trades
        $stmt = $conn->prepare("
            SELECT t.*, 
                   u1.username as user1_name, 
                   u2.username as user2_name,
                   CASE 
                     WHEN t.user1_id = ? THEN 'outgoing' 
                     ELSE 'incoming' 
                   END as trade_direction
            FROM trades t 
            JOIN users u1 ON t.user1_id = u1.id 
            JOIN users u2 ON t.user2_id = u2.id 
            WHERE (t.user1_id = ? OR t.user2_id = ?)
            ORDER BY t.created_at DESC 
            LIMIT 5
        ");
        $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
        $userStats['recent_trades'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        // Silently fail
    }
}

if ($conn) {
    try {
        $stmt = $conn->query("SELECT question_id, question_text FROM security_questions ORDER BY question_id");
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Silently fail, the dropdown will just be empty.
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GAG Pet Trader</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(to bottom, #87CEEB 0%, #E8F5E9 50%, #C8E6C9 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            position: relative;
        }

        /* Pet Animation Styles */
        .pet-container {
            position: absolute;
            z-index: 5;
            pointer-events: none;
        }

        .pet {
            width: 80px;
            height: auto;
            animation: floatAndShake 4s ease-in-out infinite;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
        }

        /* Left side pets */
        .pet-left-1 {
            top: 20%;
            left: 5%;
            animation-delay: 0s;
        }

        .pet-left-2 {
            top: 60%;
            left: 8%;
            animation-delay: 1s;
        }

        .pet-left-3 {
            top: 40%;
            left: 3%;
            animation-delay: 2s;
        }

        /* Right side pets */
        .pet-right-1 {
            top: 25%;
            right: 5%;
            animation-delay: 0.5s;
        }

        .pet-right-2 {
            top: 65%;
            right: 8%;
            animation-delay: 1.5s;
        }

        /* Animation keyframes */
        @keyframes floatAndShake {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            25% {
                transform: translateY(-15px) rotate(-5deg);
            }
            50% {
                transform: translateY(-5px) rotate(5deg);
            }
            75% {
                transform: translateY(-10px) rotate(-3deg);
            }
        }

        .auth-buttons {
            position: absolute;
            top: 30px;
            right: 40px;
            display: flex;
            gap: 15px;
            z-index: 100;
            animation: fadeInDown 1s ease-out 0.5s both;
            align-items: center;
        }

        .auth-btn, .logout-link {
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .login-btn {
            background: transparent;
            color: #2E7D32;
            border: 2px solid #2E7D32;
        }

        .login-btn:hover {
            background: #2E7D32;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.3);
        }

        .register-btn {
            background: linear-gradient(135deg, #66BB6A 0%, #4CAF50 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
        }

        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(76, 175, 80, 0.5);
        }

        .welcome-text {
            color: #2E7D32;
            font-weight: 600;
            align-self: center;
            margin-right: 10px;
        }

        .container {
            max-width: 1200px;
            width: 100%;
            text-align: center;
            position: relative;
            z-index: 10;
        }

        .logo-container {
            margin: 40px 0;
            animation: fadeInDown 1s ease-out;
        }

        .logo-container img {
            max-width: 500px;
            width: 100%;
            height: auto;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));
        }

        /* Dashboard Styles */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin: 40px 0;
        }

        .dashboard-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            text-align: left;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            color: #2E7D32;
        }

        .card-header i {
            font-size: 1.5em;
            margin-right: 15px;
        }

        .card-header h3 {
            font-size: 1.4em;
            font-weight: 600;
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #2E7D32;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }

        .action-btn {
            padding: 12px 20px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #66BB6A 0%, #4CAF50 100%);
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            display: block;
        }

        .action-btn.secondary {
            background: transparent;
            color: #2E7D32;
            border: 2px solid #2E7D32;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
        }

        .trade-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .trade-item:last-child {
            border-bottom: none;
        }

        .trade-info h4 {
            color: #333;
            margin-bottom: 5px;
        }

        .trade-info p {
            color: #666;
            font-size: 0.9em;
        }

        .trade-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
        }

        .status-pending {
            background: #FFF3CD;
            color: #856404;
        }

        .status-accepted {
            background: #D1ECF1;
            color: #0C5460;
        }

        .status-completed {
            background: #D4EDDA;
            color: #155724;
        }

        .welcome-section {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            padding: 60px 40px;
            margin: 40px auto;
            max-width: 800px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            animation: fadeInUp 1s ease-out 0.3s both;
            position: relative;
            z-index: 10;
        }

        h1 {
            font-size: 3.5em;
            color: #2E7D32;
            margin-bottom: 20px;
            font-weight: 300;
            letter-spacing: 2px;
        }

        .underline {
            width: 200px;
            height: 4px;
            background: #4CAF50;
            margin: 0 auto 40px;
        }

        .description {
            font-size: 1.2em;
            color: #555;
            line-height: 1.8;
            margin-bottom: 40px;
        }

        .cta-button {
            background: linear-gradient(135deg, #66BB6A 0%, #4CAF50 100%);
            color: white;
            padding: 18px 50px;
            font-size: 1.2em;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.4);
            transition: all 0.3s ease;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.6);
        }

        .cta-button:active {
            transform: translateY(-1px);
        }

        .about-section {
            background: white;
            border-radius: 20px;
            padding: 50px 40px;
            margin-top: 60px;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            animation: fadeInUp 1s ease-out 0.6s both;
            text-align: left;
            position: relative;
            z-index: 10;
        }

        .about-section h2 {
            font-size: 2.5em;
            color: #2E7D32;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }

        .about-section .underline-small {
            width: 100px;
            height: 3px;
            background: #4CAF50;
            margin: 0 auto 30px;
        }

        .about-section p {
            color: #555;
            line-height: 1.8;
            font-size: 1.1em;
            margin-bottom: 20px;
        }

        /* Live Trading Preview */
        .live-trading-preview {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin: 40px 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .preview-header h2 {
            color: #2E7D32;
            font-size: 1.8em;
        }

        .view-all-btn {
            background: transparent;
            color: #2E7D32;
            border: 2px solid #2E7D32;
            padding: 8px 20px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .view-all-btn:hover {
            background: #2E7D32;
            color: white;
        }

        .feed-preview {
            display: flex;
            gap: 15px;
            overflow-x: auto;
            padding: 10px 0;
        }

        .feed-item-preview {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 15px;
            min-width: 250px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .feed-item-preview:hover {
            border-color: #4CAF50;
            transform: translateY(-2px);
        }

        .feed-user {
            font-weight: bold;
            color: #2E7D32;
            margin-bottom: 8px;
        }

        .feed-items {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
        }

        .feed-type {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
        }

        .type-want {
            background: #E3F2FD;
            color: #1976D2;
        }

        .type-offer {
            background: #E8F5E8;
            color: #2E7D32;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            animation: slideUp 0.3s ease;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .close-modal {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 30px;
            color: #999;
            cursor: pointer;
            line-height: 1;
            transition: color 0.3s;
        }

        .close-modal:hover {
            color: #333;
        }

        .modal h2 {
            color: #2E7D32;
            margin-bottom: 10px;
            font-size: 2em;
            text-align: center;
        }

        .modal p {
            color: #666;
            margin-bottom: 25px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            color: #2E7D32;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.95em;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #E0E0E0;
            border-radius: 10px;
            font-size: 1em;
            transition: border-color 0.3s;
            background-color: white;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #4CAF50;
        }

        .form-button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #66BB6A 0%, #4CAF50 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            margin-top: 10px;
        }

        .form-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
        }

        .form-button:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .form-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .form-link a {
            color: #4CAF50;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }

        .form-link a:hover {
            text-decoration: underline;
        }

        .message-box {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9em;
            display: none;
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
        }

        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .fp-step {
            display: none;
        }

        .fp-step.active {
            display: block;
        }

        #termsModal .modal-content {
            max-width: 700px;
            text-align: left;
        }

        #termsModal h2 {
            text-align: center;
        }

        #termsModal p {
            text-align: left;
            margin-bottom: 15px;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Styles */
        @media (max-width: 1024px) and (min-width: 769px) {
            .logo-container {
                margin: 30px 0;
            }

            .logo-container img {
                max-width: 450px;
            }

            .auth-buttons {
                top: 25px;
                right: 30px;
                gap: 12px;
            }

            .auth-btn, .logout-link {
                padding: 11px 25px;
                font-size: 0.95em;
            }

            h1 {
                font-size: 3em;
            }

            .welcome-section {
                padding: 50px 35px;
                margin: 30px auto;
            }

            .description {
                font-size: 1.1em;
            }

            .cta-button {
                padding: 16px 45px;
                font-size: 1.1em;
            }

            .about-section {
                padding: 45px 35px;
                margin-top: 50px;
            }

            .about-section h2 {
                font-size: 2.2em;
            }

            .about-section p {
                font-size: 1.05em;
            }
            
            /* Adjust pets for tablet */
            .pet {
                width: 70px;
            }

            .dashboard-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .auth-buttons {
                top: 10px;
                right: 10px;
                gap: 8px;
            }

            .auth-btn, .logout-link {
                padding: 8px 16px;
                font-size: 0.8em;
                letter-spacing: 0.3px;
            }

            .welcome-text {
                font-size: 0.8em;
            }

            .logo-container {
                margin: 50px 0 20px;
            }

            .logo-container img {
                max-width: 320px;
            }

            h1 {
                font-size: 2em;
                letter-spacing: 1px;
            }

            .welcome-section {
                padding: 30px 20px;
                margin: 20px auto;
                border-radius: 15px;
            }

            .underline {
                width: 150px;
                margin-bottom: 25px;
            }

            .description {
                font-size: 0.95em;
                line-height: 1.6;
                margin-bottom: 30px;
            }

            .cta-button {
                padding: 14px 35px;
                font-size: 0.95em;
                letter-spacing: 0.5px;
            }

            .about-section {
                padding: 35px 25px;
                margin-top: 30px;
                border-radius: 15px;
            }

            .about-section h2 {
                font-size: 1.8em;
            }

            .about-section .underline-small {
                width: 80px;
                margin-bottom: 20px;
            }

            .about-section p {
                font-size: 0.95em;
                line-height: 1.7;
                margin-bottom: 15px;
            }
            
            /* Adjust pets for mobile */
            .pet {
                width: 50px;
            }
            
            .pet-left-1, .pet-left-2, .pet-left-3 {
                left: 2%;
            }
            
            .pet-right-1, .pet-right-2 {
                right: 2%;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .auth-buttons {
                top: 8px;
                right: 8px;
                gap: 6px;
            }

            .auth-btn, .logout-link {
                padding: 7px 12px;
                font-size: 0.7em;
            }

            .welcome-text {
                font-size: 0.7em;
            }

            .logo-container {
                margin: 40px 0 15px;
            }

            .logo-container img {
                max-width: 280px;
            }

            h1 {
                font-size: 1.6em;
            }

            .welcome-section {
                padding: 25px 15px;
                margin: 15px 10px;
            }

            .underline {
                width: 120px;
                height: 3px;
            }

            .description {
                font-size: 0.9em;
            }

            .cta-button {
                padding: 12px 30px;
                font-size: 0.9em;
                width: 100%;
                max-width: 250px;
            }

            .about-section {
                padding: 25px 20px;
                margin-top: 25px;
            }

            .about-section h2 {
                font-size: 1.5em;
            }

            .about-section p {
                font-size: 0.9em;
                margin-bottom: 12px;
            }
            
            /* Hide some pets on very small screens */
            .pet-left-3 {
                display: none;
            }

            .dashboard-card {
                padding: 20px;
            }

            .stat-number {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
<audio id="bgMusic" loop autoplay>
    <source src="music.mp3" type="audio/mpeg">
    Your browser does not support the audio element.
</audio>

    <!-- Pet Animations -->
    <div class="pet-container pet-left-1">
        <img src="logo/orangetabby.png" alt="Orange Tabby" class="pet">
    </div>
    <div class="pet-container pet-left-2">
        <img src="logo/kitsune.png" alt="Kitsune" class="pet">
    </div>
    <div class="pet-container pet-left-3">
        <img src="logo/raccoon.png" alt="Raccoon" class="pet">
    </div>
    <div class="pet-container pet-right-1">
        <img src="logo/mimic_octopus.png" alt="Mimic Octopus" class="pet">
    </div>
    <div class="pet-container pet-right-2">
        <img src="logo/headless_horseman.png" alt="Headless Horseman" class="pet">
    </div>

    <div class="auth-buttons">
        <?php if (isset($_SESSION['username'])): ?>
            <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
            <a href="logout.php" class="logout-link login-btn">Logout</a>
        <?php else: ?>
            <button class="auth-btn login-btn" id="loginBtn">Login</button>
            <button class="auth-btn register-btn" id="registerBtn">Register</button>
        <?php endif; ?>
    </div>
    
    <div class="container">
        <?php if (isset($_SESSION['username'])): ?>
            <!-- LOGGED-IN VIEW - DASHBOARD -->
            <div class="logo-container">
                <img id="logoImage" src="logo/gagLogo.png" alt="Grow a Garden Logo" onerror="this.style.display='none'; document.getElementById('logoFallback').style.display='block';">
                <div id="logoFallback" style="display: none;">
                    <h1 style="font-size: 4em; color: #2E7D32; text-shadow: 2px 2px 4px rgba(0,0,0,0.2); margin: 0;">
                        GROW A GARDEN
                    </h1>
                </div>
            </div>

            <!-- Dashboard Stats -->
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div class="card-header">
                        <i>📊</i>
                        <h3>Collection Stats</h3>
                    </div>
                    <div class="stat-number"><?php echo $userStats['total_items'] ?? 0; ?></div>
                    <div class="stat-label">Total Items</div>
                    <div class="quick-actions">
                        <a href="inventory.php" class="action-btn">View Inventory</a>
                        <a href="collection.php" class="action-btn secondary">Manage Collection</a>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="card-header">
                        <i>🤝</i>
                        <h3>Active Trades</h3>
                    </div>
                    <div class="stat-number"><?php echo $userStats['active_trades'] ?? 0; ?></div>
                    <div class="stat-label">Pending Trades</div>
                    <div class="quick-actions">
                        <a href="trading.php" class="action-btn">Start Trading</a>
                        <a href="trades.php" class="action-btn secondary">View All Trades</a>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="card-header">
                        <i>⚡</i>
                        <h3>Quick Actions</h3>
                    </div>
                    <div class="quick-actions" style="grid-template-columns: 1fr; margin-top: 30px;">
                        <a href="trading.php" class="action-btn">🚀 Start New Trade</a>
                        <a href="marketplace.php" class="action-btn secondary">🛒 Browse Marketplace</a>
                        <a href="live_feed.php" class="action-btn secondary">📡 Live Trading Feed</a>
                        <a href="friends.php" class="action-btn secondary">👥 Find Friends</a>
                    </div>
                </div>
            </div>

            <!-- Recent Trades -->
            <?php if (!empty($userStats['recent_trades'])): ?>
            <div class="live-trading-preview">
                <div class="preview-header">
                    <h2>Recent Trades</h2>
                    <a href="trades.php" class="view-all-btn">View All</a>
                </div>
                <div class="feed-preview">
                    <?php foreach ($userStats['recent_trades'] as $trade): ?>
                    <div class="feed-item-preview">
                        <div class="feed-user">
                            <?php echo htmlspecialchars($trade['trade_direction'] === 'outgoing' ? $trade['user2_name'] : $trade['user1_name']); ?>
                        </div>
                        <div class="feed-items">
                            Trade #<?php echo $trade['trade_id']; ?>
                        </div>
                        <span class="feed-type status-<?php echo $trade['status']; ?>">
                            <?php echo ucfirst($trade['status']); ?>
                        </span>
                        <div style="margin-top: 10px; font-size: 0.8em; color: #999;">
                            <?php echo date('M j, g:i A', strtotime($trade['created_at'])); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Live Trading Preview -->
            <div class="live-trading-preview">
                <div class="preview-header">
                    <h2>Live Trading Feed</h2>
                    <a href="live_feed.php" class="view-all-btn">View All</a>
                </div>
                <div class="feed-preview" id="liveFeedPreview">
                    <!-- Live feed items will be loaded by JavaScript -->
                </div>
            </div>

        <?php else: ?>
            <!-- LOGGED-OUT VIEW (LANDING PAGE) -->
            <div class="logo-container">
                <img id="logoImage" src="logo/gagLogo.png" alt="Grow a Garden Logo" onerror="this.style.display='none'; document.getElementById('logoFallback').style.display='block';">
                <div id="logoFallback" style="display: none;">
                    <h1 style="font-size: 4em; color: #2E7D32; text-shadow: 2px 2px 4px rgba(0,0,0,0.2); margin: 0;">
                        GROW A GARDEN
                    </h1>
                </div>
            </div>
            <div class="welcome-section">
                <h1>Welcome to GAG Trader</h1>
                <div class="underline"></div>
                <p class="description">
                    In Roblox games such as Grow a Garden, players frequently trade pets and other in-game items. This platform helps you find fair trades based on accurate pet values and market data. Browse available trades, check item values, and connect with other traders—then complete your trade manually in the Roblox game to ensure safe, equitable exchanges.
                </p>
                <button class="cta-button" id="getStartedBtn">Start Trading Now</button>
            </div>
            <div class="about-section">
                <h2>About Us</h2>
                <div class="underline-small"></div>
                <p>
                    Welcome to the Grow a Garden Trading Platform – your secure solution for managing pet and item trades in the Roblox Grow a Garden community. We understand the challenges players face with informal trading systems and the risks of scams and miscommunication.
                </p>
                <p>
                    Our platform provides a centralized, transparent system where players can safely negotiate trades, maintain complete transaction records, and manage their inventory with confidence. We've built comprehensive tracking tools and verification systems to protect both buyers and sellers throughout every trade.
                </p>
                <p>
                    Whether you're a casual player or a serious collector, our mission is to create a trustworthy trading environment where you can focus on building your collection without worrying about lost records or fraudulent transactions. Join our growing community of traders who value security, transparency, and fair deals.
                </p>
                <p style="margin-top: 30px; padding-top: 25px; border-top: 2px solid #E8F5E9;">
                    <strong style="color: #2E7D32; font-size: 1.2em; display: block; margin-bottom: 15px;">Our Team</strong>
                    <strong style="color: #4CAF50;">Project Lead & Developer:</strong> Reginald Tatulo – Coordinates development, demo presentations, proposal edits, and implements backend and UI components.
                    <br><br>
                    <strong style="color: #4CAF50;">Tester & Documentation:</strong> Kim Adrian Magno – Runs comprehensive test cases, documents results, prepares proposal documents, diagrams, and final reports.
                </p>
            </div>
        <?php endif; ?>
    </div>

    <!-- MODALS (Only shown if NOT logged in) -->
    <?php if (!isset($_SESSION['username'])): ?>
        <!-- Login Modal -->
        <div id="loginModal" class="modal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <h2>Login</h2>
                <p>Welcome back! Please enter your details.</p>
                <div id="loginMessageBox" class="message-box"></div>
                <form id="loginForm">
                    <input type="hidden" name="action" value="login">
                    <div class="form-group">
                        <label for="loginUsername">Username</label>
                        <input type="text" id="loginUsername" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="loginPassword">Password</label>
                        <input type="password" id="loginPassword" name="password" required>
                    </div>
                    <button type="submit" class="form-button">Login</button>
                </form>
                <div class="form-link"><a id="forgotPasswordLink">Forgot Password?</a></div>
                <div class="form-link">Don't have an account? <a id="showRegisterLink">Register here</a></div>
            </div>
        </div>

        <!-- Register Modal -->
        <div id="registerModal" class="modal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <h2>Create Account</h2>
                <p>Join our community to start trading securely.</p>
                <div id="registerMessageBox" class="message-box"></div>
                <form id="registerForm">
                    <input type="hidden" name="action" value="register">
                    <div class="form-group">
                        <label for="regUsername">Username</label>
                        <input type="text" id="regUsername" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="regEmail">Email</label>
                        <input type="email" id="regEmail" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="regPassword">Password (min. 6 characters)</label>
                        <input type="password" id="regPassword" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="regSecurityQuestion">Security Question</label>
                        <select id="regSecurityQuestion" name="security_question_id" required>
                            <option value="" disabled selected>-- Select a Question --</option>
                            <?php foreach ($questions as $q): ?>
                                <option value="<?php echo $q['question_id']; ?>"><?php echo htmlspecialchars($q['question_text']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="regSecurityAnswer">Your Answer (cannot be only numbers)</label>
                        <input type="text" id="regSecurityAnswer" name="security_answer" required>
                    </div>
                    <div class="form-group" style="flex-direction: row; align-items: center; display: flex; gap: 10px;">
                        <input type="checkbox" id="regTerms" name="terms_agreed" required style="width: auto;">
                        <label for="regTerms" style="margin-bottom: 0;">I agree to the <a href="#" id="termsLink">Terms & Conditions</a></label>
                    </div>
                    <button type="submit" id="registerSubmitBtn" class="form-button" disabled>Register</button>
                </form>
                <div class="form-link">Already have an account? <a id="showLoginLink">Login here</a></div>
            </div>
        </div>
        
        <!-- Forgot Password Modal -->
        <div id="forgotPasswordModal" class="modal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <h2>Password Reset</h2>
                <div id="fpMessageBox" class="message-box"></div>
                <form id="fpStep1" class="fp-step active">
                    <p>Enter your username to begin.</p>
                    <input type="hidden" name="action" value="get_question">
                    <div class="form-group">
                        <label for="fpUsername">Username</label>
                        <input type="text" id="fpUsername" name="username" required>
                    </div>
                    <button type="submit" class="form-button">Next</button>
                </form>
                <form id="fpStep2" class="fp-step">
                    <p id="securityQuestionText">Your security question will appear here.</p>
                    <input type="hidden" name="action" value="verify_answer">
                    <div class="form-group">
                        <label for="fpAnswer">Your Answer</label>
                        <input type="text" id="fpAnswer" name="answer" required>
                    </div>
                    <button type="submit" class="form-button">Verify</button>
                </form>
                <form id="fpStep3" class="fp-step">
                    <p>Enter your new password.</p>
                    <input type="hidden" name="action" value="reset_password">
                    <div class="form-group">
                        <label for="fpNewPassword">New Password (min. 6 characters)</label>
                        <input type="password" id="fpNewPassword" name="password" required>
                    </div>
                    <button type="submit" class="form-button">Reset Password</button>
                </form>
            </div>
        </div>

        <!-- Terms and Conditions Modal -->
        <div id="termsModal" class="modal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <h2>Terms & Conditions</h2>
                <p><strong>Last Updated: October 14, 2025</strong></p>
                <p>By registering for an account, you agree to the following terms and conditions...</p>
                <p>1. You must be respectful to all other users of the platform.</p>
                <p>2. Scamming or any attempt to defraud other users is strictly prohibited and will result in a permanent ban.</p>
                <p>3. All trades are final. Please double-check all trade details before confirming.</p>
                <p>4. We are not responsible for any trades conducted outside of this platform.</p>
                <p>5. The developers reserve the right to modify these terms at any time.</p>
            </div>
        </div>
    <?php endif; ?>

    <script>
        // Background Music System
        document.addEventListener('DOMContentLoaded', function() {
            const bgMusic = document.getElementById('bgMusic');
            let isPlaying = false;
            
            bgMusic.volume = 0.7;
            
            // Start music on first user interaction
            const startMusic = function() {
                if (!isPlaying) {
                    bgMusic.play().then(() => {
                        isPlaying = true;
                    }).catch(() => {});
                }
            };
            
            document.addEventListener('click', startMusic, { once: true });
        });

        // Modal and Form Handling
        document.addEventListener('DOMContentLoaded', () => {
            const modals = {
                login: document.getElementById('loginModal'),
                register: document.getElementById('registerModal'),
                forgot: document.getElementById('forgotPasswordModal'),
                terms: document.getElementById('termsModal')
            };
            
            const openModal = (modalName) => {
                Object.values(modals).forEach(m => {
                    if(m) m.classList.toggle('active', m === modals[modalName]);
                });
            };
            
            document.getElementById('loginBtn')?.addEventListener('click', () => openModal('login'));
            document.getElementById('registerBtn')?.addEventListener('click', () => openModal('register'));
            document.getElementById('getStartedBtn')?.addEventListener('click', () => openModal('register'));
            document.getElementById('showRegisterLink')?.addEventListener('click', () => openModal('register'));
            document.getElementById('showLoginLink')?.addEventListener('click', () => openModal('login'));
            document.getElementById('forgotPasswordLink')?.addEventListener('click', () => openModal('forgot'));
            document.getElementById('termsLink')?.addEventListener('click', (e) => {
                e.preventDefault();
                openModal('terms');
            });
            
            document.querySelectorAll('.close-modal').forEach(btn => btn.addEventListener('click', () => openModal(null)));
            window.addEventListener('click', (e) => {
                if (e.target?.classList.contains('modal')) openModal(null);
            });
            
            const handleForm = async (form, messageBoxId) => {
                const formData = new FormData(form);
                const messageBox = document.getElementById(messageBoxId);
                try {
                    const response = await fetch('auth.php', { method: 'POST', body: formData });
                    if (!response.ok) throw new Error('Network response was not ok.');
                    const result = await response.json();
                    
                    messageBox.textContent = result.message;
                    messageBox.className = `message-box ${result.success ? 'success' : 'error'}-message`;
                    messageBox.style.display = 'block';
                    return result;
                } catch (error) {
                    messageBox.textContent = 'A network error occurred. Please try again.';
                    messageBox.className = 'message-box error-message';
                    messageBox.style.display = 'block';
                    return { success: false };
                }
            };
            
            document.getElementById('loginForm')?.addEventListener('submit', async (e) => {
                e.preventDefault();
                const result = await handleForm(e.target, 'loginMessageBox');
                if (result.success) setTimeout(() => window.location.reload(), 1500);
            });
            
            document.getElementById('registerForm')?.addEventListener('submit', async (e) => {
                e.preventDefault();
                const result = await handleForm(e.target, 'registerMessageBox');
                if (result.success) {
                    e.target.reset();
                    setTimeout(() => openModal('login'), 2000);
                }
            });
            
            const regTermsCheckbox = document.getElementById('regTerms');
            const registerSubmitBtn = document.getElementById('registerSubmitBtn');
            regTermsCheckbox?.addEventListener('change', () => {
                registerSubmitBtn.disabled = !regTermsCheckbox.checked;
            });
            
            document.getElementById('regSecurityAnswer')?.addEventListener('input', (e) => {
                if (/^\d+$/.test(e.target.value)) {
                    e.target.setCustomValidity("Answer cannot be only numbers.");
                } else {
                    e.target.setCustomValidity("");
                }
            });

            const fpSteps = {
                step1: document.getElementById('fpStep1'),
                step2: document.getElementById('fpStep2'),
                step3: document.getElementById('fpStep3')
            };
            
            const showFpStep = (stepName) => Object.values(fpSteps).forEach(s => {
                if(s) s.classList.toggle('active', s === fpSteps[stepName])
            });
            
            fpSteps.step1?.addEventListener('submit', async (e) => {
                e.preventDefault();
                const result = await handleForm(e.target, 'fpMessageBox');
                if (result.success) {
                    document.getElementById('securityQuestionText').textContent = result.question;
                    showFpStep('step2');
                }
            });
            
            fpSteps.step2?.addEventListener('submit', async (e) => {
                e.preventDefault();
                const result = await handleForm(e.target, 'fpMessageBox');
                if (result.success) showFpStep('step3');
            });
            
            fpSteps.step3?.addEventListener('submit', async (e) => {
                e.preventDefault();
                const result = await handleForm(e.target, 'fpMessageBox');
                if (result.success) {
                    setTimeout(() => openModal('login'), 2000);
                }
            });

            // Load live trading feed preview for logged-in users
            <?php if (isset($_SESSION['username'])): ?>
            loadLiveFeedPreview();
            <?php endif; ?>
        });

        // Live Feed Preview Loader
        async function loadLiveFeedPreview() {
            try {
                const response = await fetch('api/get_live_trades.php');
                const liveTrades = await response.json();
                
                const feedContainer = document.getElementById('liveFeedPreview');
                if (feedContainer && liveTrades.length > 0) {
                    feedContainer.innerHTML = liveTrades.slice(0, 5).map(trade => `
                        <div class="feed-item-preview">
                            <div class="feed-user">${trade.username}</div>
                            <div class="feed-items">
                                ${trade.trade_type === 'want' ? 'Wants' : 'Offers'} ${JSON.parse(trade.items).length} items
                            </div>
                            <span class="feed-type ${trade.trade_type === 'want' ? 'type-want' : 'type-offer'}">
                                ${trade.trade_type === 'want' ? 'Want' : 'Offer'}
                            </span>
                            <div class="wfl-buttons" style="margin-top: 10px; display: flex; gap: 5px;">
                                <button class="wfl-btn win-btn" onclick="voteWFL(${trade.feed_id}, 'win')">W</button>
                                <button class="wfl-btn fair-btn" onclick="voteWFL(${trade.feed_id}, 'fair')">F</button>
                                <button class="wfl-btn lose-btn" onclick="voteWFL(${trade.feed_id}, 'lose')">L</button>
                            </div>
                        </div>
                    `).join('');
                }
            } catch (error) {
                console.error('Error loading live feed:', error);
            }
        }

        async function voteWFL(feedId, vote) {
            try {
                await fetch('api/vote_wfl.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ feed_id: feedId, vote: vote })
                });
                // Show some feedback
                alert(`Voted ${vote.toUpperCase()}!`);
            } catch (error) {
                console.error('Error voting:', error);
            }
        }
    </script>
</body>
</html>
