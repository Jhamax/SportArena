<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$base_url = "/Project_Akhir_RPL/";

$is_login = isset($_SESSION['login']);
$role = $is_login ? $_SESSION['role'] : null;
$nama = $is_login ? $_SESSION['nama'] : null;

?>

<nav class="navbar navbar-expand-lg bg-white shadow-sm sticky-top py-3">
    <div class="container">

        <!-- Logo -->
        <a class="navbar-brand" href="<?= $base_url; ?>index.php">
            <i class="bi bi-dribbble"></i> SportArena
        </a>

        <!-- Toggle Mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMenu">

            <?php if (!$is_login) : ?>

                <!-- NAVBAR PUBLIK -->
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">

                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base_url; ?>index.php">
                            Home
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base_url; ?>lapangan.php">
                            Lapangan
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#kontak-admin">
                            Kontak Admin
                        </a>
                    </li>

                </ul>

                <div class="d-flex gap-2">
                    <a href="<?= $base_url; ?>login.php" class="btn btn-login px-4">
                        Login
                    </a>

                    <a href="<?= $base_url; ?>register.php" class="btn btn-register px-4">
                        Register
                    </a>
                </div>

            <?php elseif ($role === 'user') : ?>

                <!-- NAVBAR USER -->
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">

                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base_url; ?>user/dashboard.php">
                            Dashboard
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base_url; ?>user/lapangan.php">
                            Lapangan
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base_url; ?>user/riwayat_booking.php">
                            Riwayat Booking
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base_url; ?>user/notifikasi.php">
                            Notifikasi
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base_url; ?>user/profil.php">
                            Profil
                        </a>
                    </li>

                </ul>

                <div class="d-flex align-items-center gap-3">

                    <span class="d-none d-lg-inline text-secondary small">
                        <strong><?= htmlspecialchars($nama); ?></strong>
                    </span>

                    <a href="<?= $base_url; ?>logout.php" class="btn btn-outline-danger px-4">
                        Logout
                    </a>

                </div>

            <?php elseif ($role === 'admin') : ?>

                <!-- NAVBAR ADMIN -->
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">

                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base_url; ?>admin/dashboard.php">
                            Dashboard
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base_url; ?>admin/lapangan.php">
                            Kelola Lapangan
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base_url; ?>admin/pengguna.php">
                            Kelola Pengguna
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base_url; ?>admin/jadwal_lapangan.php">
                            Jadwal
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base_url; ?>admin/booking.php">
                            Booking
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base_url; ?>admin/verifikasi_pembayaran.php">
                            Pembayaran
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base_url; ?>admin/laporan_transaksi.php">
                            Laporan
                        </a>
                    </li>

                </ul>

                <div class="d-flex align-items-center gap-3">

                    <span class="d-none d-lg-inline text-secondary small">
                        Admin, <strong><?= htmlspecialchars($nama); ?></strong>
                    </span>

                    <a href="<?= $base_url; ?>logout.php" class="btn btn-outline-danger px-4">
                        Logout
                    </a>

                </div>

            <?php endif; ?>

        </div>
    </div>
</nav>