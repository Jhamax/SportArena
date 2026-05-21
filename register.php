<?php
session_start();
include 'includes/header.php';
include 'includes/navbar.php';
?>

<style>
    .register-section {
        min-height: 80vh;
        display: flex;
        align-items: center;
        padding: 60px 0;
        background: linear-gradient(135deg, #f8fafc, #ecfdf5);
    }

    .register-card {
        background: #ffffff;
        border-radius: 28px;
        overflow: hidden;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
    }

    .register-side {
        height: 100%;
        padding: 46px;
        background: linear-gradient(135deg, #166534, #0f172a);
        color: white;
    }

    .register-side h2 {
        font-weight: 800;
        letter-spacing: -0.5px;
    }

    .register-side p {
        color: #d1fae5;
        line-height: 1.7;
    }

    .register-benefit {
        display: flex;
        gap: 14px;
        margin-top: 22px;
    }

    .register-benefit i {
        width: 40px;
        height: 40px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.14);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #bbf7d0;
        flex-shrink: 0;
    }

    .register-form {
        padding: 46px;
    }

    .register-form h3 {
        font-weight: 800;
        letter-spacing: -0.4px;
    }

    .form-label {
        font-weight: 600;
        font-size: 14px;
        color: #374151;
    }

    .form-control {
        border-radius: 14px;
        padding: 12px 14px;
    }

    .form-control:focus {
        border-color: #198754;
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.15);
    }

    .btn-register-submit {
        border-radius: 14px;
        padding: 13px;
        font-weight: 700;
    }

    @media (max-width: 991px) {

        .register-side,
        .register-form {
            padding: 32px;
        }
    }
</style>

<main class="flex-grow-1">

    <section class="register-section">
        <div class="container">

            <div class="row justify-content-center">
                <div class="col-lg-10">

                    <div class="register-card">
                        <div class="row g-0">

                            <div class="col-lg-5">
                                <div class="register-side">

                                    <span class="badge bg-light text-success px-3 py-2 mb-4">
                                        Akun Pengguna
                                    </span>

                                    <h2 class="mb-3">
                                        Daftar untuk mulai booking lapangan.
                                    </h2>

                                    <p class="mb-4">
                                        Akun ini digunakan untuk melakukan booking lapangan,
                                        mengunggah bukti pembayaran, dan memantau status reservasi.
                                    </p>

                                    <div class="register-benefit">
                                        <i class="bi bi-calendar-check"></i>
                                        <div>
                                            <h6 class="fw-bold mb-1">Booking Online</h6>
                                            <p class="small mb-0">
                                                Pilih lapangan dan jadwal tanpa harus datang langsung.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="register-benefit">
                                        <i class="bi bi-receipt"></i>
                                        <div>
                                            <h6 class="fw-bold mb-1">Upload Pembayaran</h6>
                                            <p class="small mb-0">
                                                Bukti pembayaran akan diverifikasi oleh admin.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="register-benefit">
                                        <i class="bi bi-bell"></i>
                                        <div>
                                            <h6 class="fw-bold mb-1">Pantau Status</h6>
                                            <p class="small mb-0">
                                                Lihat status booking: menunggu, terkonfirmasi, atau ditolak.
                                            </p>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="col-lg-7">
                                <div class="register-form">

                                    <h3 class="mb-2">
                                        Register
                                    </h3>

                                    <p class="text-secondary mb-4">
                                        Lengkapi data berikut untuk membuat akun pengguna.
                                    </p>

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

                                    <form action="process/register_process.php" method="POST">

                                        <div class="mb-3">
                                            <label class="form-label">Nama Lengkap</label>
                                            <input
                                                type="text"
                                                name="nama"
                                                class="form-control"
                                                placeholder="Masukkan nama lengkap"
                                                required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Email</label>
                                            <input
                                                type="email"
                                                name="email"
                                                class="form-control"
                                                placeholder="contoh@email.com"
                                                required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Nomor HP</label>
                                            <input
                                                type="text"
                                                name="no_hp"
                                                class="form-control"
                                                placeholder="08xxxxxxxxxx"
                                                required>
                                        </div>

                                        <div class="row">

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Password</label>

                                                <div class="input-group input-password-group">
                                                    <input
                                                        type="password"
                                                        name="password"
                                                        id="register_password"
                                                        class="form-control"
                                                        placeholder="Minimal 8 karakter"
                                                        required>

                                                    <button
                                                        type="button"
                                                        class="btn btn-password-toggle toggle-password"
                                                        data-target="#register_password"
                                                        aria-label="Tampilkan password">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Konfirmasi Password</label>

                                                <div class="input-group input-password-group">
                                                    <input
                                                        type="password"
                                                        name="konfirmasi_password"
                                                        id="register_konfirmasi_password"
                                                        class="form-control"
                                                        placeholder="Ulangi password"
                                                        required>

                                                    <button
                                                        type="button"
                                                        class="btn btn-password-toggle toggle-password"
                                                        data-target="#register_konfirmasi_password"
                                                        aria-label="Tampilkan password">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                </div>
                                            </div>

                                        </div>

                                        <button type="submit" name="register" class="btn btn-success btn-register-submit w-100 mt-2">
                                            Daftar Akun
                                        </button>

                                    </form>

                                    <p class="text-center text-secondary mt-4 mb-0">
                                        Sudah punya akun?
                                        <a href="login.php" class="text-success fw-semibold text-decoration-none">
                                            Login di sini
                                        </a>
                                    </p>

                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>

        </div>
    </section>

</main>

<?php include 'includes/footer.php'; ?>
<?php include 'includes/scripts.php'; ?>