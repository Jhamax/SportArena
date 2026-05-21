<?php

require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/koneksi.php';

/* =========================
   PAGINATION
========================= */
$limit = 10;
$page = (int) ($_GET['page'] ?? 1);

if ($page < 1) {
    $page = 1;
}

/* Hitung total pembayaran menunggu verifikasi */
$total_data = 0;

$stmt_total = mysqli_prepare($conn, "
    SELECT COUNT(*)
    FROM data_pembayaran p
    JOIN data_booking b ON p.id_booking = b.id_booking
    JOIN data_pengguna u ON b.id_user = u.id_user
    JOIN data_lapangan l ON b.id_lapangan = l.id_lapangan
    WHERE p.status_pembayaran = 'Menunggu Verifikasi'
");

mysqli_stmt_execute($stmt_total);
mysqli_stmt_bind_result($stmt_total, $total_data);
mysqli_stmt_fetch($stmt_total);
mysqli_stmt_close($stmt_total);

$total_halaman = ceil($total_data / $limit);

if ($total_halaman < 1) {
    $total_halaman = 1;
}

if ($page > $total_halaman) {
    $page = $total_halaman;
}

$offset = ($page - 1) * $limit;

/* Ambil data pembayaran */
$stmt_pembayaran = mysqli_prepare($conn, "
    SELECT 
        p.id_pembayaran,
        p.id_booking,
        p.bukti_pembayaran,
        p.tanggal_pembayaran,
        p.jumlah_pembayaran,
        p.metode_pembayaran,
        p.status_pembayaran,

        b.kode_booking,
        b.tanggal_booking,
        b.jam_mulai,
        b.jam_selesai,
        b.durasi,
        b.total_biaya,
        b.status_booking,

        u.nama AS nama_pengguna,
        u.email,
        u.no_hp,

        l.nama_lapangan,
        l.jenis_olahraga
    FROM data_pembayaran p
    JOIN data_booking b ON p.id_booking = b.id_booking
    JOIN data_pengguna u ON b.id_user = u.id_user
    JOIN data_lapangan l ON b.id_lapangan = l.id_lapangan
    WHERE p.status_pembayaran = 'Menunggu Verifikasi'
    ORDER BY p.tanggal_pembayaran DESC, p.id_pembayaran DESC
    LIMIT ? OFFSET ?
");

mysqli_stmt_bind_param($stmt_pembayaran, "ii", $limit, $offset);
mysqli_stmt_execute($stmt_pembayaran);
$result_pembayaran = mysqli_stmt_get_result($stmt_pembayaran);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';

?>

<style>
    .admin-page {
        background: #f8fafc;
        padding: 42px 0 70px;
    }

    .page-hero {
        background: linear-gradient(135deg, #0f172a, #166534);
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

    .payment-card {
        border: 1px solid #e5e7eb;
        border-radius: 22px;
        padding: 24px;
        margin-bottom: 18px;
        background: #ffffff;
        transition: 0.25s ease;
    }

    .payment-card:hover {
        border-color: #198754;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
    }

    .info-box {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 18px;
        height: 100%;
    }

    .proof-box {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 18px;
        height: 100%;
    }

    .proof-img {
        width: 100%;
        max-height: 260px;
        object-fit: contain;
        border-radius: 14px;
        background: white;
        border: 1px solid #e5e7eb;
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

    .status-verifikasi {
        background: #dbeafe;
        color: #1e40af;
    }

    .status-menunggu {
        background: #fef3c7;
        color: #92400e;
    }

    .form-control {
        border-radius: 14px;
        padding: 12px 14px;
    }

    .form-control:focus {
        border-color: #198754;
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.15);
    }

    .action-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .action-grid .btn,
    .action-grid form,
    .action-grid form button {
        width: 100%;
    }

    .reject-form {
        grid-column: 1 / -1;
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

    .table-meta {
        color: #64748b;
        font-size: 14px;
    }
</style>

<main class="flex-grow-1">

    <section class="admin-page">
        <div class="container">

            <div class="page-hero">
                <div class="row align-items-center gy-3">

                    <div class="col-lg-8">
                        <span class="badge bg-light text-success px-3 py-2 mb-3">
                            Verifikasi Pembayaran
                        </span>

                        <h1 class="fw-bold mb-2">
                            Periksa bukti pembayaran pengguna.
                        </h1>

                        <p class="mb-0">
                            Admin dapat melihat bukti transfer, menerima pembayaran, atau menolak pembayaran
                            jika bukti tidak valid.
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

                <div class="mb-4">
                    <h4 class="fw-bold mb-1">
                        Menunggu Verifikasi
                    </h4>

                    <p class="text-secondary mb-0">
                        Daftar pembayaran yang perlu diperiksa admin.
                    </p>

                    <?php if ($total_data > 0) : ?>
                        <div class="table-meta mt-1">
                            Menampilkan halaman <?= $page; ?> dari <?= $total_halaman; ?>.
                            Total <?= $total_data; ?> pembayaran menunggu verifikasi.
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (mysqli_num_rows($result_pembayaran) > 0) : ?>

                    <?php while ($pembayaran = mysqli_fetch_assoc($result_pembayaran)) : ?>

                        <?php
                        $bukti = $pembayaran['bukti_pembayaran'];
                        $ext = strtolower(pathinfo($bukti ?? '', PATHINFO_EXTENSION));
                        ?>

                        <div class="payment-card">

                            <div class="row g-4">

                                <div class="col-lg-8">

                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                                        <div>
                                            <h5 class="fw-bold mb-1">
                                                <?= htmlspecialchars($pembayaran['kode_booking']); ?>
                                            </h5>

                                            <p class="text-secondary mb-0">
                                                <?= htmlspecialchars($pembayaran['nama_pengguna']); ?>
                                                —
                                                <?= htmlspecialchars($pembayaran['email']); ?>
                                            </p>
                                        </div>

                                        <span class="status-badge status-verifikasi">
                                            Menunggu Verifikasi
                                        </span>
                                    </div>

                                    <div class="row g-3 mb-3">

                                        <div class="col-md-6">
                                            <div class="info-box">
                                                <small class="text-secondary">Lapangan</small>
                                                <h6 class="fw-bold mb-1">
                                                    <?= htmlspecialchars($pembayaran['nama_lapangan']); ?>
                                                </h6>
                                                <p class="text-secondary mb-0">
                                                    <?= htmlspecialchars($pembayaran['jenis_olahraga']); ?>
                                                </p>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="info-box">
                                                <small class="text-secondary">Jadwal</small>
                                                <h6 class="fw-bold mb-1">
                                                    <?= date('d M Y', strtotime($pembayaran['tanggal_booking'])); ?>
                                                </h6>
                                                <p class="text-secondary mb-0">
                                                    <?= date('H:i', strtotime($pembayaran['jam_mulai'])); ?>
                                                    -
                                                    <?= date('H:i', strtotime($pembayaran['jam_selesai'])); ?>
                                                    (<?= (int) $pembayaran['durasi']; ?> jam)
                                                </p>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="info-box">
                                                <small class="text-secondary">Jumlah Pembayaran</small>
                                                <h5 class="fw-bold text-success mb-0">
                                                    Rp<?= number_format($pembayaran['jumlah_pembayaran'], 0, ',', '.'); ?>
                                                </h5>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="info-box">
                                                <small class="text-secondary">Tanggal Upload</small>
                                                <h6 class="fw-bold mb-0">
                                                    <?= !empty($pembayaran['tanggal_pembayaran']) ? date('d M Y H:i', strtotime($pembayaran['tanggal_pembayaran'])) : '-'; ?>
                                                </h6>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="action-grid">

                                        <form 
                                            action="../process/verifikasi_process.php" 
                                            method="POST"
                                            onsubmit="return confirm('Terima pembayaran ini?');"
                                        >
                                            <input type="hidden" name="aksi" value="terima">
                                            <input type="hidden" name="id_pembayaran" value="<?= $pembayaran['id_pembayaran']; ?>">

                                            <button type="submit" class="btn btn-success">
                                                Terima Pembayaran
                                            </button>
                                        </form>

                                        <a 
                                            href="detail_booking.php?kode=<?= urlencode($pembayaran['kode_booking']); ?>" 
                                            class="btn btn-outline-success"
                                        >
                                            Detail Booking
                                        </a>

                                        <form 
                                            action="../process/verifikasi_process.php" 
                                            method="POST"
                                            class="reject-form"
                                            onsubmit="return confirm('Tolak pembayaran ini?');"
                                        >
                                            <input type="hidden" name="aksi" value="tolak">
                                            <input type="hidden" name="id_pembayaran" value="<?= $pembayaran['id_pembayaran']; ?>">

                                            <div class="mb-2">
                                                <textarea 
                                                    name="catatan_admin" 
                                                    class="form-control"
                                                    rows="2"
                                                    placeholder="Catatan admin jika pembayaran ditolak"
                                                    required
                                                ></textarea>
                                            </div>

                                            <button type="submit" class="btn btn-outline-danger">
                                                Tolak Pembayaran
                                            </button>
                                        </form>

                                    </div>

                                </div>

                                <div class="col-lg-4">

                                    <div class="proof-box">

                                        <h6 class="fw-bold mb-3">
                                            Bukti Pembayaran
                                        </h6>

                                        <?php if (!empty($bukti) && in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) : ?>

                                            <img 
                                                src="../<?= htmlspecialchars($bukti); ?>" 
                                                class="proof-img mb-3"
                                                alt="Bukti Pembayaran"
                                            >

                                            <a 
                                                href="../<?= htmlspecialchars($bukti); ?>" 
                                                target="_blank" 
                                                class="btn btn-outline-success w-100"
                                            >
                                                Buka Gambar
                                            </a>

                                        <?php elseif (!empty($bukti) && $ext == 'pdf') : ?>

                                            <div class="text-center py-4">
                                                <i class="bi bi-file-earmark-pdf fs-1 text-danger d-block mb-3"></i>

                                                <p class="text-secondary">
                                                    Bukti pembayaran berupa PDF.
                                                </p>

                                                <a 
                                                    href="../<?= htmlspecialchars($bukti); ?>" 
                                                    target="_blank" 
                                                    class="btn btn-outline-danger w-100"
                                                >
                                                    Buka PDF
                                                </a>
                                            </div>

                                        <?php else : ?>

                                            <div class="empty-state">
                                                <i class="bi bi-file-earmark-x fs-1 d-block mb-3"></i>
                                                <p class="mb-0">
                                                    Bukti pembayaran tidak tersedia.
                                                </p>
                                            </div>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            </div>

                        </div>

                    <?php endwhile; ?>

                    <?php if ($total_halaman > 1) : ?>

                        <div class="pagination-wrap">

                            <?php if ($page > 1) : ?>
                                <a 
                                    href="verifikasi_pembayaran.php?page=<?= $page - 1; ?>" 
                                    class="pagination-link"
                                >
                                    &laquo;
                                </a>
                            <?php else : ?>
                                <span class="pagination-link disabled">
                                    &laquo;
                                </span>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_halaman; $i++) : ?>

                                <a 
                                    href="verifikasi_pembayaran.php?page=<?= $i; ?>" 
                                    class="pagination-link <?= $i == $page ? 'active' : ''; ?>"
                                >
                                    <?= $i; ?>
                                </a>

                            <?php endfor; ?>

                            <?php if ($page < $total_halaman) : ?>
                                <a 
                                    href="verifikasi_pembayaran.php?page=<?= $page + 1; ?>" 
                                    class="pagination-link"
                                >
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
                        <i class="bi bi-receipt fs-1 d-block mb-3"></i>

                        <h5 class="fw-bold">
                            Tidak ada pembayaran menunggu verifikasi
                        </h5>

                        <p class="mb-0">
                            Data akan muncul setelah pengguna mengupload bukti pembayaran.
                        </p>
                    </div>

                <?php endif; ?>

            </div>

        </div>
    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php include __DIR__ . '/../includes/scripts.php'; ?>