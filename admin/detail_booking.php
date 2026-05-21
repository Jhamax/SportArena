<?php

require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_GET['kode'])) {
    header("Location: booking.php");
    exit;
}

$kode_booking = trim($_GET['kode']);

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

        u.id_user,
        u.nama AS nama_pengguna,
        u.email,
        u.no_hp,
        u.status_akun,

        l.id_lapangan,
        l.nama_lapangan,
        l.jenis_olahraga,
        l.fasilitas,
        l.deskripsi,
        l.foto,

        p.id_pembayaran,
        p.bukti_pembayaran,
        p.tanggal_pembayaran,
        p.jumlah_pembayaran,
        p.metode_pembayaran,
        p.status_pembayaran,
        p.catatan_admin
    FROM data_booking b
    JOIN data_pengguna u ON b.id_user = u.id_user
    JOIN data_lapangan l ON b.id_lapangan = l.id_lapangan
    LEFT JOIN data_pembayaran p ON b.id_booking = p.id_booking
    WHERE b.kode_booking = ?
    LIMIT 1
");

mysqli_stmt_bind_param($stmt, "s", $kode_booking);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$booking = mysqli_fetch_assoc($result);

if (!$booking) {
    $_SESSION['error'] = "Data booking tidak ditemukan.";
    header("Location: booking.php");
    exit;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';

?>

<style>
    .detail-page {
        background: #f8fafc;
        padding: 42px 0 70px;
    }

    .detail-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 28px;
        padding: 34px;
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
    }

    .info-box {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 20px;
        padding: 22px;
        height: 100%;
    }

    .field-img {
        width: 100%;
        height: 260px;
        object-fit: cover;
        border-radius: 22px;
        background: #e5e7eb;
    }

    .placeholder-img {
        width: 100%;
        height: 260px;
        border-radius: 22px;
        background: linear-gradient(135deg, #166534, #0f172a);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 54px;
    }

    .status-badge {
        padding: 8px 14px;
        border-radius: 999px;
        font-size: 13px;
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

    .payment-box {
        background: linear-gradient(135deg, #166534, #0f172a);
        color: white;
        border-radius: 24px;
        padding: 28px;
    }

    .payment-box p {
        color: #d1fae5;
    }

    .proof-img {
        max-width: 100%;
        max-height: 420px;
        object-fit: contain;
        border-radius: 18px;
        border: 1px solid #e5e7eb;
        background: #f8fafc;
    }
</style>

<main class="flex-grow-1">

    <section class="detail-page">
        <div class="container">

            <div class="mb-4">
                <a href="booking.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i>
                    Kembali ke Data Booking
                </a>
            </div>

            <div class="detail-card">

                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">

                    <div>
                        <span class="badge bg-success-subtle text-success px-3 py-2 mb-3">
                            Detail Booking
                        </span>

                        <h1 class="fw-bold mb-1">
                            <?= htmlspecialchars($booking['kode_booking']); ?>
                        </h1>

                        <p class="text-secondary mb-0">
                            Detail data booking, pengguna, lapangan, dan pembayaran.
                        </p>
                    </div>

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

                    <span class="status-badge <?= $status_class; ?>">
                        <?= htmlspecialchars($status_booking); ?>
                    </span>

                </div>

                <div class="row g-4 mb-4">

                    <div class="col-lg-5">

                        <?php if (!empty($booking['foto'])) : ?>
                            <img 
                                src="../<?= htmlspecialchars($booking['foto']); ?>" 
                                class="field-img"
                                alt="<?= htmlspecialchars($booking['nama_lapangan']); ?>"
                            >
                        <?php else : ?>
                            <div class="placeholder-img">
                                <i class="bi bi-dribbble"></i>
                            </div>
                        <?php endif; ?>

                    </div>

                    <div class="col-lg-7">

                        <div class="row g-3">

                            <div class="col-md-6">
                                <div class="info-box">
                                    <small class="text-secondary">Nama Pengguna</small>
                                    <h5 class="fw-bold mb-1">
                                        <?= htmlspecialchars($booking['nama_pengguna']); ?>
                                    </h5>
                                    <p class="text-secondary mb-0">
                                        <?= htmlspecialchars($booking['email']); ?>
                                    </p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box">
                                    <small class="text-secondary">Kontak Pengguna</small>
                                    <h5 class="fw-bold mb-1">
                                        <?= htmlspecialchars($booking['no_hp']); ?>
                                    </h5>
                                    <p class="text-secondary mb-0">
                                        Akun <?= htmlspecialchars($booking['status_akun']); ?>
                                    </p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box">
                                    <small class="text-secondary">Lapangan</small>
                                    <h5 class="fw-bold mb-1">
                                        <?= htmlspecialchars($booking['nama_lapangan']); ?>
                                    </h5>
                                    <p class="text-secondary mb-0">
                                        <?= htmlspecialchars($booking['jenis_olahraga']); ?>
                                    </p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box">
                                    <small class="text-secondary">Tanggal Booking</small>
                                    <h5 class="fw-bold mb-0">
                                        <?= date('d M Y', strtotime($booking['tanggal_booking'])); ?>
                                    </h5>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box">
                                    <small class="text-secondary">Jam Booking</small>
                                    <h5 class="fw-bold mb-0">
                                        <?= date('H:i', strtotime($booking['jam_mulai'])); ?>
                                        -
                                        <?= date('H:i', strtotime($booking['jam_selesai'])); ?>
                                    </h5>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box">
                                    <small class="text-secondary">Durasi</small>
                                    <h5 class="fw-bold mb-0">
                                        <?= htmlspecialchars($booking['durasi']); ?> Jam
                                    </h5>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>

                <div class="payment-box mb-4">

                    <div class="row align-items-center gy-3">

                        <div class="col-lg-8">
                            <h4 class="fw-bold mb-2">
                                Informasi Pembayaran
                            </h4>

                            <p class="mb-0">
                                Metode: <strong><?= htmlspecialchars($booking['metode_pembayaran'] ?? 'Transfer Bank'); ?></strong><br>
                                Total Booking: <strong>Rp<?= number_format($booking['total_biaya'], 0, ',', '.'); ?></strong><br>
                                Jumlah Pembayaran: 
                                <strong>
                                    Rp<?= number_format($booking['jumlah_pembayaran'] ?? $booking['total_biaya'], 0, ',', '.'); ?>
                                </strong><br>
                                Status Pembayaran:
                                <strong><?= htmlspecialchars($booking['status_pembayaran'] ?? 'Belum Dibayar'); ?></strong><br>
                                Tanggal Upload:
                                <strong>
                                    <?= !empty($booking['tanggal_pembayaran']) ? date('d M Y', strtotime($booking['tanggal_pembayaran'])) : '-'; ?>
                                </strong>
                            </p>
                        </div>

                        <div class="col-lg-4 text-lg-end">
                            <?php if ($booking['status_pembayaran'] == 'Menunggu Verifikasi') : ?>
                                <a 
                                    href="verifikasi_pembayaran.php?id=<?= $booking['id_pembayaran']; ?>" 
                                    class="btn btn-light btn-lg fw-semibold px-4"
                                >
                                    Verifikasi
                                </a>
                            <?php else : ?>
                                <a 
                                    href="verifikasi_pembayaran.php" 
                                    class="btn btn-light btn-lg fw-semibold px-4"
                                >
                                    Halaman Pembayaran
                                </a>
                            <?php endif; ?>
                        </div>

                    </div>

                </div>

                <?php if (!empty($booking['catatan_admin'])) : ?>
                    <div class="alert alert-warning">
                        <strong>Catatan Admin:</strong><br>
                        <?= nl2br(htmlspecialchars($booking['catatan_admin'])); ?>
                    </div>
                <?php endif; ?>

                <div class="mb-4">

                    <h4 class="fw-bold mb-3">
                        Bukti Pembayaran
                    </h4>

                    <?php
                    $bukti = $booking['bukti_pembayaran'];
                    $ext = strtolower(pathinfo($bukti ?? '', PATHINFO_EXTENSION));
                    ?>

                    <?php if (!empty($bukti) && in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) : ?>

                        <img 
                            src="../<?= htmlspecialchars($bukti); ?>" 
                            class="proof-img"
                            alt="Bukti Pembayaran"
                        >

                    <?php elseif (!empty($bukti) && $ext == 'pdf') : ?>

                        <div class="info-box">
                            <i class="bi bi-file-earmark-pdf fs-1 text-danger d-block mb-3"></i>
                            <h6 class="fw-bold">Bukti pembayaran berupa PDF</h6>
                            <a href="../<?= htmlspecialchars($bukti); ?>" target="_blank" class="btn btn-outline-danger">
                                Buka PDF
                            </a>
                        </div>

                    <?php else : ?>

                        <div class="info-box">
                            <p class="text-secondary mb-0">
                                Belum ada bukti pembayaran yang diupload.
                            </p>
                        </div>

                    <?php endif; ?>

                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a href="booking.php" class="btn btn-outline-secondary px-4">
                        Kembali
                    </a>

                    <a href="pengguna.php" class="btn btn-outline-success px-4">
                        Kelola Pengguna
                    </a>

                    <a href="lapangan.php" class="btn btn-success px-4">
                        Kelola Lapangan
                    </a>
                </div>

            </div>

        </div>
    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php include __DIR__ . '/../includes/scripts.php'; ?>