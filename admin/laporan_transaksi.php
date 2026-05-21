<?php

require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/koneksi.php';

$tanggal_awal = $_GET['tanggal_awal'] ?? date('Y-m-01');
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_awal)) {
    $tanggal_awal = date('Y-m-01');
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_akhir)) {
    $tanggal_akhir = date('Y-m-d');
}

if ($tanggal_awal > $tanggal_akhir) {
    $temp = $tanggal_awal;
    $tanggal_awal = $tanggal_akhir;
    $tanggal_akhir = $temp;
}

/* ===============================
   PAGINATION DETAIL TRANSAKSI
================================ */
$limit_transaksi = 10;
$page_transaksi = (int) ($_GET['page_transaksi'] ?? 1);

if ($page_transaksi < 1) {
    $page_transaksi = 1;
}

function buildLaporanPageUrl($page_number, $tanggal_awal, $tanggal_akhir)
{
    return 'laporan_transaksi.php?' . http_build_query([
        'tanggal_awal' => $tanggal_awal,
        'tanggal_akhir' => $tanggal_akhir,
        'page_transaksi' => $page_number
    ]);
}

/* ===============================
   RINGKASAN TRANSAKSI
   Sumber: pembayaran berhasil
   Filter: tanggal booking
================================ */
$stmt_ringkasan = mysqli_prepare($conn, "
    SELECT 
        COUNT(pay.id_pembayaran) AS jumlah_transaksi,
        COALESCE(SUM(pay.jumlah_pembayaran), 0) AS total_pendapatan,
        COUNT(DISTINCT b.id_booking) AS jumlah_booking,
        COUNT(DISTINCT b.id_user) AS jumlah_pengguna
    FROM data_pembayaran pay
    JOIN data_booking b ON pay.id_booking = b.id_booking
    WHERE pay.status_pembayaran = 'Berhasil'
    AND b.tanggal_booking BETWEEN ? AND ?
");

mysqli_stmt_bind_param($stmt_ringkasan, "ss", $tanggal_awal, $tanggal_akhir);
mysqli_stmt_execute($stmt_ringkasan);
$result_ringkasan = mysqli_stmt_get_result($stmt_ringkasan);
$ringkasan = mysqli_fetch_assoc($result_ringkasan);

$jumlah_transaksi = $ringkasan['jumlah_transaksi'] ?? 0;
$total_pendapatan = $ringkasan['total_pendapatan'] ?? 0;
$jumlah_booking = $ringkasan['jumlah_booking'] ?? 0;
$jumlah_pengguna = $ringkasan['jumlah_pengguna'] ?? 0;

mysqli_stmt_close($stmt_ringkasan);

/* ===============================
   HITUNG TOTAL DATA TRANSAKSI
================================ */
$total_data_transaksi = 0;

$stmt_total_transaksi = mysqli_prepare($conn, "
    SELECT COUNT(pay.id_pembayaran)
    FROM data_pembayaran pay
    JOIN data_booking b ON pay.id_booking = b.id_booking
    JOIN data_pengguna u ON b.id_user = u.id_user
    JOIN data_lapangan l ON b.id_lapangan = l.id_lapangan
    WHERE pay.status_pembayaran = 'Berhasil'
    AND b.tanggal_booking BETWEEN ? AND ?
");

mysqli_stmt_bind_param($stmt_total_transaksi, "ss", $tanggal_awal, $tanggal_akhir);
mysqli_stmt_execute($stmt_total_transaksi);
mysqli_stmt_bind_result($stmt_total_transaksi, $total_data_transaksi);
mysqli_stmt_fetch($stmt_total_transaksi);
mysqli_stmt_close($stmt_total_transaksi);

$total_halaman_transaksi = ceil($total_data_transaksi / $limit_transaksi);

if ($total_halaman_transaksi < 1) {
    $total_halaman_transaksi = 1;
}

if ($page_transaksi > $total_halaman_transaksi) {
    $page_transaksi = $total_halaman_transaksi;
}

$offset_transaksi = ($page_transaksi - 1) * $limit_transaksi;

/* ===============================
   DATA TRANSAKSI
   Sumber: pembayaran berhasil
   Filter: tanggal booking
================================ */
$stmt_transaksi = mysqli_prepare($conn, "
    SELECT 
        pay.id_pembayaran,
        pay.tanggal_pembayaran,
        pay.jumlah_pembayaran,
        pay.metode_pembayaran,
        pay.status_pembayaran,

        b.id_booking,
        b.kode_booking,
        b.tanggal_booking,
        b.jam_mulai,
        b.jam_selesai,
        b.durasi,
        b.total_biaya,
        b.status_booking,

        u.nama AS nama_pengguna,
        u.email,

        l.nama_lapangan,
        l.jenis_olahraga
    FROM data_pembayaran pay
    JOIN data_booking b ON pay.id_booking = b.id_booking
    JOIN data_pengguna u ON b.id_user = u.id_user
    JOIN data_lapangan l ON b.id_lapangan = l.id_lapangan
    WHERE pay.status_pembayaran = 'Berhasil'
    AND b.tanggal_booking BETWEEN ? AND ?
    ORDER BY b.tanggal_booking DESC, pay.tanggal_pembayaran DESC, pay.id_pembayaran DESC
    LIMIT ? OFFSET ?
");

mysqli_stmt_bind_param(
    $stmt_transaksi,
    "ssii",
    $tanggal_awal,
    $tanggal_akhir,
    $limit_transaksi,
    $offset_transaksi
);

mysqli_stmt_execute($stmt_transaksi);
$result_transaksi = mysqli_stmt_get_result($stmt_transaksi);

/* ===============================
   RINGKASAN STATISTIK LAPANGAN
   Mengikuti Detail Transaksi:
   sumber pembayaran berhasil
================================ */
$stmt_stat_ringkasan = mysqli_prepare($conn, "
    SELECT 
        COUNT(DISTINCT b.id_lapangan) AS total_lapangan_dipakai,
        COUNT(DISTINCT b.id_booking) AS total_booking_lapangan,
        COALESCE(SUM(b.durasi), 0) AS total_jam_pemakaian
    FROM data_pembayaran pay
    JOIN data_booking b ON pay.id_booking = b.id_booking
    WHERE pay.status_pembayaran = 'Berhasil'
    AND b.tanggal_booking BETWEEN ? AND ?
");

mysqli_stmt_bind_param($stmt_stat_ringkasan, "ss", $tanggal_awal, $tanggal_akhir);
mysqli_stmt_execute($stmt_stat_ringkasan);
$result_stat_ringkasan = mysqli_stmt_get_result($stmt_stat_ringkasan);
$stat_ringkasan = mysqli_fetch_assoc($result_stat_ringkasan);

$total_lapangan_dipakai = $stat_ringkasan['total_lapangan_dipakai'] ?? 0;
$total_booking_lapangan = $stat_ringkasan['total_booking_lapangan'] ?? 0;
$total_jam_pemakaian = $stat_ringkasan['total_jam_pemakaian'] ?? 0;

mysqli_stmt_close($stmt_stat_ringkasan);

/* ===============================
   STATISTIK PER LAPANGAN
   Mengikuti Detail Transaksi:
   hanya pembayaran berhasil
================================ */
$stmt_lapangan = mysqli_prepare($conn, "
    SELECT 
        l.id_lapangan,
        l.nama_lapangan,
        l.jenis_olahraga,
        l.harga_per_jam,
        l.status_ketersediaan,

        COUNT(DISTINCT CASE 
            WHEN pay.id_pembayaran IS NOT NULL 
            THEN b.id_booking 
        END) AS total_booking,

        COALESCE(SUM(CASE 
            WHEN pay.id_pembayaran IS NOT NULL 
            THEN b.durasi 
            ELSE 0 
        END), 0) AS total_jam,

        COALESCE(SUM(CASE 
            WHEN pay.id_pembayaran IS NOT NULL 
            THEN pay.jumlah_pembayaran 
            ELSE 0 
        END), 0) AS total_pendapatan

    FROM data_lapangan l
    LEFT JOIN data_booking b 
        ON l.id_lapangan = b.id_lapangan
        AND b.tanggal_booking BETWEEN ? AND ?
    LEFT JOIN data_pembayaran pay 
        ON b.id_booking = pay.id_booking
        AND pay.status_pembayaran = 'Berhasil'
    GROUP BY 
        l.id_lapangan,
        l.nama_lapangan,
        l.jenis_olahraga,
        l.harga_per_jam,
        l.status_ketersediaan
    ORDER BY total_booking DESC, total_jam DESC
");

mysqli_stmt_bind_param($stmt_lapangan, "ss", $tanggal_awal, $tanggal_akhir);
mysqli_stmt_execute($stmt_lapangan);
$result_lapangan = mysqli_stmt_get_result($stmt_lapangan);

$data_lapangan = [];
$max_booking = 0;

while ($row = mysqli_fetch_assoc($result_lapangan)) {
    $data_lapangan[] = $row;

    if ($row['total_booking'] > $max_booking) {
        $max_booking = $row['total_booking'];
    }
}

mysqli_stmt_close($stmt_lapangan);

/* ===============================
   STATISTIK PER JENIS OLAHRAGA
   Mengikuti Detail Transaksi:
   hanya pembayaran berhasil
================================ */
$stmt_jenis = mysqli_prepare($conn, "
    SELECT 
        l.jenis_olahraga,
        COUNT(DISTINCT b.id_booking) AS total_booking,
        COALESCE(SUM(b.durasi), 0) AS total_jam,
        COALESCE(SUM(pay.jumlah_pembayaran), 0) AS total_pendapatan
    FROM data_pembayaran pay
    JOIN data_booking b ON pay.id_booking = b.id_booking
    JOIN data_lapangan l ON b.id_lapangan = l.id_lapangan
    WHERE pay.status_pembayaran = 'Berhasil'
    AND b.tanggal_booking BETWEEN ? AND ?
    GROUP BY l.jenis_olahraga
    ORDER BY total_booking DESC
");

mysqli_stmt_bind_param($stmt_jenis, "ss", $tanggal_awal, $tanggal_akhir);
mysqli_stmt_execute($stmt_jenis);
$result_jenis = mysqli_stmt_get_result($stmt_jenis);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';

?>

<style>
    .report-page {
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

    .summary-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 24px;
        padding: 24px;
        height: 100%;
        transition: 0.25s ease;
    }

    .summary-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
    }

    .summary-icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        background: #ecfdf5;
        color: #198754;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        margin-bottom: 18px;
    }

    .form-control {
        border-radius: 14px;
        padding: 12px 14px;
        min-height: 54px;
    }

    .btn-filter-laporan {
        height: 54px;
        border-radius: 14px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 18px;
    }

    .status-badge {
        display: inline-block;
        padding: 7px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
    }

    .status-berhasil {
        background: #dcfce7;
        color: #166534;
    }

    .status-tersedia {
        background: #dcfce7;
        color: #166534;
    }

    .status-tidak {
        background: #fee2e2;
        color: #991b1b;
    }

    .rank-badge {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: #ecfdf5;
        color: #166534;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
    }

    .progress {
        height: 12px;
        border-radius: 999px;
        background: #e5e7eb;
    }

    .progress-bar {
        background: #198754;
        border-radius: 999px;
    }

    .empty-state {
        border: 1px dashed #cbd5e1;
        border-radius: 18px;
        padding: 34px;
        text-align: center;
        background: #f8fafc;
        color: #64748b;
    }

    .section-divider {
        height: 1px;
        background: #e5e7eb;
        margin: 34px 0;
    }

    .table-meta {
        color: #64748b;
        font-size: 14px;
        margin-top: 4px;
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

    @media print {
        .navbar,
        .site-footer,
        .page-hero .btn,
        .filter-card,
        .btn-print,
        .pagination-wrap {
            display: none !important;
        }

        .report-page {
            background: white;
            padding: 0;
        }

        .content-card,
        .summary-card {
            box-shadow: none;
            border: 1px solid #ddd;
        }
    }
</style>

<main class="flex-grow-1">

    <section class="report-page">
        <div class="container">

            <div class="page-hero">
                <div class="row align-items-center gy-3">

                    <div class="col-lg-8">
                        <span class="badge bg-light text-success px-3 py-2 mb-3">
                            Laporan Admin
                        </span>

                        <h1 class="fw-bold mb-2">
                            Laporan transaksi dan statistik penggunaan lapangan.
                        </h1>

                        <p class="mb-0">
                            Admin dapat melihat transaksi berhasil, total pendapatan,
                            serta statistik pemakaian lapangan berdasarkan periode tertentu.
                        </p>
                    </div>

                    <div class="col-lg-4 text-lg-end">
                        <a href="dashboard.php" class="btn btn-light fw-semibold px-4">
                            Kembali ke Dashboard
                        </a>
                    </div>

                </div>
            </div>

            <div class="filter-card mb-4">

                <form method="GET" action="laporan_transaksi.php">

                    <div class="row g-3 align-items-end">

                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Tanggal Awal</label>
                            <input 
                                type="date" 
                                name="tanggal_awal" 
                                class="form-control"
                                value="<?= htmlspecialchars($tanggal_awal); ?>"
                                required
                            >
                        </div>

                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Tanggal Akhir</label>
                            <input 
                                type="date" 
                                name="tanggal_akhir" 
                                class="form-control"
                                value="<?= htmlspecialchars($tanggal_akhir); ?>"
                                required
                            >
                        </div>

                        <div class="col-md-2">
                            <button type="submit" class="btn btn-success w-100 btn-filter-laporan">
                                Tampilkan
                            </button>
                        </div>

                    </div>

                    <div class="mt-3 d-flex gap-2 flex-wrap">

                        <a href="laporan_transaksi.php?tanggal_awal=<?= date('Y-m-d'); ?>&tanggal_akhir=<?= date('Y-m-d'); ?>" class="btn btn-sm btn-outline-secondary">
                            Hari Ini
                        </a>

                        <a href="laporan_transaksi.php?tanggal_awal=<?= date('Y-m-01'); ?>&tanggal_akhir=<?= date('Y-m-d'); ?>" class="btn btn-sm btn-outline-secondary">
                            Bulan Ini
                        </a>

                        <a href="laporan_transaksi.php?tanggal_awal=<?= date('Y-01-01'); ?>&tanggal_akhir=<?= date('Y-m-d'); ?>" class="btn btn-sm btn-outline-secondary">
                            Tahun Ini
                        </a>

                        <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-success btn-print">
                            Cetak Laporan
                        </button>

                    </div>

                </form>

            </div>

            <!-- RINGKASAN TRANSAKSI -->
            <div class="row g-4 mb-4">

                <div class="col-md-6 col-lg-3">
                    <div class="summary-card">
                        <div class="summary-icon">
                            <i class="bi bi-cash-stack"></i>
                        </div>

                        <h4 class="fw-bold mb-1">
                            Rp<?= number_format($total_pendapatan, 0, ',', '.'); ?>
                        </h4>

                        <p class="text-secondary mb-0">
                            Total Pendapatan
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="summary-card">
                        <div class="summary-icon">
                            <i class="bi bi-receipt"></i>
                        </div>

                        <h4 class="fw-bold mb-1">
                            <?= $jumlah_transaksi; ?>
                        </h4>

                        <p class="text-secondary mb-0">
                            Transaksi Berhasil
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="summary-card">
                        <div class="summary-icon">
                            <i class="bi bi-calendar-check"></i>
                        </div>

                        <h4 class="fw-bold mb-1">
                            <?= $jumlah_booking; ?>
                        </h4>

                        <p class="text-secondary mb-0">
                            Booking Terbayar
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="summary-card">
                        <div class="summary-icon">
                            <i class="bi bi-people"></i>
                        </div>

                        <h4 class="fw-bold mb-1">
                            <?= $jumlah_pengguna; ?>
                        </h4>

                        <p class="text-secondary mb-0">
                            Pengguna Bertransaksi
                        </p>
                    </div>
                </div>

            </div>

            <!-- DETAIL TRANSAKSI -->
            <div class="content-card mb-4">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">

                    <div>
                        <h4 class="fw-bold mb-1">
                            Detail Transaksi
                        </h4>

                        <p class="text-secondary mb-0">
                            Periode:
                            <?= date('d M Y', strtotime($tanggal_awal)); ?>
                            -
                            <?= date('d M Y', strtotime($tanggal_akhir)); ?>
                        </p>

                        <?php if ($total_data_transaksi > 0) : ?>
                            <div class="table-meta">
                                Menampilkan halaman <?= $page_transaksi; ?> dari <?= $total_halaman_transaksi; ?>.
                                Total <?= $total_data_transaksi; ?> transaksi.
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

                <?php if (mysqli_num_rows($result_transaksi) > 0) : ?>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Tanggal Bayar</th>
                                    <th>Kode Booking</th>
                                    <th>Pengguna</th>
                                    <th>Lapangan</th>
                                    <th>Jadwal</th>
                                    <th>Metode</th>
                                    <th>Jumlah</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php while ($transaksi = mysqli_fetch_assoc($result_transaksi)) : ?>

                                    <tr>
                                        <td>
                                            <?= !empty($transaksi['tanggal_pembayaran']) ? date('d M Y', strtotime($transaksi['tanggal_pembayaran'])) : '-'; ?>
                                        </td>

                                        <td class="fw-bold">
                                            <?= htmlspecialchars($transaksi['kode_booking']); ?>
                                        </td>

                                        <td>
                                            <div class="fw-semibold">
                                                <?= htmlspecialchars($transaksi['nama_pengguna']); ?>
                                            </div>
                                            <small class="text-secondary">
                                                <?= htmlspecialchars($transaksi['email']); ?>
                                            </small>
                                        </td>

                                        <td>
                                            <div class="fw-semibold">
                                                <?= htmlspecialchars($transaksi['nama_lapangan']); ?>
                                            </div>
                                            <small class="text-secondary">
                                                <?= htmlspecialchars($transaksi['jenis_olahraga']); ?>
                                            </small>
                                        </td>

                                        <td>
                                            <div class="fw-semibold">
                                                <?= date('d M Y', strtotime($transaksi['tanggal_booking'])); ?>
                                            </div>
                                            <small class="text-secondary">
                                                <?= date('H:i', strtotime($transaksi['jam_mulai'])); ?>
                                                -
                                                <?= date('H:i', strtotime($transaksi['jam_selesai'])); ?>
                                                (<?= $transaksi['durasi']; ?> jam)
                                            </small>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($transaksi['metode_pembayaran']); ?>
                                        </td>

                                        <td class="fw-bold text-success">
                                            Rp<?= number_format($transaksi['jumlah_pembayaran'], 0, ',', '.'); ?>
                                        </td>

                                        <td>
                                            <span class="status-badge status-berhasil">
                                                <?= htmlspecialchars($transaksi['status_pembayaran']); ?>
                                            </span>
                                        </td>
                                    </tr>

                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($total_halaman_transaksi > 1) : ?>

                        <div class="pagination-wrap">

                            <?php if ($page_transaksi > 1) : ?>
                                <a 
                                    href="<?= buildLaporanPageUrl($page_transaksi - 1, $tanggal_awal, $tanggal_akhir); ?>" 
                                    class="pagination-link"
                                >
                                    &laquo;
                                </a>
                            <?php else : ?>
                                <span class="pagination-link disabled">
                                    &laquo;
                                </span>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_halaman_transaksi; $i++) : ?>

                                <a 
                                    href="<?= buildLaporanPageUrl($i, $tanggal_awal, $tanggal_akhir); ?>" 
                                    class="pagination-link <?= $i == $page_transaksi ? 'active' : ''; ?>"
                                >
                                    <?= $i; ?>
                                </a>

                            <?php endfor; ?>

                            <?php if ($page_transaksi < $total_halaman_transaksi) : ?>
                                <a 
                                    href="<?= buildLaporanPageUrl($page_transaksi + 1, $tanggal_awal, $tanggal_akhir); ?>" 
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
                            Belum ada transaksi berhasil
                        </h5>

                        <p class="mb-0">
                            Tidak ada pembayaran berhasil pada periode yang dipilih.
                        </p>
                    </div>

                <?php endif; ?>

            </div>

            <!-- STATISTIK PENGGUNAAN LAPANGAN -->
            <div class="content-card">

                <div class="mb-4">
                    <span class="badge bg-success-subtle text-success px-3 py-2 mb-3">
                        Statistik Penggunaan Lapangan
                    </span>

                    <h4 class="fw-bold mb-1">
                        Statistik Penggunaan Lapangan
                    </h4>

                    <p class="text-secondary mb-0">
                        Statistik dihitung berdasarkan transaksi pembayaran yang sudah
                        <strong>Berhasil</strong> pada periode yang dipilih.
                    </p>
                </div>

                <div class="row g-4 mb-4">

                    <div class="col-md-4">
                        <div class="summary-card">
                            <div class="summary-icon">
                                <i class="bi bi-dribbble"></i>
                            </div>

                            <h4 class="fw-bold mb-1">
                                <?= $total_lapangan_dipakai; ?>
                            </h4>

                            <p class="text-secondary mb-0">
                                Lapangan Dipakai
                            </p>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="summary-card">
                            <div class="summary-icon">
                                <i class="bi bi-calendar-check"></i>
                            </div>

                            <h4 class="fw-bold mb-1">
                                <?= $total_booking_lapangan; ?>
                            </h4>

                            <p class="text-secondary mb-0">
                                Booking Berhasil Dibayar
                            </p>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="summary-card">
                            <div class="summary-icon">
                                <i class="bi bi-clock-history"></i>
                            </div>

                            <h4 class="fw-bold mb-1">
                                <?= $total_jam_pemakaian; ?> Jam
                            </h4>

                            <p class="text-secondary mb-0">
                                Total Jam Pemakaian
                            </p>
                        </div>
                    </div>

                </div>

                <div class="row g-4">

                    <div class="col-lg-8">

                        <h5 class="fw-bold mb-3">
                            Statistik Per Lapangan
                        </h5>

                        <?php if (count($data_lapangan) > 0) : ?>

                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Rank</th>
                                            <th>Lapangan</th>
                                            <th>Booking</th>
                                            <th>Total Jam</th>
                                            <th>Pendapatan</th>
                                            <th>Status</th>
                                            <th>Grafik</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php $rank = 1; ?>
                                        <?php foreach ($data_lapangan as $lapangan) : ?>

                                            <?php
                                            $persentase = 0;

                                            if ($max_booking > 0) {
                                                $persentase = ($lapangan['total_booking'] / $max_booking) * 100;
                                            }
                                            ?>

                                            <tr>
                                                <td>
                                                    <div class="rank-badge">
                                                        <?= $rank++; ?>
                                                    </div>
                                                </td>

                                                <td>
                                                    <div class="fw-bold">
                                                        <?= htmlspecialchars($lapangan['nama_lapangan']); ?>
                                                    </div>

                                                    <small class="text-secondary">
                                                        <?= htmlspecialchars($lapangan['jenis_olahraga']); ?>
                                                    </small>
                                                </td>

                                                <td class="fw-bold">
                                                    <?= $lapangan['total_booking']; ?>
                                                </td>

                                                <td>
                                                    <?= $lapangan['total_jam']; ?> Jam
                                                </td>

                                                <td class="fw-bold text-success">
                                                    Rp<?= number_format($lapangan['total_pendapatan'], 0, ',', '.'); ?>
                                                </td>

                                                <td>
                                                    <?php if ($lapangan['status_ketersediaan'] == 'tersedia') : ?>
                                                        <span class="status-badge status-tersedia">
                                                            Tersedia
                                                        </span>
                                                    <?php else : ?>
                                                        <span class="status-badge status-tidak">
                                                            Tidak Tersedia
                                                        </span>
                                                    <?php endif; ?>
                                                </td>

                                                <td style="min-width: 140px;">
                                                    <div class="progress">
                                                        <div 
                                                            class="progress-bar" 
                                                            style="width: <?= $persentase; ?>%;"
                                                        ></div>
                                                    </div>
                                                </td>
                                            </tr>

                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                        <?php else : ?>

                            <div class="empty-state">
                                <i class="bi bi-bar-chart fs-1 d-block mb-3"></i>

                                <h5 class="fw-bold">
                                    Belum ada statistik lapangan
                                </h5>

                                <p class="mb-0">
                                    Statistik akan muncul setelah ada transaksi pembayaran berhasil.
                                </p>
                            </div>

                        <?php endif; ?>

                    </div>

                    <div class="col-lg-4">

                        <h5 class="fw-bold mb-3">
                            Statistik Jenis Olahraga
                        </h5>

                        <?php if (mysqli_num_rows($result_jenis) > 0) : ?>

                            <div class="d-grid gap-3">

                                <?php while ($jenis_data = mysqli_fetch_assoc($result_jenis)) : ?>

                                    <div class="border rounded-4 p-3">

                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="fw-bold mb-0">
                                                <?= htmlspecialchars($jenis_data['jenis_olahraga']); ?>
                                            </h6>

                                            <span class="badge bg-success">
                                                <?= $jenis_data['total_booking']; ?> Booking
                                            </span>
                                        </div>

                                        <p class="text-secondary small mb-2">
                                            Total jam: <?= $jenis_data['total_jam']; ?> jam
                                        </p>

                                        <p class="fw-bold text-success mb-0">
                                            Rp<?= number_format($jenis_data['total_pendapatan'], 0, ',', '.'); ?>
                                        </p>

                                    </div>

                                <?php endwhile; ?>

                            </div>

                        <?php else : ?>

                            <div class="empty-state">
                                <i class="bi bi-pie-chart fs-1 d-block mb-3"></i>

                                <h6 class="fw-bold">
                                    Belum ada statistik jenis olahraga
                                </h6>

                                <p class="mb-0 small">
                                    Data akan muncul setelah ada transaksi pembayaran berhasil.
                                </p>
                            </div>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>
    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php include __DIR__ . '/../includes/scripts.php'; ?>