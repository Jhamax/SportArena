<?php

require_once __DIR__ . '/../includes/auth_user.php';
require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_GET['kode'])) {
    header("Location: riwayat_booking.php");
    exit;
}

$kode_booking = trim($_GET['kode']);
$id_user = $_SESSION['id_user'];

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
        b.batas_waktu_pembayaran,
        b.created_at,

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
    JOIN data_lapangan l ON b.id_lapangan = l.id_lapangan
    LEFT JOIN data_pembayaran p ON b.id_booking = p.id_booking
    WHERE b.kode_booking = ?
    AND b.id_user = ?
    LIMIT 1
");

mysqli_stmt_bind_param($stmt, "si", $kode_booking, $id_user);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$booking = mysqli_fetch_assoc($result);

if (!$booking) {
    $_SESSION['error'] = "Data booking tidak ditemukan.";
    header("Location: riwayat_booking.php");
    exit;
}

$status_booking = $booking['status_booking'];
$status_pembayaran = $booking['status_pembayaran'] ?? 'Belum Dibayar';

$deadline = $booking['batas_waktu_pembayaran'];
$deadline_lewat = false;

if (!empty($deadline) && strtotime($deadline) < time() && $status_booking == 'Menunggu Pembayaran') {
    $deadline_lewat = true;
}

$boleh_upload = (
    $status_booking == 'Menunggu Pembayaran' &&
    $status_pembayaran == 'Belum Dibayar' &&
    !$deadline_lewat
);

$status_booking_class = "status-dibatalkan";

if ($status_booking == "Menunggu Pembayaran") {
    $status_booking_class = "status-menunggu";
} elseif ($status_booking == "Menunggu Verifikasi") {
    $status_booking_class = "status-verifikasi";
} elseif ($status_booking == "Terkonfirmasi") {
    $status_booking_class = "status-terkonfirmasi";
} elseif ($status_booking == "Ditolak") {
    $status_booking_class = "status-ditolak";
} elseif ($status_booking == "Dibatalkan") {
    $status_booking_class = "status-dibatalkan";
}

$status_pembayaran_class = "status-belum";

