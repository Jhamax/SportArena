<?php

session_start();
require_once __DIR__ . '/../config/koneksi.php';

if (!isset($conn)) {
    die("Koneksi database tidak ditemukan.");
}

$nama = trim($_POST['nama']);
$email = trim($_POST['email']);
$no_hp = trim($_POST['no_hp']);
$password = $_POST['password'];
$konfirmasi_password = $_POST['konfirmasi_password'];

if ($nama == "" || $email == "" || $no_hp == "" || $password == "" || $konfirmasi_password == "") {
    $_SESSION['error'] = "Semua field wajib diisi.";
    header("Location: ../register.php");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Format email tidak valid.";
    header("Location: ../register.php");
    exit;
}

if (strlen($password) < 8) {
    $_SESSION['error'] = "Password minimal 8 karakter.";
    header("Location: ../register.php");
    exit;
}

if ($password !== $konfirmasi_password) {
    $_SESSION['error'] = "Konfirmasi password tidak sesuai.";
    header("Location: ../register.php");
    exit;
}

$cek_email = mysqli_prepare($conn, "SELECT id_user FROM data_pengguna WHERE email = ?");
mysqli_stmt_bind_param($cek_email, "s", $email);
mysqli_stmt_execute($cek_email);
mysqli_stmt_store_result($cek_email);

if (mysqli_stmt_num_rows($cek_email) > 0) {
    $_SESSION['error'] = "Email sudah terdaftar. Gunakan email lain.";
    header("Location: ../register.php");
    exit;
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);
$role = "user";
$status_akun = "aktif";

$query = mysqli_prepare($conn, "INSERT INTO data_pengguna 
    (nama, email, no_hp, password, role, status_akun) 
    VALUES (?, ?, ?, ?, ?, ?)
");

mysqli_stmt_bind_param(
    $query,
    "ssssss",
    $nama,
    $email,
    $no_hp,
    $password_hash,
    $role,
    $status_akun
);

if (mysqli_stmt_execute($query)) {
    $_SESSION['success'] = "Registrasi berhasil. Silakan login.";
    header("Location: ../login.php");
    exit;
} else {
    $_SESSION['error'] = "Registrasi gagal. Silakan coba lagi.";
    header("Location: ../register.php");
    exit;
}

?>