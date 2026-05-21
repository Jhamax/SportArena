<?php

require_once __DIR__ . '/../includes/auth_user.php';
require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_GET['id'])) {
    header("Location: lapangan.php");
    exit;
}

$id_lapangan = (int) $_GET['id'];

$stmt = mysqli_prepare($conn, "
    SELECT 
        id_lapangan,
        nama_lapangan,
        jenis_olahraga,
        harga_per_jam,
        fasilitas,
        deskripsi,
        foto,
        status_ketersediaan
    FROM data_lapangan
    WHERE id_lapangan = ?
    LIMIT 1
");

mysqli_stmt_bind_param($stmt, "i", $id_lapangan);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$lapangan = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$lapangan) {
    $_SESSION['error'] = "Data lapangan tidak ditemukan.";
    header("Location: lapangan.php");
    exit;
}

/*
    Ambil jadwal lapangan.
    Sekaligus cek apakah slot jadwal sudah memiliki booking aktif.
*/
$stmt_jadwal = mysqli_prepare($conn, "
    SELECT 
        j.id_jadwal,
        j.tanggal,
        j.jam_mulai,
        j.jam_selesai,
        j.status_jadwal,

        (
            SELECT COUNT(*)
            FROM data_booking b
            WHERE b.id_lapangan = j.id_lapangan
            AND b.tanggal_booking = j.tanggal
            AND b.status_booking IN ('Menunggu Pembayaran', 'Menunggu Verifikasi', 'Terkonfirmasi')
            AND b.jam_mulai < j.jam_selesai
            AND b.jam_selesai > j.jam_mulai
        ) AS jumlah_booking_aktif

    FROM data_jadwal_lapangan j
    WHERE j.id_lapangan = ?
    AND j.tanggal >= CURDATE()
    ORDER BY j.tanggal ASC, j.jam_mulai ASC
    LIMIT 12
");

mysqli_stmt_bind_param($stmt_jadwal, "i", $id_lapangan);
mysqli_stmt_execute($stmt_jadwal);
$result_jadwal = mysqli_stmt_get_result($stmt_jadwal);

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
        overflow: hidden;
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
    }

    .detail-img {
        height: 430px;
        width: 100%;
        object-fit: cover;
        background: linear-gradient(135deg, #16a34a, #0f172a);
    }

    .detail-placeholder {
        height: 430px;
        background: linear-gradient(135deg, #16a34a, #0f172a);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 64px;
    }

    .detail-content {
        padding: 34px;
    }

    .field-type {
        display: inline-block;
        padding: 8px 14px;
        border-radius: 999px;
        background: #ecfdf5;
        color: #166534;
        font-size: 13px;
        font-weight: 700;
    }

    .status-badge {
        display: inline-block;
        padding: 8px 14px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 700;
    }

    .status-tersedia {
        background: #dcfce7;
        color: #166534;
    }

    .status-tidak {
        background: #fee2e2;
        color: #991b1b;
    }

    .status-booked {
        background: #dbeafe;
        color: #1e40af;
    }

    .status-lewat {
        background: #e5e7eb;
        color: #374151;
    }

    .info-box {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 20px;
        padding: 20px;
        height: 100%;
    }

    .info-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        background: #ecfdf5;
        color: #198754;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        margin-bottom: 14px;
    }

    .booking-panel {
        background: linear-gradient(135deg, #166534, #0f172a);
        color: white;
        border-radius: 24px;
        padding: 28px;
    }

    .booking-panel p {
        color: #d1fae5;
    }

    .schedule-box {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 20px;
        padding: 18px;
        height: 100%;
    }

    .schedule-date {
        font-weight: 700;
        color: #111827;
    }

    .schedule-time {
        color: #64748b;
        font-size: 14px;
    }

    .schedule-empty {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 20px;
        padding: 24px;
        text-align: center;
        color: #64748b;
    }

    @media (max-width: 768px) {
        .detail-img,
        .detail-placeholder {
            height: 280px;
        }

        .detail-content {
            padding: 24px;
        }
    }
</style>

<main class="flex-grow-1">

    <section class="detail-page">
        <div class="container">

            <div class="mb-4">
                <a href="lapangan.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i>
                    Kembali ke Daftar Lapangan
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

            <div class="detail-card">

                <?php if (!empty($lapangan['foto'])) : ?>
                    <img
                        src="../<?= htmlspecialchars($lapangan['foto']); ?>"
                        class="detail-img"
                        alt="<?= htmlspecialchars($lapangan['nama_lapangan']); ?>">
                <?php else : ?>
                    <div class="detail-placeholder">
                        <i class="bi bi-dribbble"></i>
                    </div>
                <?php endif; ?>

                <div class="detail-content">

                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">

                        <div>
                            <span class="field-type mb-3">
                                <?= htmlspecialchars($lapangan['jenis_olahraga']); ?>
                            </span>

                            <h1 class="fw-bold mt-3 mb-2">
                                <?= htmlspecialchars($lapangan['nama_lapangan']); ?>
                            </h1>

                            <p class="text-secondary mb-0">
                                <?= htmlspecialchars($lapangan['deskripsi'] ?: 'Deskripsi lapangan belum tersedia.'); ?>
                            </p>
                        </div>

                        <div>
                            <?php if ($lapangan['status_ketersediaan'] == 'tersedia') : ?>
                                <span class="status-badge status-tersedia">
                                    Lapangan Aktif
                                </span>
                            <?php else : ?>
                                <span class="status-badge status-tidak">
                                    Lapangan Tidak Aktif
                                </span>
                            <?php endif; ?>
                        </div>

                    </div>

                    <div class="row g-4 my-4">

                        <div class="col-md-4">
                            <div class="info-box">
                                <div class="info-icon">
                                    <i class="bi bi-cash-stack"></i>
                                </div>
                                <h6 class="fw-bold">Harga Per Jam</h6>
                                <h4 class="fw-bold text-success mb-0">
                                    Rp<?= number_format($lapangan['harga_per_jam'], 0, ',', '.'); ?>
                                </h4>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="info-box">
                                <div class="info-icon">
                                    <i class="bi bi-dribbble"></i>
                                </div>
                                <h6 class="fw-bold">Jenis Olahraga</h6>
                                <p class="mb-0 text-secondary">
                                    <?= htmlspecialchars($lapangan['jenis_olahraga']); ?>
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="info-box">
                                <div class="info-icon">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                                <h6 class="fw-bold">Status Umum</h6>
                                <p class="mb-0 text-secondary">
                                    <?= $lapangan['status_ketersediaan'] == 'tersedia' ? 'Lapangan aktif dan bisa dibooking sesuai jadwal.' : 'Lapangan sedang tidak aktif.'; ?>
                                </p>
                            </div>
                        </div>

                    </div>

                    <div class="mb-4">
                        <h4 class="fw-bold mb-3">
                            Fasilitas Lapangan
                        </h4>

                        <p class="text-secondary mb-0">
                            <?= nl2br(htmlspecialchars($lapangan['fasilitas'] ?: 'Fasilitas belum tersedia.')); ?>
                        </p>
                    </div>

                    <div class="mb-4">

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <div>
                                <h4 class="fw-bold mb-1">
                                    Jadwal Ketersediaan
                                </h4>

                                <p class="text-secondary mb-0">
                                    Jadwal berikut diatur oleh admin sebagai slot operasional lapangan.
                                </p>
                            </div>
                        </div>

                        <?php if (mysqli_num_rows($result_jadwal) > 0) : ?>

                            <div class="row g-3">

                                <?php while ($jadwal = mysqli_fetch_assoc($result_jadwal)) : ?>

                                    <?php
                                    $jadwal_mulai_datetime = $jadwal['tanggal'] . ' ' . $jadwal['jam_mulai'];
                                    $sudah_mulai = strtotime($jadwal_mulai_datetime) <= time();

                                    $status_text = "Tersedia";
                                    $status_class = "status-tersedia";

                                    if ($sudah_mulai) {
                                        $status_text = "Sudah Lewat / Berjalan";
                                        $status_class = "status-lewat";
                                    } elseif ($jadwal['status_jadwal'] == 'tidak tersedia') {
                                        $status_text = "Tidak Tersedia";
                                        $status_class = "status-tidak";
                                    } elseif ((int) $jadwal['jumlah_booking_aktif'] > 0) {
                                        $status_text = "Ada Booking Aktif";
                                        $status_class = "status-booked";
                                    }
                                    ?>

                                    <div class="col-md-6 col-lg-4">
                                        <div class="schedule-box">

                                            <div class="schedule-date mb-1">
                                                <?= date('d M Y', strtotime($jadwal['tanggal'])); ?>
                                            </div>

                                            <div class="schedule-time mb-3">
                                                <?= date('H:i', strtotime($jadwal['jam_mulai'])); ?>
                                                -
                                                <?= date('H:i', strtotime($jadwal['jam_selesai'])); ?>
                                            </div>

                                            <span class="status-badge <?= $status_class; ?>">
                                                <?= $status_text; ?>
                                            </span>

                                        </div>
                                    </div>

                                <?php endwhile; ?>

                            </div>

                        <?php else : ?>

                            <div class="schedule-empty">
                                <i class="bi bi-calendar-x fs-1 d-block mb-3"></i>

                                <h5 class="fw-bold">
                                    Jadwal belum tersedia
                                </h5>

                                <p class="mb-0">
                                    Admin belum mengatur jadwal untuk lapangan ini.
                                </p>
                            </div>

                        <?php endif; ?>

                    </div>

                    <div class="booking-panel">

                        <div class="row align-items-center gy-3">

                            <div class="col-lg-8">
                                <h3 class="fw-bold mb-2">
                                    Ingin booking lapangan ini?
                                </h3>

                                <p class="mb-0">
                                    Setelah memilih lapangan, kamu akan diarahkan untuk memilih tanggal,
                                    jam mulai, durasi, dan melihat total biaya booking.
                                </p>
                            </div>

                            <div class="col-lg-4 text-lg-end">

                                <?php if ($lapangan['status_ketersediaan'] == 'tersedia') : ?>
                                    <a
                                        href="booking.php?id=<?= $lapangan['id_lapangan']; ?>"
                                        class="btn btn-light btn-lg fw-semibold px-4">
                                        Booking Sekarang
                                    </a>
                                <?php else : ?>
                                    <button class="btn btn-light btn-lg fw-semibold px-4" disabled>
                                        Tidak Tersedia
                                    </button>
                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>
    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php include __DIR__ . '/../includes/scripts.php'; ?>