<?php

session_start();

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../config/koneksi.php';

$id_user = $_SESSION['id_user'];
$nama_user = $_SESSION['nama'];

/* Hitung total booking user */
$total_booking = 0;
$query_total = mysqli_prepare($conn, "SELECT COUNT(*) FROM data_booking WHERE id_user = ?");
mysqli_stmt_bind_param($query_total, "i", $id_user);
mysqli_stmt_execute($query_total);
mysqli_stmt_bind_result($query_total, $total_booking);
mysqli_stmt_fetch($query_total);
mysqli_stmt_close($query_total);

/* Hitung booking menunggu pembayaran */
$menunggu_pembayaran = 0;
$query_menunggu = mysqli_prepare($conn, "SELECT COUNT(*) FROM data_booking WHERE id_user = ? AND status_booking = 'Menunggu Pembayaran'");
mysqli_stmt_bind_param($query_menunggu, "i", $id_user);
mysqli_stmt_execute($query_menunggu);
mysqli_stmt_bind_result($query_menunggu, $menunggu_pembayaran);
mysqli_stmt_fetch($query_menunggu);
mysqli_stmt_close($query_menunggu);

/* Hitung booking terkonfirmasi */
$booking_terkonfirmasi = 0;
$query_terkonfirmasi = mysqli_prepare($conn, "SELECT COUNT(*) FROM data_booking WHERE id_user = ? AND status_booking = 'Terkonfirmasi'");
mysqli_stmt_bind_param($query_terkonfirmasi, "i", $id_user);
mysqli_stmt_execute($query_terkonfirmasi);
mysqli_stmt_bind_result($query_terkonfirmasi, $booking_terkonfirmasi);
mysqli_stmt_fetch($query_terkonfirmasi);
mysqli_stmt_close($query_terkonfirmasi);

/* Hitung notifikasi belum dibaca */
$notifikasi_baru = 0;
$query_notifikasi = mysqli_prepare($conn, "SELECT COUNT(*) FROM data_notifikasi WHERE id_user = ? AND status_baca = 'belum dibaca'");
mysqli_stmt_bind_param($query_notifikasi, "i", $id_user);
mysqli_stmt_execute($query_notifikasi);
mysqli_stmt_bind_result($query_notifikasi, $notifikasi_baru);
mysqli_stmt_fetch($query_notifikasi);
mysqli_stmt_close($query_notifikasi);

