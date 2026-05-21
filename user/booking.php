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

if (!$lapangan) {
    $_SESSION['error'] = "Data lapangan tidak ditemukan.";
    header("Location: lapangan.php");
    exit;
}

if ($lapangan['status_ketersediaan'] !== 'tersedia') {
    $_SESSION['error'] = "Lapangan sedang tidak tersedia.";
    header("Location: detail_lapangan.php?id=" . $id_lapangan);
    exit;
}

/*
    Ambil jadwal lapangan yang dibuat admin.
    Sekaligus cek apakah ada booking aktif yang bentrok dengan jadwal tersebut.
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

$data_jadwal = [];

while ($row = mysqli_fetch_assoc($result_jadwal)) {
    $data_jadwal[] = $row;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';

?>

<style>
    .booking-page {
        background: #f8fafc;
        padding: 42px 0 70px;
    }

    .booking-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 28px;
        overflow: hidden;
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
    }

    .booking-img {
        height: 100%;
        min-height: 520px;
        width: 100%;
        object-fit: cover;
        background: linear-gradient(135deg, #16a34a, #0f172a);
    }

    .booking-placeholder {
        height: 100%;
        min-height: 520px;
        background: linear-gradient(135deg, #16a34a, #0f172a);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 70px;
    }

    .booking-form-area {
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

    .form-label {
        font-weight: 600;
        color: #374151;
    }

    .form-control,
    .form-select {
        border-radius: 14px;
        padding: 12px 14px;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #198754;
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.15);
    }

    .summary-box {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 20px;
        padding: 20px;
    }

    .schedule-card {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 18px;
        height: 100%;
    }

    .schedule-date {
        font-weight: 800;
        color: #111827;
    }

    .schedule-time {
        color: #64748b;
        font-size: 14px;
    }

    .status-badge {
        display: inline-block;
        padding: 7px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
    }

    .status-tersedia {
        background: #dcfce7;
        color: #166534;
    }

    .status-booked {
        background: #dbeafe;
        color: #1e40af;
    }

    .status-tidak {
        background: #fee2e2;
        color: #991b1b;
    }

    .status-lewat {
        background: #e5e7eb;
        color: #374151;
    }

    .schedule-empty {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 18px;
        padding: 22px;
        text-align: center;
        color: #64748b;
    }

    .price-box {
        background: linear-gradient(135deg, #166534, #0f172a);
        color: white;
        border-radius: 20px;
        padding: 22px;
    }

    .price-box p {
        color: #d1fae5;
    }

    @media (max-width: 991px) {
        .booking-img,
        .booking-placeholder {
            min-height: 280px;
        }

        .booking-form-area {
            padding: 26px;
        }
    }
</style>

<main class="flex-grow-1">

    <section class="booking-page">
        <div class="container">

            <div class="mb-4">
                <a href="detail_lapangan.php?id=<?= $lapangan['id_lapangan']; ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i>
                    Kembali ke Detail
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

            <div class="booking-card">
                <div class="row g-0">

                    <div class="col-lg-5">
                        <?php if (!empty($lapangan['foto'])) : ?>
                            <img 
                                src="../<?= htmlspecialchars($lapangan['foto']); ?>" 
                                class="booking-img"
                                alt="<?= htmlspecialchars($lapangan['nama_lapangan']); ?>"
                            >
                        <?php else : ?>
                            <div class="booking-placeholder">
                                <i class="bi bi-dribbble"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-lg-7">
                        <div class="booking-form-area">

                            <span class="field-type mb-3">
                                <?= htmlspecialchars($lapangan['jenis_olahraga']); ?>
                            </span>

                            <h1 class="fw-bold mb-2">
                                Booking <?= htmlspecialchars($lapangan['nama_lapangan']); ?>
                            </h1>

                            <p class="text-secondary mb-4">
                                Pilih jadwal yang tersedia, lalu sistem akan mengecek kembali agar tidak terjadi double booking.
                            </p>

                            <div class="summary-box mb-4">
                                <div class="row g-3">

                                    <div class="col-md-6">
                                        <small class="text-secondary">Harga Per Jam</small>
                                        <div class="fw-bold text-success">
                                            Rp<?= number_format($lapangan['harga_per_jam'], 0, ',', '.'); ?>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <small class="text-secondary">Fasilitas</small>
                                        <div class="fw-semibold">
                                            <?= htmlspecialchars($lapangan['fasilitas'] ?: '-'); ?>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- JADWAL LAPANGAN -->
                            <div class="mb-4">

                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                    <div>
                                        <h4 class="fw-bold mb-1">
                                            Jadwal Lapangan
                                        </h4>
                                        <p class="text-secondary mb-0">
                                            Jadwal berikut diatur oleh admin. Klik “Gunakan Jadwal” untuk mengisi form booking otomatis.
                                        </p>
                                    </div>
                                </div>

                                <?php if (count($data_jadwal) > 0) : ?>

                                    <div class="row g-3">

                                        <?php foreach ($data_jadwal as $jadwal) : ?>

                                            <?php
                                            $jadwal_mulai_datetime = $jadwal['tanggal'] . ' ' . $jadwal['jam_mulai'];
                                            $sudah_mulai = strtotime($jadwal_mulai_datetime) <= time();

                                            $status_text = "Tersedia";
                                            $status_class = "status-tersedia";
                                            $bisa_dipilih = true;

                                            if ($sudah_mulai) {
                                                $status_text = "Sudah Lewat / Berjalan";
                                                $status_class = "status-lewat";
                                                $bisa_dipilih = false;
                                            } elseif ($jadwal['status_jadwal'] == 'tidak tersedia') {
                                                $status_text = "Tidak Tersedia";
                                                $status_class = "status-tidak";
                                                $bisa_dipilih = false;
                                            } elseif ((int) $jadwal['jumlah_booking_aktif'] > 0) {
                                                $status_text = "Ada Booking Aktif";
                                                $status_class = "status-booked";
                                                $bisa_dipilih = false;
                                            }

                                            $jam_mulai_input = date('H:i', strtotime($jadwal['jam_mulai']));

                                            $durasi_jadwal = (strtotime($jadwal['jam_selesai']) - strtotime($jadwal['jam_mulai'])) / 3600;
                                            $durasi_jadwal = (int) $durasi_jadwal;

                                            if ($durasi_jadwal < 1 || $durasi_jadwal > 4) {
                                                $durasi_jadwal = "";
                                            }
                                            ?>

                                            <div class="col-md-6">
                                                <div class="schedule-card">

                                                    <div class="schedule-date mb-1">
                                                        <?= date('d M Y', strtotime($jadwal['tanggal'])); ?>
                                                    </div>

                                                    <div class="schedule-time mb-3">
                                                        <?= date('H:i', strtotime($jadwal['jam_mulai'])); ?>
                                                        -
                                                        <?= date('H:i', strtotime($jadwal['jam_selesai'])); ?>
                                                    </div>

                                                    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                                                        <span class="status-badge <?= $status_class; ?>">
                                                            <?= $status_text; ?>
                                                        </span>

                                                        <?php if ($bisa_dipilih) : ?>
                                                            <button 
                                                                type="button"
                                                                class="btn btn-sm btn-success btn-pilih-jadwal"
                                                                data-tanggal="<?= htmlspecialchars($jadwal['tanggal']); ?>"
                                                                data-jam="<?= htmlspecialchars($jam_mulai_input); ?>"
                                                                data-durasi="<?= htmlspecialchars($durasi_jadwal); ?>"
                                                            >
                                                                Gunakan Jadwal
                                                            </button>
                                                        <?php else : ?>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled>
                                                                Tidak Bisa Dipilih
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>

                                                </div>
                                            </div>

                                        <?php endforeach; ?>

                                    </div>

                                <?php else : ?>

                                    <div class="schedule-empty">
                                        <i class="bi bi-calendar-x fs-1 d-block mb-3"></i>
                                        <h5 class="fw-bold">
                                            Jadwal belum tersedia
                                        </h5>
                                        <p class="mb-0">
                                            Admin belum menambahkan jadwal untuk lapangan ini.
                                        </p>
                                    </div>

                                <?php endif; ?>

                            </div>

                            <form action="../process/booking_process.php" method="POST" id="bookingForm">

                                <input type="hidden" name="id_lapangan" value="<?= $lapangan['id_lapangan']; ?>">
                                <input type="hidden" name="harga_per_jam" value="<?= $lapangan['harga_per_jam']; ?>">

                                <div class="row">

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Tanggal Booking</label>
                                        <input 
                                            type="date" 
                                            name="tanggal_booking" 
                                            id="tanggal_booking"
                                            class="form-control"
                                            min="<?= date('Y-m-d'); ?>"
                                            required
                                        >
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Jam Mulai</label>
                                        <input 
                                            type="time" 
                                            name="jam_mulai" 
                                            id="jam_mulai"
                                            class="form-control"
                                            required
                                        >
                                    </div>

                                </div>

                                <div class="mb-4">
                                    <label class="form-label">Durasi Booking</label>
                                    <select name="durasi" id="durasi" class="form-select" required>
                                        <option value="">Pilih durasi</option>
                                        <option value="1">1 Jam</option>
                                        <option value="2">2 Jam</option>
                                        <option value="3">3 Jam</option>
                                        <option value="4">4 Jam</option>
                                    </select>
                                </div>

                                <div class="price-box mb-4">
                                    <h5 class="fw-bold mb-2">
                                        Informasi Pembayaran
                                    </h5>
                                    <p class="mb-0">
                                        Total biaya akan dihitung otomatis berdasarkan harga per jam
                                        dan durasi booking. Setelah booking dibuat, status awal menjadi
                                        <strong>Menunggu Pembayaran</strong>.
                                    </p>
                                </div>

                                <div class="d-flex gap-2 flex-wrap">
                                    <button type="submit" name="booking" class="btn btn-success btn-lg px-4 fw-semibold">
                                        Buat Booking
                                    </button>

                                    <a href="lapangan.php" class="btn btn-outline-secondary btn-lg px-4">
                                        Batal
                                    </a>
                                </div>

                            </form>

                        </div>
                    </div>

                </div>
            </div>

        </div>
    </section>

</main>

<script>
    document.querySelectorAll('.btn-pilih-jadwal').forEach(function (button) {
        button.addEventListener('click', function () {
            const tanggal = this.getAttribute('data-tanggal');
            const jam = this.getAttribute('data-jam');
            const durasi = this.getAttribute('data-durasi');

            const inputTanggal = document.getElementById('tanggal_booking');
            const inputJam = document.getElementById('jam_mulai');
            const selectDurasi = document.getElementById('durasi');
            const formBooking = document.getElementById('bookingForm');

            if (inputTanggal) {
                inputTanggal.value = tanggal;
            }

            if (inputJam) {
                inputJam.value = jam;
            }

            if (selectDurasi && durasi !== '') {
                selectDurasi.value = durasi;
            }

            if (formBooking) {
                formBooking.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        });
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php include __DIR__ . '/../includes/scripts.php'; ?>