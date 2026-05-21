<?php

require_once __DIR__ . '/../includes/auth_user.php';
require_once __DIR__ . '/../config/koneksi.php';

$id_user = $_SESSION['id_user'];

$stmt = mysqli_prepare($conn, "
    SELECT 
        id_user,
        nama,
        email,
        no_hp,
        role,
        status_akun,
        created_at
    FROM data_pengguna
    WHERE id_user = ?
    LIMIT 1
");

mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    session_unset();
    session_destroy();
    header("Location: ../login.php");
    exit;
}

$total_booking = 0;
$stmt_booking = mysqli_prepare($conn, "
    SELECT COUNT(*) 
    FROM data_booking 
    WHERE id_user = ?
");
mysqli_stmt_bind_param($stmt_booking, "i", $id_user);
mysqli_stmt_execute($stmt_booking);
mysqli_stmt_bind_result($stmt_booking, $total_booking);
mysqli_stmt_fetch($stmt_booking);
mysqli_stmt_close($stmt_booking);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';

?>

<style>
    .profile-page {
        background: #f8fafc;
        padding: 42px 0 70px;
    }

    .profile-hero {
        background: linear-gradient(135deg, #166534, #0f172a);
        color: white;
        border-radius: 28px;
        padding: 34px;
        margin-bottom: 28px;
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.18);
    }

    .profile-hero p {
        color: #d1fae5;
    }

    .profile-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 28px;
        padding: 34px;
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
    }

    .avatar-box {
        width: 96px;
        height: 96px;
        border-radius: 30px;
        background: #dcfce7;
        color: #166534;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 44px;
        margin-bottom: 18px;
    }

    .info-box {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 20px;
        padding: 22px;
        height: 100%;
    }

    .status-badge {
        display: inline-block;
        padding: 8px 14px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 700;
        background: #dcfce7;
        color: #166534;
    }

    .quick-card {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 18px;
        border-radius: 18px;
        border: 1px solid #e5e7eb;
        text-decoration: none;
        color: #111827;
        background: white;
        transition: 0.25s ease;
    }

    .quick-card:hover {
        border-color: #198754;
        background: #ecfdf5;
        color: #166534;
    }

    .quick-card i {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        background: #ecfdf5;
        color: #198754;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
</style>

<main class="flex-grow-1">

    <section class="profile-page">
        <div class="container">

            <div class="profile-hero">
                <div class="row align-items-center gy-3">
                    <div class="col-lg-8">
                        <span class="badge bg-light text-success px-3 py-2 mb-3">
                            Profil Pengguna
                        </span>

                        <h1 class="fw-bold mb-2">
                            Kelola informasi akun kamu.
                        </h1>

                        <p class="mb-0">
                            Data profil digunakan untuk identitas akun, proses booking,
                            dan informasi kontak pengguna.
                        </p>
                    </div>

                    <div class="col-lg-4 text-lg-end">
                        <a href="edit_profil.php" class="btn btn-light fw-semibold px-4">
                            Edit Profil
                        </a>
                    </div>
                </div>
            </div>

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

            <div class="row g-4">

                <div class="col-lg-4">
                    <div class="profile-card text-center h-100">

                        <div class="avatar-box mx-auto">
                            <i class="bi bi-person"></i>
                        </div>

                        <h3 class="fw-bold mb-1">
                            <?= htmlspecialchars($user['nama']); ?>
                        </h3>

                        <p class="text-secondary mb-3">
                            <?= htmlspecialchars($user['email']); ?>
                        </p>

                        <span class="status-badge">
                            Akun <?= htmlspecialchars(ucfirst($user['status_akun'])); ?>
                        </span>

                        <hr class="my-4">

                        <div class="text-start">
                            <small class="text-secondary">Terdaftar Sejak</small>
                            <p class="fw-semibold mb-3">
                                <?= date('d M Y', strtotime($user['created_at'])); ?>
                            </p>

                            <small class="text-secondary">Total Booking</small>
                            <h4 class="fw-bold text-success mb-0">
                                <?= $total_booking; ?>
                            </h4>
                        </div>

                    </div>
                </div>

                <div class="col-lg-8">

                    <div class="profile-card mb-4">

                        <h4 class="fw-bold mb-4">
                            Informasi Akun
                        </h4>

                        <div class="row g-4">

                            <div class="col-md-6">
                                <div class="info-box">
                                    <small class="text-secondary">Nama Lengkap</small>
                                    <h5 class="fw-bold mb-0">
                                        <?= htmlspecialchars($user['nama']); ?>
                                    </h5>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box">
                                    <small class="text-secondary">Email</small>
                                    <h5 class="fw-bold mb-0">
                                        <?= htmlspecialchars($user['email']); ?>
                                    </h5>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box">
                                    <small class="text-secondary">Nomor HP</small>
                                    <h5 class="fw-bold mb-0">
                                        <?= htmlspecialchars($user['no_hp']); ?>
                                    </h5>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box">
                                    <small class="text-secondary">Role Akun</small>
                                    <h5 class="fw-bold mb-0">
                                        <?= htmlspecialchars(ucfirst($user['role'])); ?>
                                    </h5>
                                </div>
                            </div>

                        </div>

                    </div>

                    <div class="profile-card">

                        <h4 class="fw-bold mb-4">
                            Akses Cepat
                        </h4>

                        <div class="d-grid gap-3">

                            <a href="edit_profil.php" class="quick-card">
                                <i class="bi bi-pencil-square"></i>
                                <div>
                                    <strong>Edit Profil</strong>
                                    <div class="small text-secondary">
                                        Ubah nama, nomor HP, atau password.
                                    </div>
                                </div>
                            </a>

                            <a href="riwayat_booking.php" class="quick-card">
                                <i class="bi bi-clock-history"></i>
                                <div>
                                    <strong>Riwayat Booking</strong>
                                    <div class="small text-secondary">
                                        Lihat daftar reservasi yang pernah kamu lakukan.
                                    </div>
                                </div>
                            </a>

                            <a href="notifikasi.php" class="quick-card">
                                <i class="bi bi-bell"></i>
                                <div>
                                    <strong>Notifikasi</strong>
                                    <div class="small text-secondary">
                                        Lihat informasi terbaru terkait booking.
                                    </div>
                                </div>
                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </div>
    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php include __DIR__ . '/../includes/scripts.php'; ?>