<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$base_url = "/Project_Akhir_RPL/";

$is_login = isset($_SESSION['login']);
$role = $is_login ? $_SESSION['role'] : null;

?>

<style>
    .site-footer {
        background: #0f172a;
        color: #ffffff;
        margin-top: auto;
    }

    .site-footer a {
        color: #cbd5e1;
        text-decoration: none;
        transition: 0.2s ease;
    }

    .site-footer a:hover {
        color: #ffffff;
    }

    .footer-brand {
        font-weight: 800;
        color: #ffffff;
    }

    .footer-title {
        font-weight: 700;
        color: #ffffff;
        margin-bottom: 16px;
    }

    .footer-text {
        color: #cbd5e1;
        line-height: 1.8;
    }

    .footer-list {
        list-style: none;
        padding-left: 0;
        margin-bottom: 0;
    }

    .footer-list li {
        margin-bottom: 10px;
    }

    .footer-contact-item {
        display: flex;
        gap: 10px;
        color: #cbd5e1;
        margin-bottom: 12px;
    }

    .footer-contact-item i {
        color: #22c55e;
        flex-shrink: 0;
    }

    .footer-bottom {
        border-top: 1px solid rgba(255, 255, 255, 0.10);
        color: #94a3b8;
    }

    .footer-badge {
        display: inline-block;
        background: rgba(34, 197, 94, 0.12);
        color: #bbf7d0;
        padding: 7px 12px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 16px;
    }
</style>

<footer id="kontak-admin" class="site-footer pt-5 pb-3">

    <div class="container">

        <div class="row gy-4">

            <!-- BRAND -->
            <div class="col-lg-4 col-md-6">

                <span class="footer-badge">
                    Sistem Booking Lapangan Olahraga
                </span>

                <h4 class="footer-brand mb-3">
                    <i class="bi bi-dribbble"></i>
                    SportArena
                </h4>

                <p class="footer-text mb-0">
                    SportArena adalah sistem booking lapangan olahraga berbasis web
                    yang membantu pengguna melihat informasi lapangan, melakukan booking,
                    mengunggah bukti pembayaran, dan memantau status reservasi.
                </p>

            </div>

            <!-- NAVIGASI -->
            <div class="col-lg-2 col-md-6">

                <h5 class="footer-title">
                    Navigasi
                </h5>

                <ul class="footer-list">

                    <?php if (!$is_login) : ?>

                        <li>
                            <a href="<?= $base_url; ?>index.php">Home</a>
                        </li>

                        <li>
                            <a href="<?= $base_url; ?>index.php#lapangan">Lapangan</a>
                        </li>

                        <li>
                            <a href="<?= $base_url; ?>login.php">Login</a>
                        </li>

                        <li>
                            <a href="<?= $base_url; ?>register.php">Register</a>
                        </li>

                    <?php elseif ($role === 'user') : ?>

                        <li>
                            <a href="<?= $base_url; ?>user/dashboard.php">Dashboard</a>
                        </li>

                        <li>
                            <a href="<?= $base_url; ?>user/lapangan.php">Lapangan</a>
                        </li>

                        <li>
                            <a href="<?= $base_url; ?>user/riwayat_booking.php">Riwayat Booking</a>
                        </li>

                        <li>
                            <a href="<?= $base_url; ?>user/notifikasi.php">Notifikasi</a>
                        </li>

                        <li>
                            <a href="<?= $base_url; ?>user/profil.php">Profil</a>
                        </li>

                    <?php elseif ($role === 'admin') : ?>

                        <li>
                            <a href="<?= $base_url; ?>admin/dashboard.php">Dashboard</a>
                        </li>

                        <li>
                            <a href="<?= $base_url; ?>admin/lapangan.php">Kelola Lapangan</a>
                        </li>

                        <li>
                            <a href="<?= $base_url; ?>admin/pengguna.php">Kelola Pengguna</a>
                        </li>

                        <li>
                            <a href="<?= $base_url; ?>admin/booking.php">Data Booking</a>
                        </li>

                        <li>
                            <a href="<?= $base_url; ?>admin/verifikasi_pembayaran.php">Verifikasi Pembayaran</a>
                        </li>

                    <?php endif; ?>

                </ul>

            </div>

            <!-- LAYANAN -->
            <div class="col-lg-3 col-md-6">

                <h5 class="footer-title">
                    Layanan Sistem
                </h5>

                <ul class="footer-list">
                    <li>Booking lapangan olahraga</li>
                    <li>Pengecekan data lapangan</li>
                    <li>Upload bukti pembayaran</li>
                    <li>Verifikasi pembayaran oleh admin</li>
                    <li>Riwayat dan status booking</li>
                </ul>

            </div>

            <!-- KONTAK ADMIN -->
            <div class="col-lg-3 col-md-6">

                <h5 class="footer-title">
                    Kontak Admin
                </h5>

                <div class="footer-contact-item">
                    <i class="bi bi-envelope-fill"></i>
                    <span>muhammadgan20@gmail.com</span>
                </div>

                <div class="footer-contact-item">
                    <i class="bi bi-telephone-fill"></i>
                    <span>0881-3298-934</span>
                </div>

                <div class="footer-contact-item">
                    <i class="bi bi-geo-alt-fill"></i>
                    <span>Bandung, Indonesia</span>
                </div>

                <p class="footer-text small mt-3 mb-0">
                    Hubungi admin jika terdapat kendala terkait pembayaran,
                    status booking, atau informasi lapangan.
                </p>

            </div>

        </div>

        <div class="footer-bottom text-center pt-3 mt-4">

            <p class="mb-1">
                &copy; 2026 SportArena - Sistem Booking Lapangan Olahraga Berbasis Web
            </p>

        </div>

    </div>

</footer>