<?php session_start(); ?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Petani Berdasi</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f5f5f5;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }

    .register-container {
        background-color: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 400px;
    }

    h2 {
        text-align: center;
        color: #2c3e50;
        margin-bottom: 1.5rem;
    }

    .error-message {
        color: #e74c3c;
        background-color: #fadbd8;
        padding: 0.75rem;
        border-radius: 4px;
        margin-bottom: 1rem;
        font-size: 0.9rem;
    }

    .success-message {
        color: #27ae60;
        background-color: #d5f5e3;
        padding: 0.75rem;
        border-radius: 4px;
        margin-bottom: 1rem;
        font-size: 0.9rem;
    }

    input {
        width: 100%;
        padding: 0.75rem;
        margin-bottom: 1rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
    }

    button {
        width: 100%;
        padding: 0.75rem;
        background-color: #2ecc71;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
        margin-top: 0.5rem;
    }

    button:hover {
        background-color: #27ae60;
    }

    .login-link {
        text-align: center;
        margin-top: 1rem;
        color: #7f8c8d;
    }

    .login-link a {
        color: #3498db;
        text-decoration: none;
    }

    .login-link a:hover {
        text-decoration: underline;
    }

    label {
        display: block;
        margin-bottom: 0.5rem;
        color: #7f8c8d;
        font-size: 0.9rem;
    }
    </style>
</head>

<body>
    <div class="register-container">
        <h2>Daftar Akun Baru</h2>

        <?php
        if (isset($_SESSION['errors'])) {
            echo '<div class="error-message">';
            foreach ($_SESSION['errors'] as $error) {
                echo "<p>$error</p>";
            }
            echo '</div>';
            unset($_SESSION['errors']);
        }

        if (isset($_SESSION['success'])) {
            echo '<div class="success-message">';
            echo "<p>{$_SESSION['success']}</p>";
            echo '</div>';
            unset($_SESSION['success']);
        }
        ?>

        <form action="proses_register.php" method="post" enctype="multipart/form-data">
            <input type="text" name="username" placeholder="Username" required
                value="<?php echo htmlspecialchars($_SESSION['old_input']['username'] ?? ''); ?>">

            <input type="email" name="email" placeholder="Email" required
                value="<?php echo htmlspecialchars($_SESSION['old_input']['email'] ?? ''); ?>">

            <input type="text" name="nama_lengkap" placeholder="Nama Lengkap" required
                value="<?php echo htmlspecialchars($_SESSION['old_input']['nama_lengkap'] ?? ''); ?>">

            <input type="password" name="password" placeholder="Password" required>
            <input type="password" name="password_plain" placeholder="Konfirmasi Password" required>

            <label for="foto_profil">Foto Profil (maks 2MB)</label>
            <input type="file" name="foto_profil" accept="image/*" required>
            <label for="nomor_wa">Nomor WhatsApp</label>
            <input type="text" id="nomor_wa" name="nomor_wa" placeholder="08xxxxxxxxxx" required>


            <button type="submit">Daftar</button>
        </form>

        <p class="login-link">Sudah punya akun? <a href="login.php">Login di sini</a></p>
    </div>
</body>

</html>