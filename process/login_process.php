<?php

session_start();
require_once __DIR__ . '/../config/koneksi.php';

if (!isset($conn)) {
    die("Koneksi database tidak ditemukan.");
}

$email = trim($_POST['email']);
$password = $_POST['password'];

if ($email == "" || $password == "") {
    $_SESSION['error'] = "Email dan password wajib diisi.";
    header("Location: ../login.php");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Format email tidak valid.";
    header("Location: ../login.php");
    exit;
}

$query = mysqli_prepare($conn, "SELECT id_user, nama, email, password, role, status_akun 
                                FROM data_pengguna 
                                WHERE email = ? 
                                LIMIT 1");

mysqli_stmt_bind_param($query, "s", $email);
mysqli_stmt_execute($query);
mysqli_stmt_store_result($query);

if (mysqli_stmt_num_rows($query) === 0) {
    $_SESSION['error'] = "Email belum terdaftar.";
    header("Location: ../login.php");
    exit;
}

$id_user = 0;
$nama = "";
$email_db = "";
$password_hash = "";
$role = "";
$status_akun = "";

mysqli_stmt_bind_result(
    $query,
    $id_user,
    $nama,
    $email_db,
    $password_hash,
    $role,
    $status_akun
);

mysqli_stmt_fetch($query);

if ($status_akun !== 'aktif') {
    $_SESSION['error'] = "Akun kamu sedang nonaktif. Silakan hubungi admin.";
    header("Location: ../login.php");
    exit;
}

if (!password_verify($password, $password_hash)) {
    $_SESSION['error'] = "Password salah.";
    header("Location: ../login.php");
    exit;
}

session_regenerate_id(true);

$_SESSION['login'] = true;
$_SESSION['id_user'] = $id_user;
$_SESSION['nama'] = $nama;
$_SESSION['email'] = $email_db;
$_SESSION['role'] = $role;

if ($role === 'admin') {
    header("Location: ../admin/dashboard.php");
    exit;
} else {
    header("Location: ../user/dashboard.php");
    exit;
}

?>