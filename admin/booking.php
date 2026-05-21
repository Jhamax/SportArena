<?php

require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/koneksi.php';

$keyword = trim($_GET['keyword'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

$allowed_status = [
    'Menunggu Pembayaran',
    'Menunggu Verifikasi',
    'Terkonfirmasi',
    'Ditolak',
    'Dibatalkan'
];

if (!in_array($status_filter, $allowed_status)) {
    $status_filter = '';
}

$keyword_like = '%' . $keyword . '%';

/* =========================
   PAGINATION
========================= */
$limit = 10;
$page = (int) ($_GET['page'] ?? 1);

if ($page < 1) {
    $page = 1;
}

function buildBookingPageUrl($page_number, $keyword, $status_filter)
{
    $params = [];

    if ($keyword != '') {
        $params['keyword'] = $keyword;
    }

    if ($status_filter != '') {
        $params['status'] = $status_filter;
    }

    $params['page'] = $page_number;

    return 'booking.php?' . http_build_query($params);
}

/* Hitung total data */
$total_booking = 0;

$stmt_total = mysqli_prepare($conn, "
    SELECT COUNT(*)
    FROM data_booking b
    JOIN data_pengguna u ON b.id_user = u.id_user
    JOIN data_lapangan l ON b.id_lapangan = l.id_lapangan
    LEFT JOIN data_pembayaran p ON b.id_booking = p.id_booking
    WHERE 
        (? = '' OR b.kode_booking LIKE ? OR u.nama LIKE ? OR l.nama_lapangan LIKE ?)
        AND (? = '' OR b.status_booking = ?)
");

mysqli_stmt_bind_param(
    $stmt_total,
    "ssssss",
    $keyword,
    $keyword_like,
    $keyword_like,
    $keyword_like,
    $status_filter,
    $status_filter
);

mysqli_stmt_execute($stmt_total);
mysqli_stmt_bind_result($stmt_total, $total_booking);
mysqli_stmt_fetch($stmt_total);
mysqli_stmt_close($stmt_total);

$total_halaman = ceil($total_booking / $limit);

if ($total_halaman < 1) {
    $total_halaman = 1;
}

if ($page > $total_halaman) {
    $page = $total_halaman;
}

$offset = ($page - 1) * $limit;

/* Ambil data booking */
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

        u.nama AS nama_pengguna,
        u.email,

        l.nama_lapangan,
        l.jenis_olahraga,

        p.status_pembayaran
    FROM data_booking b
    JOIN data_pengguna u ON b.id_user = u.id_user
    JOIN data_lapangan l ON b.id_lapangan = l.id_lapangan
    LEFT JOIN data_pembayaran p ON b.id_booking = p.id_booking
    WHERE 
        (? = '' OR b.kode_booking LIKE ? OR u.nama LIKE ? OR l.nama_lapangan LIKE ?)
        AND (? = '' OR b.status_booking = ?)
    ORDER BY b.created_at DESC
    LIMIT ? OFFSET ?
");

mysqli_stmt_bind_param(
    $stmt,
    "ssssssii",
    $keyword,
    $keyword_like,
    $keyword_like,
    $keyword_like,
    $status_filter,
    $status_filter,
    $limit,
    $offset
);

mysqli_stmt_execute($stmt);
$result_booking = mysqli_stmt_get_result($stmt);

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
        min-height: 54px;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #198754;
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.15);
    }

    .btn-filter-booking {
        height: 54px;
        border-radius: 14px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 18px;
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
        text-align: center;
    }

    .status-booking-admin {
        width: 175px;
        min-height: 42px;
        white-space: normal;
        padding: 7px 10px;
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
                            Data Booking
                        </span>

                        <h1 class="fw-bold mb-2">
                            Pantau semua data booking pengguna.
                        </h1>

                        <p class="mb-0">
                            Admin dapat melihat booking yang masuk, status pembayaran,
                            jadwal reservasi, dan detail transaksi pengguna.
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

            <div class="filter-card mb-4">

                <form method="GET" action="booking.php">

                    <div class="row g-3 align-items-end">

                        <div class="col-lg-6">
                            <label class="form-label fw-semibold">Cari Booking</label>
                            <input
                                type="text"
                                name="keyword"
                                class="form-control"
                                placeholder="Cari kode booking, nama pengguna, atau lapangan"
                                value="<?= htmlspecialchars($keyword); ?>">
                        </div>

                        <div class="col-lg-4">
                            <label class="form-label fw-semibold">Status Booking</label>
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>

                                <option value="Menunggu Pembayaran" <?= $status_filter == 'Menunggu Pembayaran' ? 'selected' : ''; ?>>
                                    Menunggu Pembayaran
                                </option>

                                <option value="Menunggu Verifikasi" <?= $status_filter == 'Menunggu Verifikasi' ? 'selected' : ''; ?>>
                                    Menunggu Verifikasi
                                </option>

                                <option value="Terkonfirmasi" <?= $status_filter == 'Terkonfirmasi' ? 'selected' : ''; ?>>
                                    Terkonfirmasi
                                </option>

                                <option value="Ditolak" <?= $status_filter == 'Ditolak' ? 'selected' : ''; ?>>
                                    Ditolak
                                </option>

                                <option value="Dibatalkan" <?= $status_filter == 'Dibatalkan' ? 'selected' : ''; ?>>
                                    Dibatalkan
                                </option>
                            </select>
                        </div>

                        <div class="col-lg-2">
                            <button type="submit" class="btn btn-success w-100 btn-filter-booking">
                                Filter
                            </button>
                        </div>

                    </div>

                    <?php if ($keyword != '' || $status_filter != '') : ?>
                        <div class="mt-3">
                            <a href="booking.php" class="btn btn-sm btn-outline-secondary">
                                Reset Filter
                            </a>
                        </div>
                    <?php endif; ?>

                </form>

            </div>

            <div class="content-card">

                <div class="mb-4">
                    <h4 class="fw-bold mb-1">
                        Daftar Booking
                    </h4>

                    <p class="text-secondary mb-0">
                        Seluruh data booking yang dibuat oleh pengguna.
                    </p>

                    <?php if ($total_booking > 0) : ?>
                        <div class="table-meta mt-1">
                            Menampilkan halaman <?= $page; ?> dari <?= $total_halaman; ?>.
                            Total <?= $total_booking; ?> booking.
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (mysqli_num_rows($result_booking) > 0) : ?>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Pengguna</th>
                                    <th>Lapangan</th>
                                    <th>Jadwal</th>
                                    <th>Total</th>
                                    <th>Status Booking</th>
                                    <th>Status Bayar</th>
                                    <th width="110">Aksi</th>
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
                                    ?>

                                    <tr>
                                        <td class="fw-bold">
                                            <?= htmlspecialchars($booking['kode_booking']); ?>
                                        </td>

                                        <td>
                                            <div class="fw-bold">
                                                <?= htmlspecialchars($booking['nama_pengguna']); ?>
                                            </div>
                                            <small class="text-secondary">
                                                <?= htmlspecialchars($booking['email']); ?>
                                            </small>
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
                                            <div class="fw-bold">
                                                <?= date('d M Y', strtotime($booking['tanggal_booking'])); ?>
                                            </div>
                                            <small class="text-secondary">
                                                <?= date('H:i', strtotime($booking['jam_mulai'])); ?>
                                                -
                                                <?= date('H:i', strtotime($booking['jam_selesai'])); ?>
                                                (<?= (int) $booking['durasi']; ?> jam)
                                            </small>
                                        </td>

                                        <td class="fw-bold text-success">
                                            Rp<?= number_format($booking['total_biaya'], 0, ',', '.'); ?>
                                        </td>

                                        <td>
                                            <span class="status-badge status-booking-admin <?= $status_class; ?>">
                                                <?= htmlspecialchars($status_booking); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($status_pembayaran); ?>
                                        </td>

                                        <td>
                                            <a
                                                href="detail_booking.php?kode=<?= urlencode($booking['kode_booking']); ?>"
                                                class="btn btn-sm btn-outline-success">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>

                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($total_halaman > 1) : ?>

                        <div class="pagination-wrap">

                            <?php if ($page > 1) : ?>
                                <a
                                    href="<?= buildBookingPageUrl($page - 1, $keyword, $status_filter); ?>"
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
                                    href="<?= buildBookingPageUrl($i, $keyword, $status_filter); ?>"
                                    class="pagination-link <?= $i == $page ? 'active' : ''; ?>">
                                    <?= $i; ?>
                                </a>

                            <?php endfor; ?>

                            <?php if ($page < $total_halaman) : ?>
                                <a
                                    href="<?= buildBookingPageUrl($page + 1, $keyword, $status_filter); ?>"
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
                        <i class="bi bi-calendar-x fs-1 d-block mb-3"></i>

                        <h5 class="fw-bold">
                            Data booking tidak ditemukan
                        </h5>

                        <p class="mb-0">
                            Belum ada booking yang sesuai dengan filter pencarian.
                        </p>
                    </div>

                <?php endif; ?>

            </div>

        </div>
    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php include __DIR__ . '/../includes/scripts.php'; ?>