<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Validasi akses admin
if (!isAdmin()) {
    header("Location: ../../auth/login.php");
    exit();
}

$errors = [];
$success_message = '';

// Ambil semua promo dari database
$promos = query("SELECT * FROM promos ORDER BY start_date DESC")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_promo'])) {
        // Tambah promo baru
        $name = sanitize_input($_POST['name']);
        $description = sanitize_input($_POST['description']);
        $discount_type = sanitize_input($_POST['discount_type']);
        $discount_value = sanitize_input($_POST['discount_value']);
        $start_date = sanitize_input($_POST['start_date']);
        $end_date = sanitize_input($_POST['end_date']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Validasi
        if (empty($name)) {
            $errors['name'] = 'Nama promo harus diisi';
        }
        
        if (empty($discount_value) || !is_numeric($discount_value) || $discount_value <= 0) {
            $errors['discount_value'] = 'Nilai diskon harus berupa angka lebih dari 0';
        }
        
        if (empty($start_date)) {
            $errors['start_date'] = 'Tanggal mulai harus diisi';
        }
        
        if (empty($end_date)) {
            $errors['end_date'] = 'Tanggal selesai harus diisi';
        } elseif (strtotime($end_date) < strtotime($start_date)) {
            $errors['end_date'] = 'Tanggal selesai tidak boleh sebelum tanggal mulai';
        }
        
        if (empty($errors)) {
            $query = "INSERT INTO promos (name, description, discount_type, discount_value, start_date, end_date, is_active) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssdssi", $name, $description, $discount_type, $discount_value, $start_date, $end_date, $is_active);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = 'Promo berhasil ditambahkan';
                header("Location: promo.php");
                exit();
            } else {
                $errors['database'] = 'Gagal menambahkan promo: ' . $conn->error;
            }
        }
    } elseif (isset($_POST['update_status'])) {
        // Update status promo
        $promo_id = (int)$_POST['promo_id'];
        $is_active = (int)$_POST['is_active'];
        
        $query = "UPDATE promos SET is_active = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $is_active, $promo_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = 'Status promo berhasil diperbarui';
            header("Location: promo.php");
            exit();
        } else {
            $errors['database'] = 'Gagal memperbarui status promo: ' . $conn->error;
        }
    } elseif (isset($_POST['delete_promo'])) {
        // Hapus promo
        $promo_id = (int)$_POST['promo_id'];
        
        $query = "DELETE FROM promos WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $promo_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = 'Promo berhasil dihapus';
            header("Location: promo.php");
            exit();
        } else {
            $errors['database'] = 'Gagal menghapus promo: ' . $conn->error;
        }
    }
}

// Ambil pesan sukses dari session jika ada
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Promo - Petani Berdasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>

<body>
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="main-content">
        <?php include '../../includes/navbar_admin.php'; ?>

        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Pengaturan Promo</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPromoModal">
                    <i class="fas fa-plus"></i> Tambah Promo
                </button>
            </div>

            <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?= $success_message ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nama Promo</th>
                                    <th>Deskripsi</th>
                                    <th>Diskon</th>
                                    <th>Periode</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($promos as $promo): ?>
                                <tr>
                                    <td><?= htmlspecialchars($promo['name']) ?></td>
                                    <td><?= htmlspecialchars($promo['description']) ?></td>
                                    <td>
                                        <?= $promo['discount_type'] === 'percentage' ? 
                                            $promo['discount_value'] . '%' : 
                                            'Rp ' . number_format($promo['discount_value'], 0, ',', '.') ?>
                                    </td>
                                    <td>
                                        <?= date('d M Y', strtotime($promo['start_date'])) ?> -
                                        <?= date('d M Y', strtotime($promo['end_date'])) ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $promo['is_active'] ? 'success' : 'secondary' ?>">
                                            <?= $promo['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline-block;">
                                            <input type="hidden" name="promo_id" value="<?= $promo['id'] ?>">
                                            <input type="hidden" name="is_active"
                                                value="<?= $promo['is_active'] ? 0 : 1 ?>">
                                            <button type="submit" name="update_status"
                                                class="btn btn-sm btn-<?= $promo['is_active'] ? 'warning' : 'success' ?>">
                                                <i class="fas fa-<?= $promo['is_active'] ? 'times' : 'check' ?>"></i>
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline-block;"
                                            onsubmit="return confirm('Apakah Anda yakin ingin menghapus promo ini?')">
                                            <input type="hidden" name="promo_id" value="<?= $promo['id'] ?>">
                                            <button type="submit" name="delete_promo" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Promo -->
    <div class="modal fade" id="addPromoModal" tabindex="-1" aria-labelledby="addPromoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addPromoModalLabel">Tambah Promo Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Promo</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="discount_type" class="form-label">Tipe Diskon</label>
                                <select class="form-select" id="discount_type" name="discount_type" required>
                                    <option value="percentage">Persentase (%)</option>
                                    <option value="fixed">Nominal Tetap (Rp)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="discount_value" class="form-label">Nilai Diskon</label>
                                <input type="number" class="form-control" id="discount_value" name="discount_value"
                                    step="0.01" min="0" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label">Tanggal Mulai</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label">Tanggal Selesai</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required>
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                            <label class="form-check-label" for="is_active">Aktif</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" name="add_promo" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        // Set tanggal minimal untuk end_date berdasarkan start_date
        $('#start_date').change(function() {
            $('#end_date').attr('min', $(this).val());
        });
    });
    </script>
</body>

</html>