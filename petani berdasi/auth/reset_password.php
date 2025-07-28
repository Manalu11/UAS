<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

// Validate token
if ($token) {
    $result = $conn->query("SELECT id, token_expiry FROM users WHERE reset_token='$token'");
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Check if token expired
        if (strtotime($user['token_expiry']) < time()) {
            $error = "Link has expired";
            $conn->query("UPDATE users SET reset_token=NULL, token_expiry=NULL WHERE id={$user['id']}");
        }
    } else {
        $error = "Invalid reset link";
    }
} else {
    $error = "No token provided";
}

// Process reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    
    // Simple validation
    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters";
    } elseif ($password !== $confirm) {
        $error = "Passwords don't match";
    } else {
        // Update password
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password='$hashed', reset_token=NULL, token_expiry=NULL WHERE reset_token='$token'");
        
        $success = "Password updated! <a href='login.php'>Login now</a>";
        log_action("PASSWORD_RESET", $user['id']);
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>New Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h2>Set New Password</h2>

        <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
        <?php elseif (empty($error)): ?>
        <form method="POST">
            <div class="mb-3">
                <label>New Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary">Update Password</button>
        </form>
        <?php endif; ?>
    </div>
</body>

</html>