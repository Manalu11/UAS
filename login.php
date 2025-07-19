<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - Petani Berdasi</title>
    <link rel="stylesheet" href="style_login.css" />
</head>

<body>
    <div class="login-container">
        <h2>Login</h2>
        <?php if (isset($_GET['error'])): ?>
        <div class="error-message">
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
        <?php endif; ?>
        <form action="proses_login.php" method="post" class="login-form">
            <input type="text" name="username" placeholder="Username" required><br />
            <input type="password" name="password" placeholder="Password" required><br />
            <button type="submit">Login</button>
        </form>
        <p>Belum punya akun? <a href="register.php">Daftar di sini</a></p>
    </div>
</body>

</html>