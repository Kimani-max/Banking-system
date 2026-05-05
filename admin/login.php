<?php
session_start();
require_once '../admin/includes/config.php'; // $pdo connection

$error = '';

// Auto-login with remember me cookie
if (!isset($_SESSION['user_id']) && !empty($_COOKIE['rememberme'])) {
    $token = $_COOKIE['rememberme'];

    $stmt = $pdo->prepare("SELECT user_id, username, role 
                           FROM users 
                           WHERE remember_token = ? AND status = 'active'");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['user_id']  = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role'];

        switch ($user['role']) {
            case 'admin':    header("Location: dashboards/admin_dashboard.php"); break;
            case 'teller':   header("Location: dashboards/staff_dashboard.php"); break;
            case 'manager':  header("Location: dashboards/manager_dashboard.php"); break;
            case 'auditor':  header("Location: dashboards/auditor_dashboard.php"); break;
            case 'support':  header("Location: dashboards/support_dashboard.php"); break;
            default:         header("Location: login.php"); break;
        }
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    try {
        $stmt = $pdo->prepare("SELECT user_id, username, password_hash, role 
                               FROM users 
                               WHERE username = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id']  = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie("rememberme", $token, time() + (86400 * 30), "/", "", true, true);
                $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE user_id = ?");
                $stmt->execute([$token, $user['user_id']]);
            }

            switch ($user['role']) {
                case 'admin':    header("Location: dashboards/admin_dashboard.php"); break;
                case 'teller':   header("Location: dashboards/staff_dashboard.php"); break;
                case 'manager':  header("Location: dashboards/manager_dashboard.php"); break;
                case 'auditor':  header("Location: dashboards/auditor_dashboard.php"); break;
                case 'support':  header("Location: dashboards/support_dashboard.php"); break;
                default:         header("Location: login.php"); break;
            }
            exit;
        } else {
            $error = "Invalid username or password, or inactive account.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Bank Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-body">

    <div class="auth-container">
        <div class="auth-card">
            <h2 class="auth-title">Welcome Back</h2>
            <p class="auth-subtitle">Login to continue to your account</p>

            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="post" autocomplete="off" class="auth-form">
                <label>Username</label>
                <input type="text" name="username" class="input" required>

                <label>Password</label>
                <input type="password" name="password" class="input" required>

                <div class="remember-me">
                    <input type="checkbox" name="remember" id="remember">
                    <label for="remember">Remember Me</label>
                </div>

                <button type="submit" class="btn btn-primary full-width">Login</button>
            </form>

            <p class="auth-footer">
                Don’t have an account? <a href="register.php">Signup</a>
            </p>
        </div>
    </div>

</body>
</html>
