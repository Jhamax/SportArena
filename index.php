<?php

require_once __DIR__ . '/config/koneksi.php';

$query_lapangan_home = mysqli_query($conn, "
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
    WHERE status_ketersediaan = 'tersedia'
    ORDER BY id_lapangan DESC
    LIMIT 3
");

include 'includes/header.php';
include 'includes/navbar.php';

?>

<style>
    .hero-booking {
        background:
            linear-gradient(135deg, rgba(11, 84, 50, 0.96), rgba(15, 23, 42, 0.96)),
            url('assets/img/bg-lapangan.jpg');
        background-size: cover;
        background-position: center;
        color: white;
        padding: 90px 0;
    }

    .hero-label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.18);
        color: #dcfce7;
        padding: 8px 14px;
        border-radius: 999px;
        font-size: 14px;
        margin-bottom: 18px;
    }

    .hero-title {
        font-size: 54px;
        line-height: 1.08;
        font-weight: 800;
        letter-spacing: -1.2px;
    }

    .hero-desc {
        color: #d1fae5;
        font-size: 18px;
        line-height: 1.8;
        max-width: 560px;
    }

    .booking-panel {
        background: white;
        color: #111827;
        border-radius: 28px;
        padding: 28px;
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.22);
    }

    .booking-panel .form-label {
        font-weight: 600;
        font-size: 14px;
        color: #374151;
    }

    .quick-info-card {
        background: rgba(255, 255, 255, 0.10);
        border: 1px solid rgba(255, 255, 255, 0.16);
        border-radius: 20px;
        padding: 18px;
        height: 100%;
    }

    .section-title {
        font-weight: 800;
        letter-spacing: -0.5px;
    }

    .sport-card {
        border: 1px solid #e5e7eb;
        border-radius: 24px;
        padding: 24px;
        background: white;
        height: 100%;
        transition: 0.25s ease;
    }

    .sport-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 18px 35px rgba(15, 23, 42, 0.08);
        border-color: #198754;
    }

    .sport-icon {
        width: 58px;
        height: 58px;
        border-radius: 18px;
        background: #ecfdf5;
        color: #198754;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        margin-bottom: 18px;
    }

    .field-card {
        border: 0;
        border-radius: 26px;
        overflow: hidden;
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08);
        height: 100%;
        background: white;
    }

    .field-thumb {
        height: 190px;
        background: linear-gradient(135deg, #16a34a, #0f172a);
        color: white;
        display: flex;
        align-items: end;
        padding: 22px;
    }

    .field-thumb h5 {
        font-weight: 800;
        margin: 0;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 12px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 600;
        background: #dcfce7;
        color: #166534;
    }

    .step-box {
        position: relative;
        border: 1px solid #e5e7eb;
        border-radius: 24px;
        padding: 26px;
        height: 100%;
        background: white;
    }

    .step-number {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: #198754;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        margin-bottom: 18px;
    }

    .admin-note {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 28px;
        padding: 34px;
    }

    @media (max-width: 768px) {
        .hero-booking {
            padding: 60px 0;
        }

        .hero-title {
            font-size: 38px;
        }

        .booking-panel {
            padding: 22px;
        }
    }
</style>

<main class="flex-grow-1">

    <!-- HERO -->
    <section class="hero-booking">
        <div class="container">

            <div class="row align-items-center gy-5">

                <div class="col-lg-7">

                    <div class="hero-label">
                        <i class="bi bi-calendar-check"></i>
                        Sistem Reservasi Lapangan Online
                    </div>

                    <h1 class="hero-title mb-4">
                        Cari jadwal kosong, pilih lapangan, lalu booking tanpa harus datang langsung.
                    </h1>

                    <p class="hero-desc mb-4">
                        SportArena membantu pengguna melihat informasi lapangan, mengecek jadwal,
                        melakukan reservasi, mengunggah bukti pembayaran, dan memantau status booking
                        dalam satu sistem berbasis web.
                    </p>

                    <div class="d-flex gap-3 flex-wrap mb-5">
                        <a href="register.php" class="btn btn-light btn-lg px-4 fw-semibold">
                            Mulai Booking
                        </a>

                        <a href="#lapangan" class="btn btn-outline-light btn-lg px-4 fw-semibold">
                            Lihat Lapangan
                        </a>
                    </div>

                    <div class="row g-3">

                        <div class="col-md-4">
                            <div class="quick-info-card">
                                <h5 class="fw-bold mb-1">Cek Jadwal</h5>
                                <p class="mb-0 small text-white-50">
                                    Lihat slot waktu sebelum melakukan booking.
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="quick-info-card">
                                <h5 class="fw-bold mb-1">Anti Double Booking</h5>
                                <p class="mb-0 small text-white-50">
                                    Sistem mengecek ketersediaan sebelum data disimpan.
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="quick-info-card">
                                <h5 class="fw-bold mb-1">Verifikasi Admin</h5>
                                <p class="mb-0 small text-white-50">
                                    Pembayaran dicek admin sebelum booking dikonfirmasi.
                                </p>
                            </div>
                        </div>

                    </div>

                </div>

                <div class="col-lg-5">

                    <div class="booking-panel">

                        <span class="badge bg-success-subtle text-success px-3 py-2 mb-3">
                            Untuk Pengguna Baru
                        </span>

                        <h4 class="fw-bold mb-2">
                            Mulai Reservasi Lapangan
                        </h4>

                        <p class="text-secondary mb-4">
                            Buat akun terlebih dahulu untuk melihat detail lapangan, memilih jadwal, dan
                            melakukan booking.
                        </p>

                        <div class="d-grid gap-3 mb-4">

                            <div class="d-flex gap-3 align-items-start">
                                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 34px; height: 34px; flex-shrink: 0;">
                                    1
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1">Daftar atau Login</h6>
                                    <p class="text-secondary small mb-0">
                                        Masuk sebagai pengguna untuk mengakses fitur booking.
                                    </p>
                                </div>
                            </div>

                            <div class="d-flex gap-3 align-items-start">
                                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 34px; height: 34px; flex-shrink: 0;">
                                    2
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1">Pilih Lapangan</h6>
                                    <p class="text-secondary small mb-0">
                                        Lihat daftar lapangan, fasilitas, harga, dan status ketersediaan.
                                    </p>
                                </div>
                            </div>

                            <div class="d-flex gap-3 align-items-start">
                                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 34px; height: 34px; flex-shrink: 0;">
                                    3
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1">Booking dan Pembayaran</h6>
                                    <p class="text-secondary small mb-0">
                                        Tentukan jadwal, buat booking, lalu upload bukti pembayaran.
                                    </p>
                                </div>
                            </div>

                            <div class="d-flex gap-3 align-items-start">
                                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 34px; height: 34px; flex-shrink: 0;">
                                    4
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1">Tunggu Verifikasi Admin</h6>
                                    <p class="text-secondary small mb-0">
                                        Admin akan memeriksa pembayaran dan mengubah status booking.
                                    </p>
                                </div>
                            </div>

                        </div>

                        <div class="d-grid gap-2">

                            <a href="register.php" class="btn btn-success btn-lg fw-semibold">
                                Register Sekarang
                            </a>

                            <a href="login.php" class="btn btn-outline-success btn-lg fw-semibold">
                                Login
                            </a>

                            <a href="#lapangan" class="btn btn-light border fw-semibold">
                                Lihat Lapangan Tersedia
                            </a>

                        </div>

                        <p class="small text-secondary text-center mt-3 mb-0">
                            Booking hanya bisa dilakukan setelah pengguna login.
                        </p>

                    </div>

                </div>

            </div>

        </div>
    </section>

    <!-- JENIS OLAHRAGA -->
    <section class="py-5">
        <div class="container">

            <div class="row align-items-end mb-4">
                <div class="col-lg-7">
                    <h2 class="section-title">
                        Pilih jenis lapangan sesuai kebutuhan bermain.
                    </h2>
                    <p class="text-secondary mb-0">
                        Informasi lapangan ditampilkan berdasarkan jenis olahraga, fasilitas,
                        harga per jam, dan status ketersediaan.
                    </p>
                </div>
            </div>

            <div class="row g-4">

                <div class="col-md-6 col-lg-3">
                    <div class="sport-card">
                        <div class="sport-icon">
                            <i class="bi bi-dribbble"></i>
                        </div>
                        <h5 class="fw-bold">Futsal</h5>
                        <p class="text-secondary small mb-0">
                            Cocok untuk booking tim, latihan rutin, atau pertandingan kecil.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="sport-card">
                        <div class="sport-icon">
                            <i class="bi bi-lightning-charge"></i>
                        </div>
                        <h5 class="fw-bold">Badminton</h5>
                        <p class="text-secondary small mb-0">
                            Pilihan lapangan indoor dengan jadwal sesi yang mudah dicek.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="sport-card">
                        <div class="sport-icon">
                            <i class="bi bi-basket2"></i>
                        </div>
                        <h5 class="fw-bold">Basket</h5>
                        <p class="text-secondary small mb-0">
                            Lapangan untuk latihan, sparing, dan kegiatan komunitas.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="sport-card">
                        <div class="sport-icon">
                            <i class="bi bi-record-circle"></i>
                        </div>
                        <h5 class="fw-bold">Padel</h5>
                        <p class="text-secondary small mb-0">
                            Lapangan modern untuk olahraga padel dengan sistem reservasi.
                        </p>
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- LAPANGAN -->
    <section class="py-5 bg-white" id="lapangan">
        <div class="container">

            <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="section-title mb-2">
                        Lapangan yang tersedia
                    </h2>
                    <p class="text-secondary mb-0">
                        Data lapangan ditampilkan langsung dari database yang dikelola admin.
                    </p>
                </div>

                <a href="lapangan.php" class="btn btn-outline-success fw-semibold">
                    Lihat Semua Lapangan
                </a>
            </div>

            <?php if (mysqli_num_rows($query_lapangan_home) > 0) : ?>

                <div class="row g-4">

                    <?php while ($lapangan = mysqli_fetch_assoc($query_lapangan_home)) : ?>

                        <div class="col-lg-4">
                            <div class="field-card">

                                <?php if (!empty($lapangan['foto'])) : ?>

                                    <div
                                        class="field-thumb"
                                        style="
                                            background:
                                                linear-gradient(180deg, rgba(15, 23, 42, 0.05), rgba(15, 23, 42, 0.82)),
                                                url('<?= htmlspecialchars($lapangan['foto']); ?>');
                                            background-size: cover;
                                            background-position: center;
                                        ">
                                        <div>
                                            <span class="badge bg-light text-success mb-2">
                                                <?= htmlspecialchars($lapangan['jenis_olahraga']); ?>
                                            </span>

                                            <h5>
                                                <?= htmlspecialchars($lapangan['nama_lapangan']); ?>
                                            </h5>
                                        </div>
                                    </div>

                                <?php else : ?>

                                    <div class="field-thumb">
                                        <div>
                                            <span class="badge bg-light text-success mb-2">
                                                <?= htmlspecialchars($lapangan['jenis_olahraga']); ?>
                                            </span>

                                            <h5>
                                                <?= htmlspecialchars($lapangan['nama_lapangan']); ?>
                                            </h5>
                                        </div>
                                    </div>

                                <?php endif; ?>

                                <div class="p-4">

                                    <div class="d-flex justify-content-between align-items-center mb-3">

                                        <span class="status-badge">
                                            <i class="bi bi-check-circle-fill"></i>
                                            Tersedia
                                        </span>

                                        <span class="fw-bold text-success">
                                            Rp<?= number_format($lapangan['harga_per_jam'], 0, ',', '.'); ?>/jam
                                        </span>

                                    </div>

                                    <p class="text-secondary mb-3">
                                        <?= htmlspecialchars($lapangan['deskripsi'] ?: 'Deskripsi lapangan belum tersedia.'); ?>
                                    </p>

                                    <div class="d-flex gap-2 flex-wrap mb-4">

                                        <?php
                                        $fasilitas_list = explode(',', $lapangan['fasilitas'] ?? '');
                                        $jumlah_fasilitas = 0;
                                        ?>

                                        <?php foreach ($fasilitas_list as $fasilitas) : ?>

                                            <?php
                                            $fasilitas = trim($fasilitas);

                                            if ($fasilitas == '') {
                                                continue;
                                            }

                                            if ($jumlah_fasilitas >= 3) {
                                                break;
                                            }

                                            $jumlah_fasilitas++;
                                            ?>

                                            <span class="badge text-bg-light">
                                                <?= htmlspecialchars($fasilitas); ?>
                                            </span>

                                        <?php endforeach; ?>

                                        <?php if ($jumlah_fasilitas == 0) : ?>
                                            <span class="badge text-bg-light">
                                                Fasilitas belum tersedia
                                            </span>
                                        <?php endif; ?>

                                    </div>

                                    <a
                                        href="user/detail_lapangan.php?id=<?= $lapangan['id_lapangan']; ?>"
                                        class="btn btn-success w-100">
                                        Detail & Booking
                                    </a>

                                </div>
                            </div>
                        </div>

                    <?php endwhile; ?>

                </div>

            <?php else : ?>

                <div class="text-center p-5 bg-light rounded-4">
                    <i class="bi bi-inbox fs-1 text-secondary d-block mb-3"></i>

                    <h4 class="fw-bold">
                        Belum ada lapangan tersedia
                    </h4>

                    <p class="text-secondary mb-0">
                        Data lapangan akan muncul setelah admin menambahkan data lapangan.
                    </p>
                </div>

            <?php endif; ?>

        </div>
    </section>

    <!-- KEUNGGULAN SISTEM -->
    <section class="py-5">
        <div class="container">

            <div class="text-center mb-5">
                <h2 class="section-title">
                    Booking lapangan tanpa ribet.
                </h2>
                <p class="text-secondary mb-0">
                    Semua proses dibuat lebih jelas, mulai dari cek jadwal sampai status pembayaran.
                </p>
            </div>

            <div class="row g-4">

                <div class="col-md-6 col-lg-3">
                    <div class="step-box">
                        <div class="sport-icon">
                            <i class="bi bi-search"></i>
                        </div>
                        <h5 class="fw-bold">Cari Lapangan</h5>
                        <p class="text-secondary mb-0">
                            Pengguna dapat melihat daftar lapangan berdasarkan jenis olahraga.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="step-box">
                        <div class="sport-icon">
                            <i class="bi bi-calendar2-check"></i>
                        </div>
                        <h5 class="fw-bold">Cek Jadwal</h5>
                        <p class="text-secondary mb-0">
                            Jadwal lapangan dapat dicek sebelum pengguna melakukan booking.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="step-box">
                        <div class="sport-icon">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <h5 class="fw-bold">Upload Bukti</h5>
                        <p class="text-secondary mb-0">
                            Pembayaran dilakukan manual dan bukti pembayaran diunggah ke sistem.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="step-box">
                        <div class="sport-icon">
                            <i class="bi bi-bell"></i>
                        </div>
                        <h5 class="fw-bold">Status Booking</h5>
                        <p class="text-secondary mb-0">
                            Pengguna dapat melihat apakah booking masih menunggu, diterima, atau ditolak.
                        </p>
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- ALUR BOOKING -->
    <section class="py-5 bg-white">
        <div class="container">

            <div class="text-center mb-5">
                <h2 class="section-title">
                    Alur booking dari pengguna sampai dikonfirmasi.
                </h2>
                <p class="text-secondary mb-0">
                    Alur ini mengikuti proses utama dalam sistem: pilih lapangan, booking,
                    pembayaran, lalu verifikasi admin.
                </p>
            </div>

            <div class="row g-4">

                <div class="col-md-6 col-lg-3">
                    <div class="step-box">
                        <div class="step-number">1</div>
                        <h5 class="fw-bold">Pilih Lapangan</h5>
                        <p class="text-secondary mb-0">
                            Pengguna melihat daftar lapangan, detail fasilitas, harga, dan jadwal.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="step-box">
                        <div class="step-number">2</div>
                        <h5 class="fw-bold">Cek Jadwal</h5>
                        <p class="text-secondary mb-0">
                            Sistem mengecek ketersediaan slot agar tidak terjadi double booking.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="step-box">
                        <div class="step-number">3</div>
                        <h5 class="fw-bold">Upload Bukti</h5>
                        <p class="text-secondary mb-0">
                            Setelah booking, pengguna mengunggah bukti pembayaran manual.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="step-box">
                        <div class="step-number">4</div>
                        <h5 class="fw-bold">Diverifikasi</h5>
                        <p class="text-secondary mb-0">
                            Admin memeriksa pembayaran dan mengubah status booking.
                        </p>
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- CTA -->
    <section class="py-5">
        <div class="container">

            <div class="admin-note">

                <div class="row align-items-center gy-4">

                    <div class="col-lg-8">
                        <h2 class="fw-bold mb-2">
                            Booking lapangan lebih rapi, data transaksi lebih mudah dipantau.
                        </h2>
                        <p class="text-secondary mb-0">
                            Mulai dari reservasi, status pembayaran, sampai laporan transaksi,
                            semua data tersimpan di dalam sistem.
                        </p>
                    </div>

                    <div class="col-lg-4 text-lg-end">
                        <a href="register.php" class="btn btn-success btn-lg px-4 fw-semibold">
                            Buat Akun Pengguna
                        </a>
                    </div>

                </div>

            </div>

        </div>
    </section>

</main>

<?php include 'includes/footer.php'; ?>
<?php include 'includes/scripts.php'; ?>