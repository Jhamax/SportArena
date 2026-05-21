<?php

require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_GET['id'])) {
    header("Location: lapangan.php");
    exit;
}

$id_lapangan = (int) $_GET['id'];

$stmt = mysqli_prepare($conn, "SELECT * FROM data_lapangan WHERE id_lapangan = ?");
mysqli_stmt_bind_param($stmt, "i", $id_lapangan);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$lapangan = mysqli_fetch_assoc($result);

if (!$lapangan) {
    $_SESSION['error'] = "Data lapangan tidak ditemukan.";
    header("Location: lapangan.php");
    exit;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';

?>

<style>
    .admin-page {
        background: #f8fafc;
        padding: 42px 0 70px;
    }

    .form-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 24px;
        padding: 30px;
    }

    .form-label {
        font-weight: 600;
        color: #374151;
    }

    .form-control,
    .form-select {
        border-radius: 14px;
        padding: 12px 14px;
    }

    .preview-img {
        width: 180px;
        height: 120px;
        object-fit: cover;
        border-radius: 18px;
        background: #e5e7eb;
        border: 1px solid #e5e7eb;
    }
</style>

<main class="flex-grow-1">

    <section class="admin-page">
        <div class="container">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="fw-bold mb-1">Edit Lapangan</h1>
                    <p class="text-secondary mb-0">
                        Perbarui data lapangan yang sudah tersimpan.
                    </p>
                </div>

                <a href="lapangan.php" class="btn btn-outline-secondary">
                    Kembali
                </a>
            </div>

            <div class="form-card">

                <form action="../process/lapangan_process.php" method="POST" enctype="multipart/form-data">

                    <input type="hidden" name="aksi" value="edit">
                    <input type="hidden" name="id_lapangan" value="<?= $lapangan['id_lapangan']; ?>">
                    <input type="hidden" name="foto_lama" value="<?= htmlspecialchars($lapangan['foto']); ?>">

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Lapangan</label>
                            <input 
                                type="text" 
                                name="nama_lapangan" 
                                class="form-control" 
                                value="<?= htmlspecialchars($lapangan['nama_lapangan']); ?>"
                                required
                            >
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jenis Olahraga</label>
                            <select name="jenis_olahraga" class="form-select" required>
                                <option value="Futsal" <?= $lapangan['jenis_olahraga'] == 'Futsal' ? 'selected' : ''; ?>>Futsal</option>
                                <option value="Badminton" <?= $lapangan['jenis_olahraga'] == 'Badminton' ? 'selected' : ''; ?>>Badminton</option>
                                <option value="Basket" <?= $lapangan['jenis_olahraga'] == 'Basket' ? 'selected' : ''; ?>>Basket</option>
                                <option value="Padel" <?= $lapangan['jenis_olahraga'] == 'Padel' ? 'selected' : ''; ?>>Padel</option>
                            </select>
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Harga Per Jam</label>
                            <input 
                                type="number" 
                                name="harga_per_jam" 
                                class="form-control" 
                                value="<?= htmlspecialchars($lapangan['harga_per_jam']); ?>"
                                min="0"
                                required
                            >
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status Ketersediaan</label>
                            <select name="status_ketersediaan" class="form-select" required>
                                <option value="tersedia" <?= $lapangan['status_ketersediaan'] == 'tersedia' ? 'selected' : ''; ?>>
                                    Tersedia
                                </option>
                                <option value="tidak tersedia" <?= $lapangan['status_ketersediaan'] == 'tidak tersedia' ? 'selected' : ''; ?>>
                                    Tidak Tersedia
                                </option>
                            </select>
                        </div>

                    </div>

                    <div class="mb-3">
                        <label class="form-label">Fasilitas</label>
                        <textarea 
                            name="fasilitas" 
                            class="form-control" 
                            rows="3"
                        ><?= htmlspecialchars($lapangan['fasilitas']); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea 
                            name="deskripsi" 
                            class="form-control" 
                            rows="4"
                        ><?= htmlspecialchars($lapangan['deskripsi']); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label d-block">Foto Saat Ini</label>

                        <?php if (!empty($lapangan['foto'])) : ?>
                            <img 
                                src="../<?= htmlspecialchars($lapangan['foto']); ?>" 
                                class="preview-img"
                                alt="Foto Lapangan"
                            >
                        <?php else : ?>
                            <div class="preview-img d-flex align-items-center justify-content-center">
                                <i class="bi bi-image text-secondary fs-2"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Ganti Foto</label>
                        <input 
                            type="file" 
                            name="foto" 
                            class="form-control"
                            accept="image/jpeg,image/png,image/jpg,image/webp"
                        >
                        <small class="text-secondary">
                            Kosongkan jika tidak ingin mengganti foto.
                        </small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success px-4">
                            Simpan Perubahan
                        </button>

                        <a href="lapangan.php" class="btn btn-outline-secondary px-4">
                            Batal
                        </a>
                    </div>

                </form>

            </div>

        </div>
    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php include __DIR__ . '/../includes/scripts.php'; ?>