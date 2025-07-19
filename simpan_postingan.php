<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include file koneksi database
require_once 'koneksi.php';

// Fungsi untuk menyimpan file gambar
function uploadGambar($file) {
    $targetDir = "uploads/";
    $fileName = uniqid() . '_' . basename($file["name"]);
    $targetFile = $targetDir . $fileName;
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // Validasi file gambar
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return ['error' => 'File yang diupload bukan gambar'];
    }

    // Batasi ukuran file (max 2MB)
    if ($file["size"] > 2000000) {
        return ['error' => 'Ukuran gambar terlalu besar (maks 2MB)'];
    }

    // Batasi format file
    $allowedFormats = ["jpg", "jpeg", "png", "gif"];
    if (!in_array($imageFileType, $allowedFormats)) {
        return ['error' => 'Hanya format JPG, JPEG, PNG & GIF yang diizinkan'];
    }

    // Coba upload file
    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        return ['success' => $fileName];
    } else {
        return ['error' => 'Terjadi kesalahan saat mengupload gambar'];
    }
}

// Proses data form jika metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi input
    $errors = [];
    $judul = trim($_POST['judul'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $harga = trim($_POST['harga'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $gambar = $_FILES['gambar'] ?? null;

    if (empty($judul)) {
        $errors[] = "Judul produk harus diisi";
    }

    if (empty($kategori)) {
        $errors[] = "Kategori harus dipilih";
    }

    if (!is_numeric($harga) || $harga < 0) {
        $errors[] = "Harga harus berupa angka positif";
    }

    if (empty($deskripsi)) {
        $errors[] = "Deskripsi harus diisi";
    }

    if (!$gambar || $gambar['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Gambar produk harus diupload";
    }

    // Jika tidak ada error, proses data
    if (empty($errors)) {
        // Upload gambar
        $uploadResult = uploadGambar($gambar);
        
        if (isset($uploadResult['error'])) {
            $errors[] = $uploadResult['error'];
        } else {
            // Simpan data ke database
            $namaGambar = $uploadResult['success'];
            $user_id = $_SESSION['user_id'];
            
            $stmt = $conn->prepare("INSERT INTO posts (user_id, judul, deskripsi, harga, kategori, gambar) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issdss", $user_id, $judul, $deskripsi, $harga, $kategori, $namaGambar);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Postingan berhasil ditambahkan!";
                header("Location: produk.php");
                exit();
            } else {
                $errors[] = "Gagal menyimpan postingan: " . $conn->error;
            }
        }
    }

    // Jika ada error, simpan ke session dan redirect kembali
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old_input'] = [
            'judul' => $judul,
            'kategori' => $kategori,
            'harga' => $harga,
            'deskripsi' => $deskripsi
        ];
        header("Location: produk.php");
        exit();
    }
} else {
    // Jika bukan metode POST, redirect
    header("Location: produk.php");
    exit();
}
?>