<?php

require_once __DIR__ . '/../includes/auth_user.php';
require_once __DIR__ . '/../config/koneksi.php';

$id_user = $_SESSION['id_user'];

/* =========================
   PAGINATION
========================= */
$limit = 10;
$page = (int) ($_GET['page'] ?? 1);

if ($page < 1) {
    $page = 1;
}

/* Hitung total notifikasi */
$total_notifikasi = 0;

$query_total = mysqli_prepare($conn, "
    SELECT COUNT(*)
    FROM data_notifikasi
    WHERE id_user = ?
");

mysqli_stmt_bind_param($query_total, "i", $id_user);
mysqli_stmt_execute($query_total);
mysqli_stmt_bind_result($query_total, $total_notifikasi);
mysqli_stmt_fetch($query_total);
mysqli_stmt_close($query_total);

$total_halaman = ceil($total_notifikasi / $limit);

if ($total_halaman < 1) {
    $total_halaman = 1;
}

if ($page > $total_halaman) {
    $page = $total_halaman;
}

$offset = ($page - 1) * $limit;

/* Ambil notifikasi sesuai halaman */
$query_notifikasi = mysqli_prepare($conn, "
    SELECT 
        n.id_notifikasi,
        n.id_booking,
        n.judul,
        n.pesan,
        n.status_baca,
        n.created_at,
        b.kode_booking
    FROM data_notifikasi n
    LEFT JOIN data_booking b ON n.id_booking = b.id_booking
    WHERE n.id_user = ?
    ORDER BY n.created_at DESC
    LIMIT ? OFFSET ?
");

mysqli_stmt_bind_param($query_notifikasi, "iii", $id_user, $limit, $offset);
mysqli_stmt_execute($query_notifikasi);
$result_notifikasi = mysqli_stmt_get_result($query_notifikasi);

/* Hitung notifikasi belum dibaca */
$total_belum_dibaca = 0;

$query_count = mysqli_prepare($conn, "
    SELECT COUNT(*) 
    FROM data_notifikasi 
    WHERE id_user = ? 
    AND status_baca = 'belum dibaca'
");

mysqli_stmt_bind_param($query_count, "i", $id_user);
mysqli_stmt_execute($query_count);
mysqli_stmt_bind_result($query_count, $total_belum_dibaca);
mysqli_stmt_fetch($query_count);
mysqli_stmt_close($query_count);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';

?>

<style>
    .notification-page {
        background: #f8fafc;
        padding: 42px 0 70px;
    }

    .page-hero {
        background: linear-gradient(135deg, #166534, #0f172a);
        color: white;
        border-radius: 28px;
        padding: 34px;
        margin-bottom: 28px;
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.18);
    }

    .page-hero p {
        color: #d1fae5;
    }

    .content-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 24px;
        padding: 26px;
    }

    .notification-item {
        border: 1px solid #e5e7eb;
        border-radius: 20px;
        padding: 22px;
        margin-bottom: 16px;
        background: white;
        transition: 0.25s ease;
    }

    .notification-item:hover {
        border-color: #198754;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
    }

    .notification-unread {
        background: #ecfdf5;
        border-color: #bbf7d0;
    }

    .notification-icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        background: #dcfce7;
        color: #166534;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        flex-shrink: 0;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 7px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.2;
        white-space: nowrap;
        text-align: center;
    }

    .status-unread {
        background: #dcfce7;
        color: #166534;
    }

    .status-read {
        background: #e5e7eb;
        color: #374151;
    }

    .empty-state {
        border: 1px dashed #cbd5e1;
        border-radius: 18px;
        padding: 38px;
        text-align: center;
        background: #f8fafc;
        color: #64748b;
    }

    .pagination-wrap {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 26px;
    }

    .pagination-link {
        min-width: 42px;
        height: 42px;
        border-radius: 12px;
        border: 1px solid #d1d5db;
        background: white;
        color: #166534;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        transition: 0.2s ease;
        padding: 0 12px;
    }

    .pagination-link:hover {
        background: #ecfdf5;
        border-color: #198754;
        color: #166534;
    }

    .pagination-link.active {
        background: #198754;
        border-color: #198754;
        color: white;
    }

    .pagination-link.disabled {
        background: #f3f4f6;
        color: #9ca3af;
        pointer-events: none;
    }

    .notification-meta {
        color: #64748b;
        font-size: 14px;
    }
</style>

<main class="flex-grow-1">

    <section class="notification-page">
        <div class="container">

            <div class="page-hero">
                <div class="row align-items-center gy-3">
                    <div class="col-lg-8">
                        <span class="badge bg-light text-success px-3 py-2 mb-3">
                            Notifikasi
                        </span>

                        <h1 class="fw-bold mb-2">
                            Informasi terbaru tentang booking kamu.
                        </h1>

                        <p class="mb-0">
                            Pantau perubahan status booking, pembayaran, dan verifikasi dari admin.
                        </p>
                    </div>

                    <div class="col-lg-4 text-lg-end">
                        <a href="dashboard.php" class="btn btn-light fw-semibold px-4">
                            Kembali ke Dashboard
                        </a>
                    </div>
                </div>
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

            <div class="content-card">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                    <div>
                        <h4 class="fw-bold mb-1">
                            Daftar Notifikasi
                        </h4>

                        <p class="text-secondary mb-0">
                            <?= $total_belum_dibaca; ?> notifikasi belum dibaca.
                            <?php if ($total_notifikasi > 0) : ?>
                                <br>
                                <span class="notification-meta">
                                    Menampilkan halaman <?= $page; ?> dari <?= $total_halaman; ?>.
                                    Total <?= $total_notifikasi; ?> notifikasi.
                                </span>
                            <?php endif; ?>
                        </p>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">

                        <?php if ($total_belum_dibaca > 0) : ?>
                            <form action="../process/notifikasi_process.php" method="POST">
                                <input type="hidden" name="aksi" value="baca_semua">
                                <button type="submit" class="btn btn-success">
                                    Tandai Semua Dibaca
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if ($total_notifikasi > 0) : ?>
                            <form
                                action="../process/notifikasi_process.php"
                                method="POST"
                                onsubmit="return confirm('Yakin ingin menghapus semua notifikasi?');">
                                <input type="hidden" name="aksi" value="hapus_semua">
                                <button type="submit" class="btn btn-outline-danger">
                                    Hapus Semua
                                </button>
                            </form>
                        <?php endif; ?>

                    </div>
                    
                </div>

                <?php if (mysqli_num_rows($result_notifikasi) > 0) : ?>

                    <?php while ($notifikasi = mysqli_fetch_assoc($result_notifikasi)) : ?>

                        <div class="notification-item <?= $notifikasi['status_baca'] == 'belum dibaca' ? 'notification-unread' : ''; ?>">

                            <div class="d-flex gap-3 align-items-start">

                                <div class="notification-icon">
                                    <i class="bi bi-bell"></i>
                                </div>

                                <div class="flex-grow-1">

                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">

                                        <div>
                                            <h5 class="fw-bold mb-1">
                                                <?= htmlspecialchars($notifikasi['judul']); ?>
                                            </h5>

                                            <?php if (!empty($notifikasi['kode_booking'])) : ?>
                                                <small class="text-secondary">
                                                    Kode Booking:
                                                    <strong><?= htmlspecialchars($notifikasi['kode_booking']); ?></strong>
                                                </small>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($notifikasi['status_baca'] == 'belum dibaca') : ?>
                                            <span class="status-badge status-unread">
                                                Belum Dibaca
                                            </span>
                                        <?php else : ?>
                                            <span class="status-badge status-read">
                                                Dibaca
                                            </span>
                                        <?php endif; ?>

                                    </div>

                                    <p class="text-secondary mb-3">
                                        <?= nl2br(htmlspecialchars($notifikasi['pesan'])); ?>
                                    </p>

                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                                        <small class="text-secondary">
                                            <?= date('d M Y H:i', strtotime($notifikasi['created_at'])); ?>
                                        </small>

                                        <div class="d-flex gap-2 flex-wrap">

                                            <?php if (!empty($notifikasi['kode_booking'])) : ?>
                                                <a
                                                    href="detail_riwayat.php?kode=<?= urlencode($notifikasi['kode_booking']); ?>"
                                                    class="btn btn-sm btn-outline-success">
                                                    Lihat Booking
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($notifikasi['status_baca'] == 'belum dibaca') : ?>
                                                <form action="../process/notifikasi_process.php" method="POST">
                                                    <input type="hidden" name="aksi" value="baca_satu">
                                                    <input type="hidden" name="id_notifikasi" value="<?= $notifikasi['id_notifikasi']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        Tandai Dibaca
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <form
                                                action="../process/notifikasi_process.php"
                                                method="POST"
                                                onsubmit="return confirm('Yakin ingin menghapus notifikasi ini?');">
                                                <input type="hidden" name="aksi" value="hapus">
                                                <input type="hidden" name="id_notifikasi" value="<?= $notifikasi['id_notifikasi']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    Hapus
                                                </button>
                                            </form>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    <?php endwhile; ?>

                    <?php if ($total_halaman > 1) : ?>

                        <div class="pagination-wrap">

                            <?php if ($page > 1) : ?>
                                <a
                                    href="notifikasi.php?page=<?= $page - 1; ?>"
                                    class="pagination-link">
                                    &laquo;
                                </a>
                            <?php else : ?>
                                <span class="pagination-link disabled">
                                    &laquo;
                                </span>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_halaman; $i++) : ?>

                                <a
                                    href="notifikasi.php?page=<?= $i; ?>"
                                    class="pagination-link <?= $i == $page ? 'active' : ''; ?>">
                                    <?= $i; ?>
                                </a>

                            <?php endfor; ?>

                            <?php if ($page < $total_halaman) : ?>
                                <a
                                    href="notifikasi.php?page=<?= $page + 1; ?>"
                                    class="pagination-link">
                                    &raquo;
                                </a>
                            <?php else : ?>
                                <span class="pagination-link disabled">
                                    &raquo;
                                </span>
                            <?php endif; ?>

                        </div>

                    <?php endif; ?>

                <?php else : ?>

                    <div class="empty-state">
                        <i class="bi bi-bell-slash fs-1 d-block mb-3"></i>
                        <h5 class="fw-bold">Belum ada notifikasi</h5>
                        <p class="mb-3">
                            Notifikasi akan muncul setelah kamu melakukan booking atau setelah admin memverifikasi pembayaran.
                        </p>
                        <a href="lapangan.php" class="btn btn-success">
                            Booking Lapangan
                        </a>
                    </div>

                <?php endif; ?>

            </div>

        </div>
    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php include __DIR__ . '/../includes/scripts.php'; ?>