<?php

require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_POST['aksi']) || !isset($_POST['id_user'])) {
    header("Location: ../admin/pengguna.php");
    exit;
}

$aksi = $_POST['aksi'];
$id_user = (int) $_POST['id_user'];

if ($id_user <= 0) {
    $_SESSION['error'] = "Data pengguna tidak valid.";
    header("Location: ../admin/pengguna.php");
    exit;
}

/* Ambil data pengguna */
$stmt_user = mysqli_prepare($conn, "
    SELECT id_user, nama, email, role, status_akun
    FROM data_pengguna
    WHERE id_user = ?
    LIMIT 1
");

mysqli_stmt_bind_param($stmt_user, "i", $id_user);
mysqli_stmt_execute($stmt_user);
$result_user = mysqli_stmt_get_result($stmt_user);
$user = mysqli_fetch_assoc($result_user);

if (!$user) {
    $_SESSION['error'] = "Pengguna tidak ditemukan.";
    header("Location: ../admin/pengguna.php");
    exit;
}

/* Demi keamanan, proses ini hanya untuk role user */
if ($user['role'] !== 'user') {
    $_SESSION['error'] = "Akun admin tidak dapat dikelola dari halaman ini.";
    header("Location: ../admin/pengguna.php");
    exit;
}

/* AKTIFKAN AKUN */
if ($aksi === 'aktifkan') {

    $status = "aktif";

    $stmt = mysqli_prepare($conn, "
        UPDATE data_pengguna
        SET status_akun = ?
        WHERE id_user = ?
        AND role = 'user'
    ");

    mysqli_stmt_bind_param($stmt, "si", $status, $id_user);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Akun pengguna berhasil diaktifkan.";
    } else {
        $_SESSION['error'] = "Gagal mengaktifkan akun pengguna.";
    }

    header("Location: ../admin/pengguna.php");
    exit;
}

/* NONAKTIFKAN AKUN */
if ($aksi === 'nonaktifkan') {

    $status = "nonaktif";

    $stmt = mysqli_prepare($conn, "
        UPDATE data_pengguna
        SET status_akun = ?
        WHERE id_user = ?
        AND role = 'user'
    ");

    mysqli_stmt_bind_param($stmt, "si", $status, $id_user);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Akun pengguna berhasil dinonaktifkan.";
    } else {
        $_SESSION['error'] = "Gagal menonaktifkan akun pengguna.";
    }

    header("Location: ../admin/pengguna.php");
    exit;
}

/* RESET PASSWORD */
if ($aksi === 'reset_password') {

    $password_default = "user12345";
    $password_hash = password_hash($password_default, PASSWORD_DEFAULT);

    $stmt = mysqli_prepare($conn, "
        UPDATE data_pengguna
        SET password = ?
        WHERE id_user = ?
        AND role = 'user'
    ");

    mysqli_stmt_bind_param($stmt, "si", $password_hash, $id_user);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Password pengguna berhasil direset menjadi: user12345";
    } else {
        $_SESSION['error'] = "Gagal reset password pengguna.";
    }

    header("Location: ../admin/pengguna.php");
    exit;
}

/* HAPUS PENGGUNA */
if ($aksi === 'hapus') {

    /* Cek apakah pengguna sudah punya booking */
    $jumlah_booking = 0;

    $stmt_booking = mysqli_prepare($conn, "
        SELECT COUNT(*)
        FROM data_booking
        WHERE id_user = ?
    ");

    mysqli_stmt_bind_param($stmt_booking, "i", $id_user);
    mysqli_stmt_execute($stmt_booking);
    mysqli_stmt_bind_result($stmt_booking, $jumlah_booking);
    mysqli_stmt_fetch($stmt_booking);
    mysqli_stmt_close($stmt_booking);

    if ($jumlah_booking > 0) {
        $_SESSION['error'] = "Pengguna tidak bisa dihapus karena sudah memiliki data booking. Nonaktifkan akun saja.";
        header("Location: ../admin/pengguna.php");
        exit;
    }

    $stmt = mysqli_prepare($conn, "
        DELETE FROM data_pengguna
        WHERE id_user = ?
        AND role = 'user'
    ");

    mysqli_stmt_bind_param($stmt, "i", $id_user);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Akun pengguna berhasil dihapus.";
    } else {
        $_SESSION['error'] = "Gagal menghapus akun pengguna.";
    }

    header("Location: ../admin/pengguna.php");
    exit;
}

$_SESSION['error'] = "Aksi tidak valid.";
header("Location: ../admin/pengguna.php");
exit;

?>