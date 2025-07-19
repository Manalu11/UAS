<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Proses POST produk baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_produk'])) {
    // Validasi CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }

    $user = $_SESSION['username'];
    $queryUser = mysqli_query($conn, "SELECT id, foto_profil FROM users WHERE username='$user'");
    $dataUser = mysqli_fetch_assoc($queryUser);

    if (!$dataUser) {
        echo "<script>alert('Gagal mendapatkan data user.');</script>";
        exit();
    }

    $user_id = $dataUser['id'];
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $isi = mysqli_real_escape_string($conn, $_POST['isi']);
    $harga = mysqli_real_escape_string($conn, $_POST['harga']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);

    // Validasi 
    
    
    $allowed_categories = ['benih', 'alat_tani', 'pupuk', 'pestisida', 'pakaian', 
                          'edukasi', 'perlengkapan', 'organik', 'gudang', 'hasil tani'];

    if (!in_array($kategori, $allowed_categories)) {
        echo "<script>alert('Kategori tidak valid.');</script>";
        exit();
    }

    // Upload gambar
    $gambar = $_FILES['gambar']['name'];
    $tmp = $_FILES['gambar']['tmp_name'];
    $type = $_FILES['gambar']['type'];
    $size = $_FILES['gambar']['size'];

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 2 * 1024 * 1024; // 2MB

    if (!in_array($type, $allowed_types)) {
        echo "<script>alert('Hanya file JPG, PNG, atau GIF yang diperbolehkan.');</script>";
    } elseif ($size > $max_size) {
        echo "<script>alert('Ukuran file maksimal 2MB.');</script>";
    } else {
        $ext = pathinfo($gambar, PATHINFO_EXTENSION);
        $uniqueName = uniqid('produk_', true) . '.' . $ext;
        $path = "uploads/produk/" . $uniqueName;

        // Buat direktori jika belum ada
        if (!is_dir('uploads/produk')) {
            mkdir('uploads/produk', 0777, true);
        }

        if (move_uploaded_file($tmp, $path)) {
            $query = "INSERT INTO posts (user_id, judul, isi, harga, gambar_path, kategori, created_at) 
                      VALUES ('$user_id', '$judul', '$isi', '$harga', '$path', '$kategori', NOW())";

            if (mysqli_query($conn, $query)) {
                echo "<script>
                        alert('Produk berhasil diposting!');
                        window.location.href = 'beranda.php';
                      </script>";
                exit();
            } else {
                echo "<script>alert('Gagal menyimpan data produk: " . mysqli_error($conn) . "');</script>";
            }
        } else {
            echo "<script>alert('Gagal mengunggah gambar.');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posting Produk - Petani Berdasi</title>
    <link rel="stylesheet" href="style_produk.css">
    <style>
    :root {
        --primary: #2e7d32;
        --secondary: #1b5e20;
        --light-green: #e8f5e9;
        --white: #ffffff;
        --dark-gray: #333;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background-color: var(--light-green);
        padding: 20px;
    }

    .container {
        max-width: 800px;
        margin: 0 auto;
        background-color: var(--white);
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
    }

    h1 {
        color: var(--primary);
        margin-bottom: 20px;
        text-align: center;
    }

    .form-group {
        margin-bottom: 20px;
    }

    label {
        display: block;
        margin-bottom: 8px;
        color: var(--dark-gray);
        font-weight: 500;
    }

    input[type="text"],
    input[type="number"],
    textarea,
    input[type="file"],
    select.form-control {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
        transition: border 0.3s;
    }

    input[type="text"]:focus,
    input[type="number"]:focus,
    textarea:focus,
    select.form-control:focus {
        border-color: var(--primary);
        outline: none;
    }

    textarea {
        min-height: 120px;
        resize: vertical;
    }

    select.form-control {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 10px center;
        background-size: 1em;
    }

    button[type="submit"] {
        background-color: var(--primary);
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        font-weight: 500;
        width: 100%;
        transition: background-color 0.3s;
    }

    button[type="submit"]:hover {
        background-color: var(--secondary);
    }

    .file-info {
        margin-top: 5px;
        font-size: 14px;
        color: #666;
    }

    .back-link {
        display: inline-block;
        margin-top: 20px;
        color: var(--primary);
        text-decoration: none;
        font-weight: 500;
    }

    .back-link i {
        margin-right: 5px;
    }

    @media (max-width: 600px) {
        .container {
            padding: 20px;
        }
    }
    </style>
</head>

<body>
    <div class="container">
        <h1><i class="fas fa-plus-circle"></i> Posting Produk Baru</h1>

        <form action="" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="form-group">
                <label for="judul">Judul Produk</label>
                <input type="text" id="judul" name="judul" placeholder="Contoh: Beras Organik 5kg" required>
            </div>

            <div class="form-group">
                <label for="harga">Harga (Rp)</label>
                <input type="number" id="harga" name="harga" placeholder="Contoh: 50000" required>
            </div>

            <div class="form-group">
                <label for="kategori">Kategori Produk</label>
                <select id="kategori" name="kategori" class="form-control" required>
                    <option value="" disabled selected>Pilih Kategori</option>
                    <option value="benih">Benih</option>
                    <option value="alat_tani">Alat Tani</option>
                    <option value="pupuk">Pupuk</option>
                    <option value="pestisida">Pestisida</option>
                    <option value="pakaian">Pakaian</option>
                    <option value="edukasi">Edukasi</option>
                    <option value="perlengkapan">Perlengkapan</option>
                    <option value="organik">Organik</option>
                    <option value="gudang">Gudang</option>
                    <option value="hasil tani">hasil tani</option>
                </select>
            </div>

            <div class="form-group">
                <label for="isi">Deskripsi Produk</label>
                <textarea id="isi" name="isi" placeholder="Deskripsikan produk Anda secara detail..."
                    required></textarea>
            </div>

            <div class="form-group">
                <label for="gambar">Gambar Produk</label>
                <input type="file" id="gambar" name="gambar" accept="image/*" required>
                <div class="file-info">Format: JPG/PNG, Maksimal 2MB</div>
            </div>

            <button type="submit" name="post_produk"><i class="fas fa-upload"></i> POSTING PRODUK</button>
        </form>

        <a href="beranda.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali ke Beranda</a>
    </div>
</body>

</html>