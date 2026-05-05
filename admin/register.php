<?php
session_start();
require_once '../admin/includes/config.php'; // $pdo connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // check if username exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $error = "Username already taken!";
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO users (username, password_hash, role, status) 
             VALUES (?, ?, ?, 'active')"
        );
        if ($stmt->execute([$username, $hashedPassword, $role])) {
            $success = "Registration successful. <a href='login.php'>Login here</a>";
        } else {
            $error = "Error creating account!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Bank Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="reg-body">

<div class="reg-container">
    <h2 class="reg-title">Create an Account</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="post" class="reg-form" autocomplete="off">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>

        <div class="form-group">
            <label>Role</label>
            <select name="role" required>
                <option value="teller">Teller</option>
                <option value="admin">Admin</option>
                <option value="manager">Manager</option>
                <option value="auditor">Auditor</option>
                <option value="support">Support</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Register</button>
    </form>

    <p class="auth-link">
        Already have an account? <a href="login.php">Login here</a>
    </p>
</div>

</body>
</html>
