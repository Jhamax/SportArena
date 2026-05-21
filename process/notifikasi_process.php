<?php

require_once __DIR__ . '/../includes/auth_user.php';
require_once __DIR__ . '/../config/koneksi.php';

$id_user = $_SESSION['id_user'];

if (!isset($_POST['aksi'])) {
    header("Location: ../user/notifikasi.php");
    exit;
}

$aksi = $_POST['aksi'];

/* =========================
   TANDAI SATU DIBACA
========================= */
if ($aksi == 'baca_satu') {

    $id_notifikasi = (int) ($_POST['id_notifikasi'] ?? 0);

    if ($id_notifikasi <= 0) {
        $_SESSION['error'] = "Data notifikasi tidak valid.";
        header("Location: ../user/notifikasi.php");
        exit;
    }

    $status_baca = "dibaca";

    $stmt = mysqli_prepare($conn, "
        UPDATE data_notifikasi
        SET status_baca = ?
        WHERE id_notifikasi = ?
        AND id_user = ?
    ");

    mysqli_stmt_bind_param($stmt, "sii", $status_baca, $id_notifikasi, $id_user);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Notifikasi berhasil ditandai sebagai dibaca.";
    } else {
        $_SESSION['error'] = "Gagal menandai notifikasi.";
    }

    header("Location: ../user/notifikasi.php");
    exit;
}

/* =========================
   TANDAI SEMUA DIBACA
========================= */
if ($aksi == 'baca_semua') {

    $status_baca = "dibaca";

    $stmt = mysqli_prepare($conn, "
        UPDATE data_notifikasi
        SET status_baca = ?
        WHERE id_user = ?
        AND status_baca = 'belum dibaca'
    ");

    mysqli_stmt_bind_param($stmt, "si", $status_baca, $id_user);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Semua notifikasi berhasil ditandai sebagai dibaca.";
    } else {
        $_SESSION['error'] = "Gagal menandai semua notifikasi.";
    }

    header("Location: ../user/notifikasi.php");
    exit;
}

/* =========================
   HAPUS SATU NOTIFIKASI
========================= */
if ($aksi == 'hapus') {

    $id_notifikasi = (int) ($_POST['id_notifikasi'] ?? 0);

    if ($id_notifikasi <= 0) {
        $_SESSION['error'] = "Data notifikasi tidak valid.";
        header("Location: ../user/notifikasi.php");
        exit;
    }

    $stmt = mysqli_prepare($conn, "
        DELETE FROM data_notifikasi
        WHERE id_notifikasi = ?
        AND id_user = ?
    ");

    mysqli_stmt_bind_param($stmt, "ii", $id_notifikasi, $id_user);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Notifikasi berhasil dihapus.";
    } else {
        $_SESSION['error'] = "Gagal menghapus notifikasi.";
    }

    header("Location: ../user/notifikasi.php");
    exit;
}

/* =========================
   HAPUS SEMUA NOTIFIKASI
========================= */
if ($aksi == 'hapus_semua') {

    $stmt = mysqli_prepare($conn, "
        DELETE FROM data_notifikasi
        WHERE id_user = ?
    ");

    mysqli_stmt_bind_param($stmt, "i", $id_user);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Semua notifikasi berhasil dihapus.";
    } else {
        $_SESSION['error'] = "Gagal menghapus semua notifikasi.";
    }

    header("Location: ../user/notifikasi.php");
    exit;
}

$_SESSION['error'] = "Aksi tidak valid.";
header("Location: ../user/notifikasi.php");
exit;

?>