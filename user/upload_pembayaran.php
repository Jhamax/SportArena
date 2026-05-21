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

        l.nama_lapangan,
        l.jenis_olahraga,

        p.id_pembayaran,
        p.bukti_pembayaran,
        p.status_pembayaran,
        p.metode_pembayaran,
        p.catatan_admin
    FROM data_booking b
    JOIN data_lapangan l ON b.id_lapangan = l.id_lapangan
    JOIN data_pembayaran p ON b.id_booking = p.id_booking
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

/*
    Upload bukti hanya boleh kalau:
    - Status booking masih Menunggu Pembayaran
    - Status pembayaran Belum Dibayar atau Ditolak
*/
$bisa_upload = (
    $booking['status_booking'] == 'Menunggu Pembayaran' &&
    $booking['status_pembayaran'] == 'Belum Dibayar'
);

if (!$bisa_upload) {
    $_SESSION['error'] = "Booking ini tidak dapat mengupload bukti pembayaran karena status booking sudah " . $booking['status_booking'] . ".";
    header("Location: riwayat_booking.php");
    exit;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';

?>

<style>
    .upload-page {
        background: #f8fafc;
        padding: 42px 0 70px;
    }

    .upload-card {
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

    .payment-box {
        background: linear-gradient(135deg, #166534, #0f172a);
        color: white;
        border-radius: 24px;
        padding: 28px;
    }

    .payment-box p {
        color: #d1fae5;
    }

    .form-control {
        border-radius: 14px;
        padding: 12px 14px;
    }

    .status-badge {
        display: inline-block;
        padding: 8px 14px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 700;
    }

    .status-belum {
        background: #fef3c7;
        color: #92400e;
    }

    .status-verifikasi {
        background: #dbeafe;
        color: #1e40af;
    }

    .status-berhasil {
        background: #dcfce7;
        color: #166534;
    }

    .status-ditolak {
        background: #fee2e2;
        color: #991b1b;
    }

    .deadline-box {
        background: #fff7ed;
        border: 1px solid #fed7aa;
        color: #9a3412;
        border-radius: 18px;
        padding: 20px;
        margin-bottom: 24px;
    }

    .deadline-box i {
        font-size: 28px;
        margin-top: 2px;
    }

    .deadline-box strong {
        color: #991b1b;
    }
</style>

<main class="flex-grow-1">

    <section class="upload-page">
        <div class="container">

            <div class="mb-4">
                <a href="riwayat_booking.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i>
                    Kembali ke Riwayat
                </a>
            </div>

            <?php if (isset($_SESSION['error'])) : ?>
                <div class="alert alert-danger">
                    <?= $_SESSION['error']; ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])) : ?>
                <div class="alert alert-success">
                    <?= $_SESSION['success']; ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <div class="row justify-content-center">
                <div class="col-lg-10">

                    <div class="upload-card">

                        <div class="text-center mb-4">
                            <span class="badge bg-success-subtle text-success px-3 py-2 mb-3">
                                Upload Pembayaran
                            </span>

                            <h1 class="fw-bold mb-2">
                                Upload Bukti Pembayaran
                            </h1>

                            <p class="text-secondary mb-0">
                                Unggah bukti transfer agar admin dapat memverifikasi booking kamu.
                            </p>
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
                                    <small class="text-secondary">Status Pembayaran</small>
                                    <div class="mt-2">
                                        <?php if ($booking['status_pembayaran'] == 'Belum Dibayar') : ?>
                                            <span class="status-badge status-belum">Belum Dibayar</span>
                                        <?php elseif ($booking['status_pembayaran'] == 'Menunggu Verifikasi') : ?>
                                            <span class="status-badge status-verifikasi">Menunggu Verifikasi</span>
                                        <?php elseif ($booking['status_pembayaran'] == 'Berhasil') : ?>
                                            <span class="status-badge status-berhasil">Berhasil</span>
                                        <?php else : ?>
                                            <span class="status-badge status-ditolak">Ditolak</span>
                                        <?php endif; ?>
                                    </div>
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
                                    <small class="text-secondary">Jadwal</small>
                                    <h5 class="fw-bold mb-1">
                                        <?= date('d M Y', strtotime($booking['tanggal_booking'])); ?>
                                    </h5>
                                    <p class="text-secondary mb-0">
                                        <?= date('H:i', strtotime($booking['jam_mulai'])); ?>
                                        -
                                        <?= date('H:i', strtotime($booking['jam_selesai'])); ?>
                                        (<?= $booking['durasi']; ?> jam)
                                    </p>
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
                                        Transfer ke rekening berikut:
                                    </p>
                                </div>

                                <div class="col-lg-5">
                                    <div class="bg-white text-dark rounded-4 p-3">
                                        <strong>Bank BCA</strong><br>
                                        No Rekening: <strong>1234567890</strong><br>
                                        Atas Nama: <strong>Admin SportArena</strong>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <?php if (!empty($booking['batas_waktu_pembayaran'])) : ?>
                            <div class="deadline-box">
                                <div class="d-flex gap-3 align-items-start">
                                    <i class="bi bi-clock-history"></i>

                                    <div>
                                        <strong>Batas waktu pembayaran:</strong><br>

                                        <span class="fs-5">
                                            <?= date('d M Y H:i', strtotime($booking['batas_waktu_pembayaran'])); ?>
                                        </span>

                                        <p class="mb-0 mt-2">
                                            Jika melewati batas waktu ini, booking akan dibatalkan otomatis oleh sistem.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($booking['catatan_admin'])) : ?>
                            <div class="alert alert-warning">
                                <strong>Catatan Admin:</strong><br>
                                <?= nl2br(htmlspecialchars($booking['catatan_admin'])); ?>
                            </div>
                        <?php endif; ?>

                        <form action="../process/pembayaran_process.php" method="POST" enctype="multipart/form-data">

                            <input type="hidden" name="kode_booking" value="<?= htmlspecialchars($booking['kode_booking']); ?>">

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Bukti Pembayaran</label>
                                <input
                                    type="file"
                                    name="bukti_pembayaran"
                                    class="form-control"
                                    accept="image/jpeg,image/png,image/jpg,image/webp,application/pdf"
                                    required>
                                <small class="text-secondary">
                                    Format: JPG, JPEG, PNG, WEBP, atau PDF. Maksimal 3MB.
                                </small>
                            </div>

                            <button type="submit" name="upload_pembayaran" class="btn btn-success btn-lg px-4 fw-semibold">
                                Upload Bukti Pembayaran
                            </button>

                            <a href="riwayat_booking.php" class="btn btn-outline-secondary btn-lg px-4">
                                Lihat Riwayat
                            </a>

                        </form>

                    </div>

                </div>
            </div>

        </div>
    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php include __DIR__ . '/../includes/scripts.php'; ?>