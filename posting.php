<?php
include 'koneksi.php';
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Proses form posting jika disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_produk'])) {
    $user = $_SESSION['username'];
    $queryUser = mysqli_query($conn, "SELECT id FROM users WHERE username='$user'");
    $dataUser = mysqli_fetch_assoc($queryUser);
    $user_id = $dataUser['id'];

    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $isi = mysqli_real_escape_string($conn, $_POST['isi']);
    $harga = mysqli_real_escape_string($conn, $_POST['harga']);
    
    // Handle file upload
    $gambar = $_FILES['gambar']['name'];
    $tmp = $_FILES['gambar']['tmp_name'];
    $path = "uploads/" . basename($gambar);

    if (move_uploaded_file($tmp, $path)) {
        $query = "INSERT INTO posts (user_id, judul, isi, harga, gambar_path, created_at) 
                  VALUES ('$user_id', '$judul', '$isi', '$harga', '$path', NOW())";
        if (mysqli_query($conn, $query)) {
            $success = "Produk berhasil diposting!";
        } else {
            $error = "Gagal memposting produk: " . mysqli_error($conn);
        }
    } else {
        $error = "Gagal mengupload gambar.";
    }
}

// Ambil data produk untuk ditampilkan
$queryProduk = mysqli_query($conn, "SELECT posts.*, users.username 
                                   FROM posts 
                                   JOIN users ON posts.user_id = users.id 
                                   ORDER BY posts.created_at DESC");
$produkList = mysqli_fetch_all($queryProduk, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk</title>
    <link rel="stylesheet" href="style_produk.css">
</head>

<body>
    <h1>Daftar Produk</h1>

    <!-- Container untuk daftar produk -->
    <div class="produk-container">
        <?php foreach ($produkList as $produk): ?>
        <div class="produk-card">
            <img src="<?= $produk['gambar_path'] ?>" alt="<?= $produk['judul'] ?>" class="produk-img">
            <div class="produk-info">
                <h3><?= htmlspecialchars($produk['judul']) ?></h3>
                <p><?= htmlspecialchars($produk['isi']) ?></p>
                <div class="produk-harga">Rp <?= number_format($produk['harga'], 0, ',', '.') ?></div>
                <div class="produk-penjual">Diposting oleh: <?= htmlspecialchars($produk['username']) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Tombol + untuk memunculkan modal -->
    <a href="#" class="add-button" id="addButton">+</a>

    <!-- Modal untuk posting produk -->
    <div id="postModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Posting Produk Baru</h2>

            <?php if (isset($error)): ?>
            <p class="error"><?= $error ?></p>
            <?php endif; ?>

            <?php if (isset($success)): ?>
            <p class="success"><?= $success ?></p>
            <?php endif; ?>

            <form action="produk.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="judul">Judul:</label>
                    <input type="text" id="judul" name="judul" required>
                </div>

                <div class="form-group">
                    <label for="harga">Harga (Rp):</label>
                    <input type="number" id="harga" name="harga" required>
                </div>

                <div class="form-group">
                    <label for="isi">Deskripsi:</label>
                    <textarea id="isi" name="isi" required></textarea>
                </div>

                <div class="form-group">
                    <label for="gambar">Gambar Produk:</label>
                    <input type="file" id="gambar" name="gambar" accept="image/*" required>
                </div>

                <button type="submit" name="post_produk" class="submit-btn">Posting Produk</button>
            </form>
        </div>
    </div>

    <script>
    // Script untuk menampilkan/menyembunyikan modal
    const modal = document.getElementById("postModal");
    const btn = document.getElementById("addButton");
    const span = document.getElementsByClassName("close")[0];

    btn.onclick = function() {
        modal.style.display = "block";
    }

    span.onclick = function() {
        modal.style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // Reset form dan sembunyikan pesan sukses/error saat modal dibuka
    btn.addEventListener('click', function() {
        const form = document.querySelector('form');
        form.reset();
        document.querySelector('.error')?.remove();
        document.querySelector('.success')?.remove();
    });
    </script>
</body>

</html>