/* Ambil riwayat booking terbaru */
$query_booking = mysqli_prepare($conn, "
    SELECT 
        b.kode_booking,
        b.tanggal_booking,
        b.jam_mulai,
        b.jam_selesai,
        b.total_biaya,
        b.status_booking,
        l.nama_lapangan,
        l.jenis_olahraga
    FROM data_booking b
    JOIN data_lapangan l ON b.id_lapangan = l.id_lapangan
    WHERE b.id_user = ?
    ORDER BY b.created_at DESC
    LIMIT 5
");
mysqli_stmt_bind_param($query_booking, "i", $id_user);
mysqli_stmt_execute($query_booking);
$result_booking = mysqli_stmt_get_result($query_booking);

/* Ambil lapangan tersedia */
$query_lapangan = mysqli_query($conn, "
    SELECT 
        id_lapangan,
        nama_lapangan,
        jenis_olahraga,
        harga_per_jam,
        fasilitas,
        status_ketersediaan
    FROM data_lapangan
    WHERE status_ketersediaan = 'tersedia'
    ORDER BY id_lapangan DESC
    LIMIT 3
");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';

?>

<style>
    .dashboard-section {
        padding: 42px 0 70px;
        background: #f8fafc;
    }

    .welcome-card {
        background: linear-gradient(135deg, #166534, #0f172a);
        color: white;
        border-radius: 28px;
        padding: 34px;
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.18);
    }

    .welcome-card p {
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
        width: 48px;
        height: 48px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #ecfdf5;
        color: #198754;
        font-size: 22px;
        margin-bottom: 18px;
    }

    .content-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 24px;
        padding: 26px;
        height: 100%;
    }

    .quick-action {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 18px;
        border-radius: 18px;
        border: 1px solid #e5e7eb;
        text-decoration: none;
        color: #111827;
        background: white;
        transition: 0.25s ease;
    }

    .quick-action:hover {
        border-color: #198754;
        background: #ecfdf5;
        color: #166534;
    }

    .quick-action i {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        background: #ecfdf5;
        color: #198754;
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

    .field-mini-card {
        border: 1px solid #e5e7eb;
        border-radius: 20px;
        padding: 20px;
        height: 100%;
        background: #ffffff;
    }

    .field-type {
        display: inline-block;
        font-size: 12px;
        font-weight: 700;
        padding: 6px 10px;
        border-radius: 999px;
        background: #ecfdf5;
        color: #166534;
        margin-bottom: 12px;
    }

    .empty-state {
        border: 1px dashed #cbd5e1;
        border-radius: 18px;
        padding: 28px;
        text-align: center;
        color: #64748b;
        background: #f8fafc;
    }

    @media (max-width: 768px) {
        .welcome-card {
            padding: 26px;
        }

        .content-card {
            padding: 22px;
        }
    }
</style>

<main class="flex-grow-1">

    <section class="dashboard-section">
        <div class="container">

            <!-- WELCOME -->
            <div class="welcome-card mb-4">
                <div class="row align-items-center gy-3">

                    <div class="col-lg-8">
                        <span class="badge bg-light text-success px-3 py-2 mb-3">
                            User Dashboard
                        </span>

                        <h1 class="fw-bold mb-2">
                            Halo, <?= htmlspecialchars($nama_user); ?>.
                        </h1>

                        <p class="mb-0">
                            Dari halaman ini kamu bisa melihat ringkasan booking,
                            mengecek status pembayaran, melihat riwayat reservasi,
                            dan mulai melakukan booking lapangan.
                        </p>
                    </div>

                    <div class="col-lg-4 text-lg-end">
                        <a href="lapangan.php" class="btn btn-light btn-lg fw-semibold px-4">
                            <i class="bi bi-calendar-plus"></i>
                            Booking Lapangan
                        </a>
                    </div>

                </div>
            </div>

            <!-- SUMMARY -->
            <div class="row g-4 mb-4">

                <div class="col-md-6 col-lg-3">
                    <div class="summary-card">
                        <div class="summary-icon">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <h3 class="fw-bold mb-1">
                            <?= $total_booking; ?>
                        </h3>
                        <p class="text-secondary mb-0">
                            Total Booking
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="summary-card">
                        <div class="summary-icon">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <h3 class="fw-bold mb-1">
                            <?= $menunggu_pembayaran; ?>
                        </h3>
                        <p class="text-secondary mb-0">
                            Menunggu Pembayaran
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="summary-card">
                        <div class="summary-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <h3 class="fw-bold mb-1">
                            <?= $booking_terkonfirmasi; ?>
                        </h3>
                        <p class="text-secondary mb-0">
                            Terkonfirmasi
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="summary-card">
                        <div class="summary-icon">
                            <i class="bi bi-bell"></i>
                        </div>
                        <h3 class="fw-bold mb-1">
                            <?= $notifikasi_baru; ?>
                        </h3>
                        <p class="text-secondary mb-0">
                            Notifikasi Baru
                        </p>
                    </div>
                </div>

            </div>

            <div class="row g-4">

                <!-- RIWAYAT BOOKING -->
                <div class="col-lg-8">
                    <div class="content-card">

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                            <div>
                                <h4 class="fw-bold mb-1">
                                    Booking Terbaru
                                </h4>
                                <p class="text-secondary mb-0">
                                    Status reservasi lapangan yang terakhir kamu lakukan.
                                </p>
                            </div>

                            <a href="riwayat_booking.php" class="btn btn-outline-success">
                                Lihat Riwayat
                            </a>
                        </div>

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
                                            <th>Status</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php while ($booking = mysqli_fetch_assoc($result_booking)) : ?>

                                            <?php
                                            $status = $booking['status_booking'];
                                            $status_class = "status-dibatalkan";

                                            if ($status == "Menunggu Pembayaran") {
                                                $status_class = "status-menunggu";
                                            } elseif ($status == "Menunggu Verifikasi") {
                                                $status_class = "status-verifikasi";
                                            } elseif ($status == "Terkonfirmasi") {
                                                $status_class = "status-terkonfirmasi";
                                            } elseif ($status == "Ditolak") {
                                                $status_class = "status-ditolak";
                                            } elseif ($status == "Dibatalkan") {
                                                $status_class = "status-dibatalkan";
                                            }
                                            ?>

                                            <tr>
                                                <td class="fw-semibold">
                                                    <?= htmlspecialchars($booking['kode_booking']); ?>
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
                                                        <?= htmlspecialchars($status); ?>
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
                                <p class="mb-3">
                                    Kamu belum melakukan reservasi lapangan.
                                </p>
                                <a href="lapangan.php" class="btn btn-success">
                                    Booking Sekarang
                                </a>
                            </div>

                        <?php endif; ?>

                    </div>
                </div>

                <!-- QUICK ACTION -->
                <div class="col-lg-4">
                    <div class="content-card">

                        <h4 class="fw-bold mb-1">
                            Akses Cepat
                        </h4>

                        <p class="text-secondary mb-4">
                            Menu utama yang sering digunakan pengguna.
                        </p>

                        <div class="d-grid gap-3">

                            <a href="lapangan.php" class="quick-action">
                                <i class="bi bi-search"></i>
                                <div>
                                    <strong>Cari Lapangan</strong>
                                    <div class="small text-secondary">
                                        Lihat daftar dan detail lapangan.
                                    </div>
                                </div>
                            </a>

                            <a href="riwayat_booking.php" class="quick-action">
                                <i class="bi bi-clock-history"></i>
                                <div>
                                    <strong>Riwayat Booking</strong>
                                    <div class="small text-secondary">
                                        Pantau status reservasi kamu.
                                    </div>
                                </div>
                            </a>

                            <a href="notifikasi.php" class="quick-action">
                                <i class="bi bi-bell"></i>
                                <div>
                                    <strong>Notifikasi</strong>
                                    <div class="small text-secondary">
                                        Lihat informasi status booking.
                                    </div>
                                </div>
                            </a>

                            <a href="profil.php" class="quick-action">
                                <i class="bi bi-person"></i>
                                <div>
                                    <strong>Profil Saya</strong>
                                    <div class="small text-secondary">
                                        Kelola data akun pengguna.
                                    </div>
                                </div>
                            </a>

                        </div>

                    </div>
                </div>

            </div>

            <!-- LAPANGAN TERSEDIA -->
            <div class="content-card mt-4">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                    <div>
                        <h4 class="fw-bold mb-1">
                            Lapangan Tersedia
                        </h4>
                        <p class="text-secondary mb-0">
                            Beberapa lapangan yang dapat dipilih untuk proses booking.
                        </p>
                    </div>

                    <a href="lapangan.php" class="btn btn-outline-success">
                        Lihat Semua
                    </a>
                </div>

                <?php if (mysqli_num_rows($query_lapangan) > 0) : ?>

                    <div class="row g-4">

                        <?php while ($lapangan = mysqli_fetch_assoc($query_lapangan)) : ?>

                            <div class="col-md-6 col-lg-4">
                                <div class="field-mini-card">

                                    <span class="field-type">
                                        <?= htmlspecialchars($lapangan['jenis_olahraga']); ?>
                                    </span>

                                    <h5 class="fw-bold">
                                        <?= htmlspecialchars($lapangan['nama_lapangan']); ?>
                                    </h5>

                                    <p class="text-secondary small">
                                        <?= htmlspecialchars($lapangan['fasilitas'] ?: 'Fasilitas belum tersedia.'); ?>
                                    </p>

                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <span class="fw-bold text-success">
                                            Rp<?= number_format($lapangan['harga_per_jam'], 0, ',', '.'); ?>/jam
                                        </span>

                                        <a href="detail_lapangan.php?id=<?= $lapangan['id_lapangan']; ?>" class="btn btn-sm btn-success">
                                            Detail
                                        </a>
                                    </div>

                                </div>
                            </div>

                        <?php endwhile; ?>

                    </div>

                <?php else : ?>

                    <div class="empty-state">
                        <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                        <h5 class="fw-bold">Belum ada data lapangan</h5>
                        <p class="mb-0">
                            Data lapangan akan muncul setelah admin menambahkan data lapangan.
                        </p>
                    </div>

                <?php endif; ?>

            </div>

        </div>
    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php include __DIR__ . '/../includes/scripts.php'; ?>