<?php

require_once __DIR__ . '/../includes/auth_user.php';
require_once __DIR__ . '/../config/koneksi.php';

$id_user = $_SESSION['id_user'];

if (!isset($_POST['aksi']) || $_POST['aksi'] !== 'update_profil') {
    header("Location: ../user/profil.php");
    exit;
}

$nama = trim($_POST['nama'] ?? '');
$no_hp = trim($_POST['no_hp'] ?? '');

$password_lama = $_POST['password_lama'] ?? '';
$password_baru = $_POST['password_baru'] ?? '';
$konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

if ($nama == "" || $no_hp == "") {
    $_SESSION['error'] = "Nama dan nomor HP wajib diisi.";
    header("Location: ../user/edit_profil.php");
    exit;
}

/* Ambil password lama dari database */
$stmt_user = mysqli_prepare($conn, "
    SELECT password 
    FROM data_pengguna 
    WHERE id_user = ?
    LIMIT 1
");

mysqli_stmt_bind_param($stmt_user, "i", $id_user);
mysqli_stmt_execute($stmt_user);
$result_user = mysqli_stmt_get_result($stmt_user);
$user = mysqli_fetch_assoc($result_user);

if (!$user) {
    $_SESSION['error'] = "Data pengguna tidak ditemukan.";
    header("Location: ../user/edit_profil.php");
    exit;
}

/*
    Kalau user mengisi salah satu field password,
    maka semua field password wajib diisi.
*/
$ingin_ganti_password = (
    $password_lama !== "" ||
    $password_baru !== "" ||
    $konfirmasi_password !== ""
);

if ($ingin_ganti_password) {

    if ($password_lama == "" || $password_baru == "" || $konfirmasi_password == "") {
        $_SESSION['error'] = "Untuk mengganti password, semua field password wajib diisi.";
        header("Location: ../user/edit_profil.php");
        exit;
    }

    if (!password_verify($password_lama, $user['password'])) {
        $_SESSION['error'] = "Password lama tidak sesuai.";
        header("Location: ../user/edit_profil.php");
        exit;
    }

    if (strlen($password_baru) < 8) {
        $_SESSION['error'] = "Password baru minimal 8 karakter.";
        header("Location: ../user/edit_profil.php");
        exit;
    }

    if ($password_baru !== $konfirmasi_password) {
        $_SESSION['error'] = "Konfirmasi password baru tidak sesuai.";
        header("Location: ../user/edit_profil.php");
        exit;
    }

    $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);

    $stmt_update = mysqli_prepare($conn, "
        UPDATE data_pengguna SET
            nama = ?,
            no_hp = ?,
            password = ?
        WHERE id_user = ?
    ");

    mysqli_stmt_bind_param(
        $stmt_update,
        "sssi",
        $nama,
        $no_hp,
        $password_hash,
        $id_user
    );

} else {

    $stmt_update = mysqli_prepare($conn, "
        UPDATE data_pengguna SET
            nama = ?,
            no_hp = ?
        WHERE id_user = ?
    ");

    mysqli_stmt_bind_param(
        $stmt_update,
        "ssi",
        $nama,
        $no_hp,
        $id_user
    );

}

if (mysqli_stmt_execute($stmt_update)) {
    $_SESSION['nama'] = $nama;
    $_SESSION['success'] = "Profil berhasil diperbarui.";
    header("Location: ../user/profil.php");
    exit;
} else {
    $_SESSION['error'] = "Profil gagal diperbarui.";
    header("Location: ../user/edit_profil.php");
    exit;
}

?>