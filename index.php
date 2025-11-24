<?php 
session_start();
require_once 'config.php';
$conn = getDBConnection();
$questions = [];
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
            overflow-x: hidden;
        }

        /* Pet Animation Styles */
        .pet-container {
            position: absolute;
            z-index: 11;
            pointer-events: none;
        }

        .pet {
            width: 200px;
            height: auto;
            animation: floatAndShake 4s ease-in-out infinite;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
        }

        /* Left side pets */
        .pet-left-1 {
            top: 7%;
            left: 5%;
			width: 400px;
            animation-delay: 0s;
        }

        .pet-left-2 {
            top: 80%;
            left: 4%;
			width: 400px;
            animation-delay: 1s;
        }

        .pet-left-3 {
            top: 35%;
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
            <!-- LOGGED-IN VIEW -->
            <div class="logo-container">
                <img id="logoImage" src="logo/gagLogo.png" alt="Grow a Garden Logo" onerror="this.style.display='none'; document.getElementById('logoFallback').style.display='block';">
                <div id="logoFallback" style="display: none;">
                    <h1 style="font-size: 4em; color: #2E7D32; text-shadow: 2px 2px 4px rgba(0,0,0,0.2); margin: 0;">
                        GROW A GARDEN
                    </h1>
                </div>
            </div>
            <div class="welcome-section">
                <h1>Dashboard</h1>
                <div class="underline"></div>
                <p class="description">
                    Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>! You can now access all the features of the trading platform.
                </p>
                <button class="cta-button">Start Trading</button>
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
                <h1><b>Welcome Trader</b></h1>
                <div class="underline"></div>
                <p class="description">
                   In Grow a Garden on Roblox, players trade plants and pets to expand their gardens and collections. This platform helps you discover fair trades using up-to-date market data and accurate item values. Browse trade offers, compare plant and pet worth, and connect with reliable traders. All exchanges are completed manually in-game, ensuring every trade is safe, fair, and fun for everyone.
                </p>
                <button class="cta-button" id="getStartedBtn">Get Started</button>
            </div>
            <div class="about-section">
    <h2>About Us</h2>
    <div class="underline-small"></div>
    <p>
        Welcome to the <strong>Grow a Garden Trading Platform</strong> – your trusted companion for exploring fair and safe plant and pet trades in the Roblox <em>Grow a Garden</em> community. We know how confusing and risky informal trading can be, so our platform was designed to guide players toward smarter, safer trades.
    </p>
    <p>
        This platform provides tools to help you <strong>discover fair trade offers</strong> using up-to-date market data and accurate item values. You can <strong>browse available trades, compare plant and pet worth</strong>, and <strong>connect with reliable traders</strong> – all in one simple, organized space.
    </p>
    <p>
        Please note that <strong>all trades are completed manually within Roblox</strong>. Our platform does not process in-game trades directly; instead, it helps ensure fairness, transparency, and confidence before you trade.
    </p>
    <p>
        Whether you're growing your first garden or collecting rare pets, our mission is to create a trustworthy trading environment that empowers players to trade wisely, avoid scams, and enjoy the community safely.
    </p>
    <p style="margin-top: 30px; padding-top: 25px; border-top: 2px solid #E8F5E9;">
        <strong style="color: #2E7D32; font-size: 1.2em; display: block; margin-bottom: 15px;">Our Team</strong>
        <strong style="color: #4CAF50;">Project Lead & Developer:</strong> Reginald Tatulo – Oversees development, demo presentations, proposal revisions, and implements both backend and UI components.
        <br><br>
        <strong style="color: #4CAF50;">Tester & Documentation:</strong> Kim Adrian Magno – Conducts detailed testing, documents findings, and prepares proposals, diagrams, and final reports.
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
document.addEventListener('DOMContentLoaded', function() {
    const bgMusic = document.getElementById('bgMusic');
    const musicToggle = document.getElementById('musicToggle');
    let isPlaying = false;
    
    bgMusic.volume = 0.7;
    
    function updateButton(playing) {
        if (playing) {
            musicToggle.textContent = '🔊';
            musicToggle.classList.remove('muted');
            musicToggle.title = 'Mute Music';
        } else {
            musicToggle.textContent = '🔇';
            musicToggle.classList.add('muted');
            musicToggle.title = 'Play Music';
        }
    }
    
    // Start music on first user interaction
    const startMusic = function() {
        if (!isPlaying) {
            bgMusic.play().then(() => {
                isPlaying = true;
                updateButton(true);
            }).catch(() => {});
        }
    };
    
    document.addEventListener('click', startMusic, { once: true });
    
    // Toggle button functionality
    musicToggle.addEventListener('click', function(e) {
        e.stopPropagation();
        if (isPlaying) {
            bgMusic.pause();
            isPlaying = false;
        } else {
            bgMusic.play().then(() => {
                isPlaying = true;
            }).catch(() => {});
        }
        updateButton(isPlaying);
    });
    
    updateButton(false);
});

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
    });
    </script>
</body>
</html>