if ($status_pembayaran == "Belum Dibayar") {
    $status_pembayaran_class = "status-belum";
} elseif ($status_pembayaran == "Menunggu Verifikasi") {
    $status_pembayaran_class = "status-verifikasi";
} elseif ($status_pembayaran == "Berhasil") {
    $status_pembayaran_class = "status-terkonfirmasi";
} elseif ($status_pembayaran == "Ditolak") {
    $status_pembayaran_class = "status-ditolak";
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';

?>

<style>
    .confirm-page {
        background: #f8fafc;
        padding: 42px 0 70px;
    }

    .confirm-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 28px;
        padding: 34px;
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
    }

    .success-icon {
        width: 82px;
        height: 82px;
        border-radius: 28px;
        background: #dcfce7;
        color: #166534;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 42px;
        margin: 0 auto 18px;
    }

    .info-box {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 20px;
        padding: 22px;
        height: 100%;
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

    .bank-box {
        background: white;
        color: #111827;
        border-radius: 18px;
        padding: 18px;
    }

    .status-badge {
        padding: 8px 14px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 700;
        display: inline-block;
    }

    .status-menunggu,
    .status-belum {
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

    .deadline-box {
        background: #fff7ed;
        border: 1px solid #fed7aa;
        color: #9a3412;
        border-radius: 18px;
        padding: 18px;
    }

    .proof-img {
        max-width: 100%;
        max-height: 360px;
        object-fit: contain;
        border-radius: 18px;
        border: 1px solid #e5e7eb;
        background: #f8fafc;
    }

    .step-box {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 20px;
        padding: 20px;
        height: 100%;
    }

    .step-number {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: #198754;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        margin-bottom: 12px;
    }
</style>

<main class="flex-grow-1">

    <section class="confirm-page">
        <div class="container">

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

            <div class="row justify-content-center">
                <div class="col-lg-10">

                    <div class="confirm-card">

                        <div class="text-center mb-4">

                            <?php if ($status_booking == 'Terkonfirmasi') : ?>
                                <div class="success-icon">
                                    <i class="bi bi-check-circle"></i>
                                </div>

                                <span class="badge bg-success-subtle text-success px-3 py-2 mb-3">
                                    Booking Terkonfirmasi
                                </span>

                                <h1 class="fw-bold mb-2">
                                    Booking kamu sudah dikonfirmasi.
                                </h1>

                                <p class="text-secondary mb-0">
                                    Pembayaran berhasil diverifikasi oleh admin.
                                </p>

                            <?php elseif ($status_booking == 'Menunggu Verifikasi') : ?>
                                <div class="success-icon">
                                    <i class="bi bi-hourglass-split"></i>
                                </div>

                                <span class="badge bg-primary-subtle text-primary px-3 py-2 mb-3">
                                    Menunggu Verifikasi
                                </span>

                                <h1 class="fw-bold mb-2">
                                    Bukti pembayaran sedang diverifikasi.
                                </h1>

                                <p class="text-secondary mb-0">
                                    Admin akan memeriksa bukti pembayaran yang sudah kamu upload.
                                </p>

                            <?php elseif ($status_booking == 'Ditolak') : ?>
                                <div class="success-icon" style="background:#fee2e2; color:#991b1b;">
                                    <i class="bi bi-x-circle"></i>
                                </div>

                                <span class="badge bg-danger-subtle text-danger px-3 py-2 mb-3">
                                    Booking Ditolak
                                </span>

                                <h1 class="fw-bold mb-2">
                                    Booking kamu ditolak.
                                </h1>

                                <p class="text-secondary mb-0">
                                    Silakan lihat catatan admin atau buat booking baru jika diperlukan.
                                </p>

                            <?php elseif ($status_booking == 'Dibatalkan') : ?>
                                <div class="success-icon" style="background:#e5e7eb; color:#374151;">
                                    <i class="bi bi-calendar-x"></i>
                                </div>

                                <span class="badge bg-secondary-subtle text-secondary px-3 py-2 mb-3">
                                    Booking Dibatalkan
                                </span>

                                <h1 class="fw-bold mb-2">
                                    Booking ini sudah dibatalkan.
                                </h1>

                                <p class="text-secondary mb-0">
                                    Booking tidak dapat dilanjutkan ke proses pembayaran.
                                </p>

                            <?php else : ?>
                                <div class="success-icon">
                                    <i class="bi bi-calendar-check"></i>
                                </div>

                                <span class="badge bg-success-subtle text-success px-3 py-2 mb-3">
                                    Booking Berhasil Dibuat
                                </span>

                                <h1 class="fw-bold mb-2">
                                    Booking berhasil dibuat.
                                </h1>

                                <p class="text-secondary mb-0">
                                    Silakan lakukan pembayaran sebelum batas waktu yang ditentukan.
                                </p>
                            <?php endif; ?>

                        </div>

                        <div class="row g-4 mb-4">

                            <div class="col-md-6">
                                <div class="info-box">
                                    <small class="text-secondary">Kode Booking</small>
                                    <h4 class="fw-bold mb-0">
                                        <?= htmlspecialchars($booking['kode_booking']); ?>
                                    </h4>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box">
                                    <small class="text-secondary">Status Booking</small>
                                    <div class="mt-2">
                                        <span class="status-badge <?= $status_booking_class; ?>">
                                            <?= htmlspecialchars($status_booking); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box">
                                    <small class="text-secondary">Status Pembayaran</small>
                                    <div class="mt-2">
                                        <span class="status-badge <?= $status_pembayaran_class; ?>">
                                            <?= htmlspecialchars($status_pembayaran); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box">
                                    <small class="text-secondary">Dibuat Pada</small>
                                    <h5 class="fw-bold mb-0">
                                        <?= date('d M Y H:i', strtotime($booking['created_at'])); ?>
                                    </h5>
                                </div>
                            </div>

                        </div>

                        <?php if (!empty($booking['batas_waktu_pembayaran']) && $status_booking == 'Menunggu Pembayaran') : ?>
                            <div class="deadline-box mb-4">
                                <div class="d-flex gap-3 align-items-start">
                                    <i class="bi bi-clock-history fs-4"></i>

                                    <div>
                                        <strong>Batas waktu pembayaran:</strong><br>
                                        <?= date('d M Y H:i', strtotime($booking['batas_waktu_pembayaran'])); ?>

                                        <?php if ($deadline_lewat) : ?>
                                            <div class="mt-2 fw-semibold">
                                                Batas waktu pembayaran sudah lewat. Booking akan dibatalkan otomatis.
                                            </div>
                                        <?php else : ?>
                                            <div class="mt-2 small">
                                                Jika melewati batas waktu ini, booking akan dibatalkan otomatis oleh sistem.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="row g-4 mb-4">

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

                        <div class="payment-box mb-4">
                            <div class="row align-items-center gy-3">

                                <div class="col-lg-7">
                                    <h4 class="fw-bold mb-2">
                                        Total Pembayaran
                                    </h4>

                                    <h2 class="fw-bold mb-3">
                                        Rp<?= number_format($booking['total_biaya'], 0, ',', '.'); ?>
                                    </h2>

                                    <p class="mb-0">
                                        Pembayaran dilakukan melalui transfer manual, lalu bukti pembayaran diupload ke sistem.
                                    </p>
                                </div>

                                <div class="col-lg-5">
                                    <div class="bank-box">
                                        <strong>Bank BCA</strong><br>
                                        No Rekening: <strong>1234567890</strong><br>
                                        Atas Nama: <strong>Admin SportArena</strong>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <?php if (!empty($booking['catatan_admin'])) : ?>
                            <div class="alert alert-warning">
                                <strong>Catatan Admin:</strong><br>
                                <?= nl2br(htmlspecialchars($booking['catatan_admin'])); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($booking['bukti_pembayaran'])) : ?>

                            <div class="mb-4">

                                <h4 class="fw-bold mb-3">
                                    Bukti Pembayaran
                                </h4>

                                <?php
                                $bukti = $booking['bukti_pembayaran'];
                                $ext = strtolower(pathinfo($bukti ?? '', PATHINFO_EXTENSION));
                                ?>

                                <?php if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) : ?>

                                    <img 
                                        src="../<?= htmlspecialchars($bukti); ?>" 
                                        class="proof-img"
                                        alt="Bukti Pembayaran"
                                    >

                                <?php elseif ($ext == 'pdf') : ?>

                                    <div class="info-box">
                                        <i class="bi bi-file-earmark-pdf fs-1 text-danger d-block mb-3"></i>
                                        <h6 class="fw-bold">Bukti pembayaran berupa PDF</h6>
                                        <a href="../<?= htmlspecialchars($bukti); ?>" target="_blank" class="btn btn-outline-danger">
                                            Buka PDF
                                        </a>
                                    </div>

                                <?php endif; ?>

                            </div>

                        <?php endif; ?>

                        <?php if ($status_booking == 'Menunggu Pembayaran') : ?>

                            <div class="row g-3 mb-4">

                                <div class="col-md-4">
                                    <div class="step-box">
                                        <div class="step-number">1</div>
                                        <h6 class="fw-bold mb-1">Transfer Pembayaran</h6>
                                        <p class="text-secondary small mb-0">
                                            Transfer sesuai total pembayaran ke rekening admin.
                                        </p>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="step-box">
                                        <div class="step-number">2</div>
                                        <h6 class="fw-bold mb-1">Upload Bukti</h6>
                                        <p class="text-secondary small mb-0">
                                            Upload bukti pembayaran sebelum batas waktu.
                                        </p>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="step-box">
                                        <div class="step-number">3</div>
                                        <h6 class="fw-bold mb-1">Tunggu Verifikasi</h6>
                                        <p class="text-secondary small mb-0">
                                            Admin akan memeriksa dan mengonfirmasi pembayaran.
                                        </p>
                                    </div>
                                </div>

                            </div>

                        <?php endif; ?>

                        <div class="d-flex gap-2 flex-wrap">

                            <a href="riwayat_booking.php" class="btn btn-outline-secondary btn-lg px-4">
                                Riwayat Booking
                            </a>

                            <a href="lapangan.php" class="btn btn-outline-success btn-lg px-4">
                                Booking Lapangan Lain
                            </a>

                            <?php if ($boleh_upload) : ?>
                                <a 
                                    href="upload_pembayaran.php?kode=<?= urlencode($booking['kode_booking']); ?>" 
                                    class="btn btn-success btn-lg px-4 fw-semibold"
                                >
                                    Upload Bukti Pembayaran
                                </a>
                            <?php endif; ?>

                        </div>

                    </div>

                </div>
            </div>

        </div>
    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php include __DIR__ . '/../includes/scripts.php'; ?>