<?php
session_start();

if (isset($_SESSION['login'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin/dashboard.php");
        exit;
    } else {
        header("Location: user/dashboard.php");
        exit;
    }
}

include 'includes/header.php';
include 'includes/navbar.php';
?>

<style>
    .login-section {
        min-height: 80vh;
        display: flex;
        align-items: center;
        padding: 70px 0;
        background:
            linear-gradient(135deg, rgba(15, 23, 42, 0.96), rgba(22, 101, 52, 0.92)),
            url('assets/img/bg-lapangan.jpg');
        background-size: cover;
        background-position: center;
    }

    .login-card {
        background: #ffffff;
        border-radius: 28px;
        overflow: hidden;
        box-shadow: 0 28px 70px rgba(0, 0, 0, 0.25);
    }

    .login-info {
        height: 100%;
        padding: 48px;
        background: linear-gradient(135deg, #166534, #0f172a);
        color: white;
    }

    .login-info h2 {
        font-weight: 800;
        letter-spacing: -0.6px;
    }

    .login-info p {
        color: #d1fae5;
        line-height: 1.8;
    }

    .login-point {
        display: flex;
        gap: 14px;
        margin-top: 22px;
    }

    .login-point i {
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

    .login-form {
        padding: 48px;
    }

    .login-form h3 {
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
        padding: 13px 14px;
    }

    .form-control:focus {
        border-color: #198754;
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.15);
    }

    .btn-login-submit {
        border-radius: 14px;
        padding: 13px;
        font-weight: 700;
    }

    @media (max-width: 991px) {

        .login-info,
        .login-form {
            padding: 32px;
        }
    }
</style>

<main class="flex-grow-1">

    <section class="login-section">
        <div class="container">

            <div class="row justify-content-center">
                <div class="col-lg-10">

                    <div class="login-card">
                        <div class="row g-0">

                            <div class="col-lg-5">
                                <div class="login-info">

                                    <span class="badge bg-light text-success px-3 py-2 mb-4">
                                        Masuk Sistem
                                    </span>

                                    <h2 class="mb-3">
                                        Login untuk mengakses fitur booking.
                                    </h2>

                                    <p class="mb-4">
                                        Masuk menggunakan akun yang sudah terdaftar untuk melakukan
                                        reservasi lapangan, melihat riwayat booking, dan memantau status pembayaran.
                                    </p>

                                    <div class="login-point">
                                        <i class="bi bi-calendar-check"></i>
                                        <div>
                                            <h6 class="fw-bold mb-1">Booking Lapangan</h6>
                                            <p class="small mb-0">
                                                Pilih lapangan dan jadwal yang tersedia.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="login-point">
                                        <i class="bi bi-receipt"></i>
                                        <div>
                                            <h6 class="fw-bold mb-1">Pembayaran Manual</h6>
                                            <p class="small mb-0">
                                                Upload bukti pembayaran untuk diverifikasi admin.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="login-point">
                                        <i class="bi bi-bell"></i>
                                        <div>
                                            <h6 class="fw-bold mb-1">Pantau Status Booking</h6>
                                            <p class="small mb-0">
                                                Lihat status reservasi, pembayaran, dan konfirmasi dari admin.
                                            </p>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="col-lg-7">
                                <div class="login-form">

                                    <h3 class="mb-2">
                                        Login
                                    </h3>

                                    <p class="text-secondary mb-4">
                                        Masukkan email dan password akun kamu.
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

                                    <form action="process/login_process.php" method="POST">

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
                                            <label class="form-label">Password</label>

                                            <div class="input-group input-password-group">
                                                <input
                                                    type="password"
                                                    name="password"
                                                    id="login_password"
                                                    class="form-control"
                                                    placeholder="Masukkan password"
                                                    required>

                                                <button
                                                    type="button"
                                                    class="btn btn-password-toggle toggle-password"
                                                    data-target="#login_password"
                                                    aria-label="Tampilkan password">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <button type="submit" name="login" class="btn btn-success btn-login-submit w-100 mt-2">
                                            Login
                                        </button>

                                    </form>

                                    <p class="text-center text-secondary mt-4 mb-0">
                                        Belum punya akun?
                                        <a href="register.php" class="text-success fw-semibold text-decoration-none">
                                            Register di sini
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