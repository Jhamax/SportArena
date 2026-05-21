<?php

require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/koneksi.php';

$query = mysqli_query($conn, "SELECT * FROM data_lapangan ORDER BY id_lapangan DESC");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';

?>

<style>
    .admin-page {
        background: #f8fafc;
        padding: 42px 0 70px;
    }

    .page-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 24px;
        padding: 26px;
    }

    .field-img {
        width: 90px;
        height: 65px;
        object-fit: cover;
        border-radius: 14px;
        background: #e5e7eb;
    }

    .status-badge {
        padding: 7px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        display: inline-block;
    }

    .status-tersedia {
        background: #dcfce7;
        color: #166534;
    }

    .status-tidak {
        background: #fee2e2;
        color: #991b1b;
    }

    .empty-state {
        border: 1px dashed #cbd5e1;
        border-radius: 18px;
        padding: 28px;
        text-align: center;
        background: #f8fafc;
        color: #64748b;
    }
</style>

<main class="flex-grow-1">

    <section class="admin-page">
        <div class="container">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="fw-bold mb-1">Kelola Lapangan</h1>
                    <p class="text-secondary mb-0">
                        Admin dapat menambah, mengubah, dan menghapus data lapangan olahraga.
                    </p>
                </div>

                <a href="tambah_lapangan.php" class="btn btn-success px-4">
                    <i class="bi bi-plus-circle"></i>
                    Tambah Lapangan
                </a>
            </div>

            <?php if (isset($_SESSION['success'])) : ?>
                <div class="alert alert-success">
                    <?= $_SESSION['success']; ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])) : ?>
                <div class="alert alert-danger">
                    <?= $_SESSION['error']; ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="page-card">

                <?php if (mysqli_num_rows($query) > 0) : ?>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Foto</th>
                                    <th>Nama Lapangan</th>
                                    <th>Jenis</th>
                                    <th>Harga/Jam</th>
                                    <th>Fasilitas</th>
                                    <th>Status</th>
                                    <th width="170">Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php while ($lapangan = mysqli_fetch_assoc($query)) : ?>

                                    <tr>
                                        <td>
                                            <?php if (!empty($lapangan['foto'])) : ?>
                                                <img 
                                                    src="../<?= htmlspecialchars($lapangan['foto']); ?>" 
                                                    class="field-img"
                                                    alt="Foto Lapangan"
                                                >
                                            <?php else : ?>
                                                <div class="field-img d-flex align-items-center justify-content-center">
                                                    <i class="bi bi-image text-secondary"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <div class="fw-bold">
                                                <?= htmlspecialchars($lapangan['nama_lapangan']); ?>
                                            </div>
                                            <small class="text-secondary">
                                                <?= htmlspecialchars($lapangan['deskripsi'] ?: 'Belum ada deskripsi'); ?>
                                            </small>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($lapangan['jenis_olahraga']); ?>
                                        </td>

                                        <td class="fw-semibold">
                                            Rp<?= number_format($lapangan['harga_per_jam'], 0, ',', '.'); ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($lapangan['fasilitas'] ?: '-'); ?>
                                        </td>

                                        <td>
                                            <?php if ($lapangan['status_ketersediaan'] == 'tersedia') : ?>
                                                <span class="status-badge status-tersedia">Tersedia</span>
                                            <?php else : ?>
                                                <span class="status-badge status-tidak">Tidak Tersedia</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <div class="d-flex gap-2">
                                                <a 
                                                    href="edit_lapangan.php?id=<?= $lapangan['id_lapangan']; ?>" 
                                                    class="btn btn-sm btn-warning"
                                                >
                                                    Edit
                                                </a>

                                                <form 
                                                    action="../process/lapangan_process.php" 
                                                    method="POST"
                                                    onsubmit="return confirm('Yakin ingin menghapus lapangan ini?');"
                                                >
                                                    <input type="hidden" name="aksi" value="hapus">
                                                    <input type="hidden" name="id_lapangan" value="<?= $lapangan['id_lapangan']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>

                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                <?php else : ?>

                    <div class="empty-state">
                        <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                        <h5 class="fw-bold">Belum ada data lapangan</h5>
                        <p class="mb-3">
                            Tambahkan data lapangan agar pengguna dapat melihat dan melakukan booking.
                        </p>
                        <a href="tambah_lapangan.php" class="btn btn-success">
                            Tambah Lapangan
                        </a>
                    </div>

                <?php endif; ?>

            </div>

        </div>
    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php include __DIR__ . '/../includes/scripts.php'; ?>