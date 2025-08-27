<?php
session_start();
require_once '../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $db = new Database();
            
            // Check if email already exists
            $existingUser = $db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
            if ($existingUser) {
                $error = 'An account with this email already exists.';
            } else {
                // Create new user
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $db->query(
                    "INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)",
                    [$name, $email, $passwordHash]
                );
                
                $success = 'Account created successfully! You can now log in.';
                
                // Auto-login the user
                $user = $db->fetch("SELECT * FROM users WHERE email = ?", [$email]);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                
                // Redirect to dashboard
                header('Location: ../dashboard.php');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Registration failed. Please try again.';
            error_log("Registration error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - ResumeAI</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh;">
    <div class="form-container">
        <div style="text-align: center; margin-bottom: 30px;">
            <a href="../index.php" class="nav-brand" style="text-decoration: none;">
                <i class="fas fa-file-alt"></i>
                <span>ResumeAI</span>
            </a>
        </div>
        
        <h2 class="form-title">Create Your Account</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" id="name" name="name" class="form-input" 
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" class="form-input" 
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-input" 
                       placeholder="Minimum 6 characters" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
            </div>
            
            <button type="submit" class="form-submit">
                <i class="fas fa-user-plus"></i>
                Create Account
            </button>
        </form>
        
        <div class="form-link">
            Already have an account? <a href="login.php">Sign in here</a>
        </div>
        
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
            <p style="color: #666; font-size: 14px;">
                By creating an account, you agree to our Terms of Service and Privacy Policy.
            </p>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>