<?php

require_once __DIR__ . '/../includes/auth_user.php';
require_once __DIR__ . '/../config/koneksi.php';

$id_user = $_SESSION['id_user'];

$keyword = trim($_GET['keyword'] ?? '');
$status = trim($_GET['status'] ?? '');

$allowed_status = [
    'Menunggu Pembayaran',
    'Menunggu Verifikasi',
    'Terkonfirmasi',
    'Ditolak',
    'Dibatalkan'
];

if (!in_array($status, $allowed_status)) {
    $status = '';
}

$keyword_like = '%' . $keyword . '%';

$stmt = mysqli_prepare($conn, "
    SELECT 
        b.id_booking,
        b.kode_booking,
        b.tanggal_booking,
        b.jam_mulai,
        b.jam_selesai,
        b.durasi,
        b.total_biaya,
        b.status_booking,
        b.created_at,

        l.nama_lapangan,
        l.jenis_olahraga,

        p.status_pembayaran,
        p.bukti_pembayaran
    FROM data_booking b
    JOIN data_lapangan l ON b.id_lapangan = l.id_lapangan
    LEFT JOIN data_pembayaran p ON b.id_booking = p.id_booking
    WHERE b.id_user = ?
    AND (? = '' OR b.kode_booking LIKE ? OR l.nama_lapangan LIKE ?)
    AND (? = '' OR b.status_booking = ?)
    ORDER BY b.created_at DESC
");

mysqli_stmt_bind_param(
    $stmt,
    "isssss",
    $id_user,
    $keyword,
    $keyword_like,
    $keyword_like,
    $status,
    $status
);

mysqli_stmt_execute($stmt);
$result_booking = mysqli_stmt_get_result($stmt);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';

?>

<style>
    .history-page {
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

    .filter-card,
    .content-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 24px;
        padding: 26px;
    }

    .form-control,
    .form-select {
        border-radius: 14px;
        padding: 12px 14px;
    }

    .status-badge {
        padding: 7px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        display: inline-block;
    }

    .status-menunggu {
        background: #fef3c7;
        color: #92400e;
    }

    .status-verifikasi {
        background: #dbeafe;
        color: #1e40af;
    }

    .status-terkonfirmasi {
        background: #dcfce7;
        color: #166534;
    }

    .status-ditolak {
        background: #fee2e2;
        color: #991b1b;
    }

    .status-dibatalkan {
        background: #e5e7eb;
        color: #374151;
    }

    .empty-state {
        border: 1px dashed #cbd5e1;
        border-radius: 18px;
        padding: 34px;
        text-align: center;
        background: #f8fafc;
        color: #64748b;
    }

    .action-box {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        min-width: 170px;
    }

    .action-box .btn,
    .action-box form,
    .action-box form button {
        width: 100%;
    }

    .action-upload {
        grid-column: 1 / -1;
    }
</style>

<main class="flex-grow-1">

    <section class="history-page">
        <div class="container">

            <div class="page-hero">
                <div class="row align-items-center gy-3">
                    <div class="col-lg-8">
                        <span class="badge bg-light text-success px-3 py-2 mb-3">
                            Riwayat Booking
                        </span>

                        <h1 class="fw-bold mb-2">
                            Pantau semua booking lapangan kamu.
                        </h1>

                        <p class="mb-0">
                            Lihat status booking, pembayaran, dan detail reservasi yang pernah kamu lakukan.
                        </p>
                    </div>

                    <div class="col-lg-4 text-lg-end">
                        <a href="lapangan.php" class="btn btn-light fw-semibold px-4">
                            Booking Lagi
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

            <div class="filter-card mb-4">
                <form method="GET" action="riwayat_booking.php">

                    <div class="row g-3 align-items-end">

                        <div class="col-lg-5">
                            <label class="form-label fw-semibold">Cari Booking</label>
                            <input
                                type="text"
                                name="keyword"
                                class="form-control"
                                placeholder="Cari kode booking atau nama lapangan"
                                value="<?= htmlspecialchars($keyword); ?>">
                        </div>

                        <div class="col-lg-4">
                            <label class="form-label fw-semibold">Status Booking</label>
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="Menunggu Pembayaran" <?= $status == 'Menunggu Pembayaran' ? 'selected' : ''; ?>>
                                    Menunggu Pembayaran
                                </option>
                                <option value="Menunggu Verifikasi" <?= $status == 'Menunggu Verifikasi' ? 'selected' : ''; ?>>
                                    Menunggu Verifikasi
                                </option>
                                <option value="Terkonfirmasi" <?= $status == 'Terkonfirmasi' ? 'selected' : ''; ?>>
                                    Terkonfirmasi
                                </option>
                                <option value="Ditolak" <?= $status == 'Ditolak' ? 'selected' : ''; ?>>
                                    Ditolak
                                </option>
                                <option value="Dibatalkan" <?= $status == 'Dibatalkan' ? 'selected' : ''; ?>>
                                    Dibatalkan
                                </option>
                            </select>
                        </div>

                        <div class="col-lg-3">
                            <button type="submit" class="btn btn-success w-100">
                                Filter
                            </button>
                        </div>

                    </div>

                    <?php if ($keyword != '' || $status != '') : ?>
                        <div class="mt-3">
                            <a href="riwayat_booking.php" class="btn btn-sm btn-outline-secondary">
                                Reset Filter
                            </a>
                        </div>
                    <?php endif; ?>

                </form>
            </div>

            <div class="content-card">

                <?php if (mysqli_num_rows($result_booking) > 0) : ?>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Lapangan</th>
                                    <th>Tanggal</th>
                                    <th>Jam</th>
                                    <th>Total</th>
                                    <th>Status Booking</th>
                                    <th>Status Bayar</th>
                                    <th width="190">Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php while ($booking = mysqli_fetch_assoc($result_booking)) : ?>

                                    <?php
                                    $status_booking = $booking['status_booking'];
                                    $status_class = "status-dibatalkan";

                                    if ($status_booking == "Menunggu Pembayaran") {
                                        $status_class = "status-menunggu";
                                    } elseif ($status_booking == "Menunggu Verifikasi") {
                                        $status_class = "status-verifikasi";
                                    } elseif ($status_booking == "Terkonfirmasi") {
                                        $status_class = "status-terkonfirmasi";
                                    } elseif ($status_booking == "Ditolak") {
                                        $status_class = "status-ditolak";
                                    } elseif ($status_booking == "Dibatalkan") {
                                        $status_class = "status-dibatalkan";
                                    }

                                    $status_pembayaran = $booking['status_pembayaran'] ?? 'Belum Dibayar';

                                    $boleh_upload = (
                                        $status_pembayaran == 'Belum Dibayar' &&
                                        $status_booking == 'Menunggu Pembayaran'
                                    );

                                    $boleh_batal = (
                                        $status_booking == 'Menunggu Pembayaran' &&
                                        $status_pembayaran == 'Belum Dibayar'
                                    );
                                    ?>

                                    <tr>
                                        <td class="fw-semibold">
                                            <?= htmlspecialchars($booking['kode_booking']); ?>
                                        </td>

                                        <td>
                                            <div class="fw-bold">
                                                <?= htmlspecialchars($booking['nama_lapangan']); ?>
                                            </div>
                                            <small class="text-secondary">
                                                <?= htmlspecialchars($booking['jenis_olahraga']); ?>
                                            </small>
                                        </td>

                                        <td>
                                            <?= date('d M Y', strtotime($booking['tanggal_booking'])); ?>
                                        </td>

                                        <td>
                                            <?= date('H:i', strtotime($booking['jam_mulai'])); ?>
                                            -
                                            <?= date('H:i', strtotime($booking['jam_selesai'])); ?>
                                        </td>

                                        <td class="fw-semibold">
                                            Rp<?= number_format($booking['total_biaya'], 0, ',', '.'); ?>
                                        </td>

                                        <td>
                                            <span class="status-badge <?= $status_class; ?>">
                                                <?= htmlspecialchars($status_booking); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <span class="text-secondary small">
                                                <?= htmlspecialchars($status_pembayaran); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <div class="action-box">

                                                <a
                                                    href="detail_riwayat.php?kode=<?= urlencode($booking['kode_booking']); ?>"
                                                    class="btn btn-sm btn-outline-success">
                                                    Detail
                                                </a>

                                                <?php if ($boleh_batal) : ?>
                                                    <form
                                                        action="../process/batal_booking_process.php"
                                                        method="POST"
                                                        onsubmit="return confirm('Yakin ingin membatalkan booking ini?');">
                                                        <input
                                                            type="hidden"
                                                            name="kode_booking"
                                                            value="<?= htmlspecialchars($booking['kode_booking']); ?>">

                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            Batalkan
                                                        </button>
                                                    </form>
                                                <?php else : ?>
                                                    <span></span>
                                                <?php endif; ?>

                                                <?php if ($boleh_upload) : ?>
                                                    <a
                                                        href="upload_pembayaran.php?kode=<?= urlencode($booking['kode_booking']); ?>"
                                                        class="btn btn-sm btn-success action-upload">
                                                        Upload
                                                    </a>
                                                <?php endif; ?>

                                            </div>
                                        </td>
                                    </tr>

                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                <?php else : ?>

                    <div class="empty-state">
                        <i class="bi bi-calendar-x fs-1 d-block mb-3"></i>
                        <h5 class="fw-bold">Belum ada riwayat booking</h5>
                        <p class="mb-3">
                            Kamu belum melakukan booking lapangan.
                        </p>
                        <a href="lapangan.php" class="btn btn-success">
                            Booking Sekarang
                        </a>
                    </div>

                <?php endif; ?>

            </div>

        </div>
    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php include __DIR__ . '/../includes/scripts.php'; ?>