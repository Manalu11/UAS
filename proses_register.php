<?php
session_start();
include 'koneksi.php';

$errors = [];
$_SESSION['old_input'] = $_POST;

// Validasi dasar
if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['nama_lengkap']) || empty($_POST['password']) || empty($_POST['password_plain']) || empty($_POST['nomor_wa'])) {
    $errors[] = 'Semua field wajib diisi.';
}

if ($_POST['password'] !== $_POST['password_plain']) {
    $errors[] = 'Password dan konfirmasi tidak cocok.';
}

if (!preg_match('/^08[0-9]{8,11}$/', $_POST['nomor_wa'])) {
    $errors[] = 'Format nomor WhatsApp tidak valid.';
}

if (!empty($_FILES['foto_profil']['name'])) {
    $foto = $_FILES['foto_profil'];
    $allowed_types = ['image/jpeg', 'image/png'];
    $max_size = 2 * 1024 * 1024;

    if (!in_array($foto['type'], $allowed_types)) {
        $errors[] = 'Hanya file JPG atau PNG yang diizinkan.';
    }

    if ($foto['size'] > $max_size) {
        $errors[] = 'Ukuran foto maksimal 2MB.';
    }
} else {
    $errors[] = 'Foto profil wajib diunggah.';
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header("Location: register.php");
    exit;
}

// Semua valid, simpan ke database
$username = mysqli_real_escape_string($conn, $_POST['username']);
$email = mysqli_real_escape_string($conn, $_POST['email']);
$nama = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$nomor_wa = mysqli_real_escape_string($conn, $_POST['nomor_wa']);

// Upload foto profil
$ext = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
$uniqueName = uniqid('profil_', true) . '.' . $ext;
$path = 'uploads/profil/' . $uniqueName;

if (!move_uploaded_file($_FILES['foto_profil']['tmp_name'], $path)) {
    $_SESSION['errors'] = ['Gagal mengunggah foto profil.'];
    header("Location: register.php");
    exit;
}

$query = "INSERT INTO users (username, email, nama_lengkap, password, foto_profil, nomor_wa) 
          VALUES ('$username', '$email', '$nama', '$password', '$path', '$nomor_wa')";

if (mysqli_query($conn, $query)) {
    unset($_SESSION['old_input']);
    $_SESSION['success'] = 'Pendaftaran berhasil! Silakan login.';
    header("Location: register.php");
    exit;
} else {
    $_SESSION['errors'] = ['Gagal mendaftar. Silakan coba lagi.'];
    header("Location: register.php");
    exit;
}