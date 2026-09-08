<?php
require_once __DIR__ . '/auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['password'] ?? '') === adminPassword()) {
        $_SESSION['is_admin'] = true;
        header('Location: index.php');
        exit;
    }
    $error = 'Incorrect password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="card-box" style="max-width: 360px;">
        <h1>Admin Login</h1>
        <?php if ($error): ?>
            <div class="errors"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required autofocus>
            <button type="submit">Log In</button>
        </form>
    </div>
</body>
</html>
