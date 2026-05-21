<?php

require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/koneksi.php';

$nama_admin = $_SESSION['nama'] ?? 'Admin';

/* Helper ambil 1 nilai */
function getValue($conn, $query)
{
    $result = mysqli_query($conn, $query);

    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_row($result);
    return $row[0] ?? 0;
}

/* Ringkasan utama */
$total_pengguna = getValue($conn, "SELECT COUNT(*) FROM data_pengguna WHERE role = 'user'");
$total_admin = getValue($conn, "SELECT COUNT(*) FROM data_pengguna WHERE role = 'admin'");
$total_lapangan = getValue($conn, "SELECT COUNT(*) FROM data_lapangan");
$total_jadwal = getValue($conn, "SELECT COUNT(*) FROM data_jadwal_lapangan");
$total_booking = getValue($conn, "SELECT COUNT(*) FROM data_booking");

$total_pendapatan = getValue($conn, "
    SELECT COALESCE(SUM(jumlah_pembayaran), 0)
    FROM data_pembayaran
    WHERE status_pembayaran = 'Berhasil'
");

/* Status booking */
$booking_menunggu_pembayaran = getValue($conn, "
    SELECT COUNT(*) FROM data_booking
    WHERE status_booking = 'Menunggu Pembayaran'
");

$booking_menunggu_verifikasi = getValue($conn, "
    SELECT COUNT(*) FROM data_booking
    WHERE status_booking = 'Menunggu Verifikasi'
");

$booking_terkonfirmasi = getValue($conn, "
    SELECT COUNT(*) FROM data_booking
    WHERE status_booking = 'Terkonfirmasi'
");

$booking_ditolak = getValue($conn, "
    SELECT COUNT(*) FROM data_booking
    WHERE status_booking = 'Ditolak'
");

$booking_dibatalkan = getValue($conn, "
    SELECT COUNT(*) FROM data_booking
    WHERE status_booking = 'Dibatalkan'
");

/* Pembayaran */
$pembayaran_belum_aktif = getValue($conn, "
    SELECT COUNT(*) 
    FROM data_pembayaran p
    JOIN data_booking b ON p.id_booking = b.id_booking
    WHERE p.status_pembayaran = 'Belum Dibayar'
    AND b.status_booking = 'Menunggu Pembayaran'
");

$pembayaran_belum_dibatalkan = getValue($conn, "
    SELECT COUNT(*) 
    FROM data_pembayaran p
    JOIN data_booking b ON p.id_booking = b.id_booking
    WHERE p.status_pembayaran = 'Belum Dibayar'
    AND b.status_booking = 'Dibatalkan'
");

$pembayaran_menunggu = getValue($conn, "
    SELECT COUNT(*) FROM data_pembayaran
    WHERE status_pembayaran = 'Menunggu Verifikasi'
");

$pembayaran_berhasil = getValue($conn, "
    SELECT COUNT(*) FROM data_pembayaran
    WHERE status_pembayaran = 'Berhasil'
");

$pembayaran_ditolak = getValue($conn, "
    SELECT COUNT(*) FROM data_pembayaran
    WHERE status_pembayaran = 'Ditolak'
");

/* Booking terbaru */
$query_booking_terbaru = mysqli_query($conn, "
    SELECT 
        b.kode_booking,
        b.tanggal_booking,
        b.jam_mulai,
        b.jam_selesai,
        b.total_biaya,
        b.status_booking,
        b.created_at,

        u.nama AS nama_pengguna,
        l.nama_lapangan,
        l.jenis_olahraga,
        p.status_pembayaran
    FROM data_booking b
    JOIN data_pengguna u ON b.id_user = u.id_user
    JOIN data_lapangan l ON b.id_lapangan = l.id_lapangan
    LEFT JOIN data_pembayaran p ON b.id_booking = p.id_booking
    ORDER BY b.created_at DESC
    LIMIT 5
");

/* Pembayaran menunggu verifikasi */
$query_pembayaran_menunggu = mysqli_query($conn, "
    SELECT 
        p.id_pembayaran,
        p.tanggal_pembayaran,
        p.jumlah_pembayaran,
        p.status_pembayaran,

        b.kode_booking,
        b.status_booking,

        u.nama AS nama_pengguna,
        l.nama_lapangan
    FROM data_pembayaran p
    JOIN data_booking b ON p.id_booking = b.id_booking
    JOIN data_pengguna u ON b.id_user = u.id_user
    JOIN data_lapangan l ON b.id_lapangan = l.id_lapangan
    WHERE p.status_pembayaran = 'Menunggu Verifikasi'
    ORDER BY p.tanggal_pembayaran DESC, p.id_pembayaran DESC
    LIMIT 5
");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';

?>

<style>
    .admin-dashboard {
        background: #f8fafc;
        padding: 42px 0 70px;
    }

    .dashboard-hero {
        background: linear-gradient(135deg, #0f172a, #166534);
        color: white;
        border-radius: 28px;
        padding: 34px;
        margin-bottom: 28px;
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.18);
    }

    .dashboard-hero p {
        color: #d1fae5;
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
        width: 52px;
        height: 52px;
        border-radius: 18px;
        background: #ecfdf5;
        color: #198754;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-bottom: 18px;
    }

    .content-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 24px;
        padding: 26px;
        height: 100%;
    }

    .quick-menu {
        display: flex;
        align-items: center;
        gap: 14px;
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 18px;
        text-decoration: none;
        color: #111827;
        transition: 0.2s ease;
    }

    .quick-menu:hover {
        background: #ecfdf5;
        border-color: #198754;
        color: #111827;
        transform: translateX(4px);
    }

    .quick-menu i {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        background: #198754;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
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

    .small-stat {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 18px;
        height: 100%;
    }

    .empty-state {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 18px;
        padding: 28px;
        text-align: center;
        color: #64748b;
    }
</style>

<main class="flex-grow-1">

    <section class="admin-dashboard">
        <div class="container">

            <div class="dashboard-hero">
                <div class="row align-items-center gy-3">

                    <div class="col-lg-8">
                        <span class="badge bg-light text-success px-3 py-2 mb-3">
                            Dashboard Admin
                        </span>

                        <h1 class="fw-bold mb-2">
                            Selamat datang, <?= htmlspecialchars($nama_admin); ?>.
                        </h1>

                        <p class="mb-0">
                            Pantau data pengguna, lapangan, jadwal, booking, pembayaran, laporan,
                            dan status transaksi pada sistem ArenaSport.
                        </p>
                    </div>

                    <div class="col-lg-4 text-lg-end">
                        <a href="verifikasi_pembayaran.php" class="btn btn-light fw-semibold px-4">
                            Verifikasi Pembayaran
                        </a>
                    </div>

                </div>
            </div>

            <!-- RINGKASAN UTAMA -->
            <div class="row g-4 mb-4">

                <div class="col-md-6 col-lg-3">
                    <div class="summary-card">
                        <div class="summary-icon">
                            <i class="bi bi-people"></i>
                        </div>

                        <h3 class="fw-bold mb-1">
                            <?= $total_pengguna; ?>
                        </h3>

                        <p class="text-secondary mb-0">
                            Total Pengguna
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="summary-card">
                        <div class="summary-icon">
                            <i class="bi bi-dribbble"></i>
                        </div>

                        <h3 class="fw-bold mb-1">
                            <?= $total_lapangan; ?>
                        </h3>

                        <p class="text-secondary mb-0">
                            Total Lapangan
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="summary-card">
                        <div class="summary-icon">
                            <i class="bi bi-calendar-week"></i>
                        </div>

                        <h3 class="fw-bold mb-1">
                            <?= $total_jadwal; ?>
                        </h3>

                        <p class="text-secondary mb-0">
                            Jadwal Lapangan
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="summary-card">
                        <div class="summary-icon">
                            <i class="bi bi-cash-stack"></i>
                        </div>

                        <h3 class="fw-bold mb-1">
                            Rp<?= number_format($total_pendapatan, 0, ',', '.'); ?>
                        </h3>

                        <p class="text-secondary mb-0">
                            Total Transaksi Berhasil
                        </p>
                    </div>
                </div>

            </div>

            <!-- STATUS BOOKING -->
            <div class="content-card mb-4">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                    <div>
                        <h4 class="fw-bold mb-1">
                            Ringkasan Status Booking
                        </h4>

                        <p class="text-secondary mb-0">
                            Status seluruh booking yang tercatat di sistem.
                        </p>
                    </div>

                    <a href="booking.php" class="btn btn-outline-success">
                        Lihat Data Booking
                    </a>
                </div>

                <div class="row g-3">

                    <div class="col-md-6 col-lg">
                        <div class="small-stat">
                            <div class="text-secondary small mb-1">Menunggu Pembayaran</div>
                            <h4 class="fw-bold mb-0"><?= $booking_menunggu_pembayaran; ?></h4>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg">
                        <div class="small-stat">
                            <div class="text-secondary small mb-1">Menunggu Verifikasi</div>
                            <h4 class="fw-bold mb-0"><?= $booking_menunggu_verifikasi; ?></h4>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg">
                        <div class="small-stat">
                            <div class="text-secondary small mb-1">Terkonfirmasi</div>
                            <h4 class="fw-bold mb-0"><?= $booking_terkonfirmasi; ?></h4>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg">
                        <div class="small-stat">
                            <div class="text-secondary small mb-1">Ditolak</div>
                            <h4 class="fw-bold mb-0"><?= $booking_ditolak; ?></h4>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg">
                        <div class="small-stat">
                            <div class="text-secondary small mb-1">Dibatalkan</div>
                            <h4 class="fw-bold mb-0"><?= $booking_dibatalkan; ?></h4>
                        </div>
                    </div>

                </div>

            </div>

            <div class="row g-4 mb-4">

                <!-- AKSES CEPAT -->
                <div class="col-lg-4">
                    <div class="content-card">

                        <h4 class="fw-bold mb-1">
                            Akses Cepat Admin
                        </h4>

                        <p class="text-secondary mb-4">
                            Menu utama untuk mengelola sistem.
                        </p>

                        <div class="d-grid gap-3">

                            <a href="lapangan.php" class="quick-menu">
                                <i class="bi bi-dribbble"></i>
                                <div>
                                    <strong>Kelola Lapangan</strong>
                                    <div class="small text-secondary">
                                        Tambah, edit, dan hapus data lapangan.
                                    </div>
                                </div>
                            </a>

                            <a href="jadwal_lapangan.php" class="quick-menu">
                                <i class="bi bi-calendar-week"></i>
                                <div>
                                    <strong>Kelola Jadwal</strong>
                                    <div class="small text-secondary">
                                        Atur slot jadwal lapangan.
                                    </div>
                                </div>
                            </a>

                            <a href="pengguna.php" class="quick-menu">
                                <i class="bi bi-people"></i>
                                <div>
                                    <strong>Kelola Pengguna</strong>
                                    <div class="small text-secondary">
                                        Aktifkan, nonaktifkan, dan reset akun.
                                    </div>
                                </div>
                            </a>

                            <a href="verifikasi_pembayaran.php" class="quick-menu">
                                <i class="bi bi-receipt"></i>
                                <div>
                                    <strong>Verifikasi Pembayaran</strong>
                                    <div class="small text-secondary">
                                        Cek bukti pembayaran user.
                                    </div>
                                </div>
                            </a>

                            <a href="laporan_transaksi.php" class="quick-menu">
                                <i class="bi bi-bar-chart"></i>
                                <div>
                                    <strong>Laporan</strong>
                                    <div class="small text-secondary">
                                        Lihat transaksi dan statistik lapangan.
                                    </div>
                                </div>
                            </a>

                        </div>

                    </div>
                </div>

                <!-- BOOKING TERBARU -->
                <div class="col-lg-8">
                    <div class="content-card">

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                            <div>
                                <h4 class="fw-bold mb-1">
                                    Booking Terbaru
                                </h4>

                                <p class="text-secondary mb-0">
                                    Data booking terbaru yang dibuat oleh pengguna.
                                </p>
                            </div>

                            <a href="booking.php" class="btn btn-outline-success">
                                Lihat Semua
                            </a>
                        </div>

                        <?php if ($query_booking_terbaru && mysqli_num_rows($query_booking_terbaru) > 0) : ?>

                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Kode</th>
                                            <th>Pengguna</th>
                                            <th>Lapangan</th>
                                            <th>Jadwal</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php while ($booking = mysqli_fetch_assoc($query_booking_terbaru)) : ?>

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
                                            ?>

                                            <tr>
                                                <td class="fw-bold">
                                                    <?= htmlspecialchars($booking['kode_booking']); ?>
                                                </td>

                                                <td>
                                                    <?= htmlspecialchars($booking['nama_pengguna']); ?>
                                                </td>

                                                <td>
                                                    <div class="fw-semibold">
                                                        <?= htmlspecialchars($booking['nama_lapangan']); ?>
                                                    </div>
                                                    <small class="text-secondary">
                                                        <?= htmlspecialchars($booking['jenis_olahraga']); ?>
                                                    </small>
                                                </td>

                                                <td>
                                                    <div class="fw-semibold">
                                                        <?= date('d M Y', strtotime($booking['tanggal_booking'])); ?>
                                                    </div>
                                                    <small class="text-secondary">
                                                        <?= date('H:i', strtotime($booking['jam_mulai'])); ?>
                                                        -
                                                        <?= date('H:i', strtotime($booking['jam_selesai'])); ?>
                                                    </small>
                                                </td>

                                                <td class="fw-bold text-success">
                                                    Rp<?= number_format($booking['total_biaya'], 0, ',', '.'); ?>
                                                </td>

                                                <td>
                                                    <span class="status-badge <?= $status_class; ?>">
                                                        <?= htmlspecialchars($status_booking); ?>
                                                    </span>
                                                </td>
                                            </tr>

                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>

                        <?php else : ?>

                            <div class="empty-state">
                                <i class="bi bi-calendar-x fs-1 d-block mb-3"></i>
                                <h5 class="fw-bold">Belum ada booking</h5>
                                <p class="mb-0">
                                    Data booking terbaru akan muncul setelah pengguna membuat booking.
                                </p>
                            </div>

                        <?php endif; ?>

                    </div>
                </div>

            </div>

            <!-- PEMBAYARAN -->
            <div class="row g-4">

                <div class="col-lg-4">
                    <div class="content-card">

                        <h4 class="fw-bold mb-1">
                            Ringkasan Pembayaran
                        </h4>

                        <p class="text-secondary mb-4">
                            Status pembayaran yang tercatat.
                        </p>

                        <div class="d-grid gap-3">

                            <div class="small-stat">
                                <div class="text-secondary small mb-1">Belum Dibayar Aktif</div>
                                <h4 class="fw-bold mb-0"><?= $pembayaran_belum_aktif; ?></h4>
                            </div>

                            <div class="small-stat">
                                <div class="text-secondary small mb-1">Dibatalkan Sebelum Bayar</div>
                                <h4 class="fw-bold mb-0"><?= $pembayaran_belum_dibatalkan; ?></h4>
                            </div>

                            <div class="small-stat">
                                <div class="text-secondary small mb-1">Menunggu Verifikasi</div>
                                <h4 class="fw-bold mb-0"><?= $pembayaran_menunggu; ?></h4>
                            </div>

                            <div class="small-stat">
                                <div class="text-secondary small mb-1">Berhasil</div>
                                <h4 class="fw-bold mb-0"><?= $pembayaran_berhasil; ?></h4>
                            </div>

                            <div class="small-stat">
                                <div class="text-secondary small mb-1">Ditolak</div>
                                <h4 class="fw-bold mb-0"><?= $pembayaran_ditolak; ?></h4>
                            </div>

                        </div>

                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="content-card">

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                            <div>
                                <h4 class="fw-bold mb-1">
                                    Pembayaran Menunggu Verifikasi
                                </h4>

                                <p class="text-secondary mb-0">
                                    Pembayaran yang perlu segera dicek admin.
                                </p>
                            </div>

                            <a href="verifikasi_pembayaran.php" class="btn btn-outline-success">
                                Verifikasi
                            </a>
                        </div>

                        <?php if ($query_pembayaran_menunggu && mysqli_num_rows($query_pembayaran_menunggu) > 0) : ?>

                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Kode</th>
                                            <th>Pengguna</th>
                                            <th>Lapangan</th>
                                            <th>Jumlah</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php while ($pembayaran = mysqli_fetch_assoc($query_pembayaran_menunggu)) : ?>

                                            <tr>
                                                <td class="fw-bold">
                                                    <?= htmlspecialchars($pembayaran['kode_booking']); ?>
                                                </td>

                                                <td>
                                                    <?= htmlspecialchars($pembayaran['nama_pengguna']); ?>
                                                </td>

                                                <td>
                                                    <?= htmlspecialchars($pembayaran['nama_lapangan']); ?>
                                                </td>

                                                <td class="fw-bold text-success">
                                                    Rp<?= number_format($pembayaran['jumlah_pembayaran'], 0, ',', '.'); ?>
                                                </td>

                                                <td>
                                                    <span class="status-badge status-verifikasi">
                                                        <?= htmlspecialchars($pembayaran['status_pembayaran']); ?>
                                                    </span>
                                                </td>

                                                <td>
                                                    <a
                                                        href="verifikasi_pembayaran.php?id=<?= $pembayaran['id_pembayaran']; ?>"
                                                        class="btn btn-sm btn-success">
                                                        Cek
                                                    </a>
                                                </td>
                                            </tr>

                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>

                        <?php else : ?>

                            <div class="empty-state">
                                <i class="bi bi-check-circle fs-1 d-block mb-3"></i>
                                <h5 class="fw-bold">Tidak ada pembayaran menunggu verifikasi</h5>
                                <p class="mb-0">
                                    Semua pembayaran sudah diproses atau belum ada upload pembayaran baru.
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