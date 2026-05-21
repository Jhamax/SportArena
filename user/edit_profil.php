<?php

require_once __DIR__ . '/../includes/auth_user.php';
require_once __DIR__ . '/../config/koneksi.php';

$id_user = $_SESSION['id_user'];

$stmt = mysqli_prepare($conn, "
    SELECT 
        id_user,
        nama,
        email,
        no_hp
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

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';

?>

<style>
    .edit-profile-page {
        background: #f8fafc;
        padding: 42px 0 70px;
    }

    .edit-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 28px;
        padding: 34px;
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
    }

    .form-label {
        font-weight: 600;
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

    .password-box {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 22px;
        padding: 24px;
    }
</style>

<main class="flex-grow-1">

    <section class="edit-profile-page">
        <div class="container">

            <div class="mb-4">
                <a href="profil.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i>
                    Kembali ke Profil
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
                <div class="col-lg-9">

                    <div class="edit-card">

                        <div class="mb-4">
                            <span class="badge bg-success-subtle text-success px-3 py-2 mb-3">
                                Edit Profil
                            </span>

                            <h1 class="fw-bold mb-2">
                                Perbarui informasi akun.
                            </h1>

                            <p class="text-secondary mb-0">
                                Ubah data profil pengguna. Email digunakan sebagai identitas login,
                                sehingga tidak diubah dari halaman ini.
                            </p>
                        </div>

                        <form action="../process/update_profil_process.php" method="POST">

                            <input type="hidden" name="aksi" value="update_profil">

                            <div class="row">

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input 
                                        type="text" 
                                        name="nama" 
                                        class="form-control"
                                        value="<?= htmlspecialchars($user['nama']); ?>"
                                        required
                                    >
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input 
                                        type="email" 
                                        class="form-control"
                                        value="<?= htmlspecialchars($user['email']); ?>"
                                        disabled
                                    >
                                    <small class="text-secondary">
                                        Email digunakan untuk login dan tidak dapat diubah di halaman ini.
                                    </small>
                                </div>

                            </div>

                            <div class="mb-4">
                                <label class="form-label">Nomor HP</label>
                                <input 
                                    type="text" 
                                    name="no_hp" 
                                    class="form-control"
                                    value="<?= htmlspecialchars($user['no_hp']); ?>"
                                    required
                                >
                            </div>

                            <div class="password-box mb-4">

                                <h5 class="fw-bold mb-2">
                                    Ubah Password
                                </h5>

                                <p class="text-secondary small mb-4">
                                    Kosongkan bagian password jika tidak ingin mengganti password.
                                    Jika ingin mengganti password, isi semua field password di bawah.
                                </p>

                                <div class="mb-3">
                                    <label class="form-label">Password Lama</label>
                                    <input 
                                        type="password" 
                                        name="password_lama" 
                                        class="form-control"
                                        placeholder="Masukkan password lama"
                                    >
                                </div>

                                <div class="row">

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Password Baru</label>
                                        <input 
                                            type="password" 
                                            name="password_baru" 
                                            class="form-control"
                                            placeholder="Minimal 8 karakter"
                                        >
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Konfirmasi Password Baru</label>
                                        <input 
                                            type="password" 
                                            name="konfirmasi_password" 
                                            class="form-control"
                                            placeholder="Ulangi password baru"
                                        >
                                    </div>

                                </div>

                            </div>

                            <div class="d-flex gap-2 flex-wrap">
                                <button type="submit" class="btn btn-success btn-lg px-4 fw-semibold">
                                    Simpan Perubahan
                                </button>

                                <a href="profil.php" class="btn btn-outline-secondary btn-lg px-4">
                                    Batal
                                </a>
                            </div>

                        </form>

                    </div>

                </div>
            </div>

        </div>
    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php include __DIR__ . '/../includes/scripts.php'; ?>