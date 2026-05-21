<?php

require_once __DIR__ . '/../includes/auth_user.php';
require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_POST['kode_booking'])) {
    $_SESSION['error'] = "Kode booking tidak valid.";
    header("Location: ../user/riwayat_booking.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$kode_booking = trim($_POST['kode_booking']);

if ($kode_booking == "") {
    $_SESSION['error'] = "Kode booking tidak boleh kosong.";
    header("Location: ../user/riwayat_booking.php");
    exit;
}

/* Ambil data booking */
$stmt = mysqli_prepare($conn, "
    SELECT 
        b.id_booking,
        b.kode_booking,
        b.id_user,
        b.status_booking,
        p.status_pembayaran
    FROM data_booking b
    LEFT JOIN data_pembayaran p ON b.id_booking = p.id_booking
    WHERE b.kode_booking = ?
    AND b.id_user = ?
    LIMIT 1
");

mysqli_stmt_bind_param($stmt, "si", $kode_booking, $id_user);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$booking = mysqli_fetch_assoc($result);

if (!$booking) {
    $_SESSION['error'] = "Data booking tidak ditemukan.";
    header("Location: ../user/riwayat_booking.php");
    exit;
}

/*
    Booking hanya boleh dibatalkan jika:
    - Belum upload pembayaran
    - Status booking masih Menunggu Pembayaran
*/
if ($booking['status_booking'] !== 'Menunggu Pembayaran') {
    $_SESSION['error'] = "Booking tidak bisa dibatalkan karena status booking sudah " . $booking['status_booking'] . ".";
    header("Location: ../user/riwayat_booking.php");
    exit;
}

if (($booking['status_pembayaran'] ?? 'Belum Dibayar') !== 'Belum Dibayar') {
    $_SESSION['error'] = "Booking tidak bisa dibatalkan karena pembayaran sudah diproses.";
    header("Location: ../user/riwayat_booking.php");
    exit;
}

/* Update status booking */
$status_booking = "Dibatalkan";

$stmt_update = mysqli_prepare($conn, "
    UPDATE data_booking
    SET status_booking = ?
    WHERE id_booking = ?
    AND id_user = ?
");

mysqli_stmt_bind_param(
    $stmt_update,
    "sii",
    $status_booking,
    $booking['id_booking'],
    $id_user
);

$update_booking = mysqli_stmt_execute($stmt_update);

/* Tambah notifikasi user */
$judul = "Booking Dibatalkan";
$pesan = "Booking dengan kode {$booking['kode_booking']} berhasil dibatalkan.";

$stmt_notif = mysqli_prepare($conn, "
    INSERT INTO data_notifikasi
    (id_user, id_booking, judul, pesan)
    VALUES (?, ?, ?, ?)
");

mysqli_stmt_bind_param(
    $stmt_notif,
    "iiss",
    $id_user,
    $booking['id_booking'],
    $judul,
    $pesan
);

mysqli_stmt_execute($stmt_notif);

if ($update_booking) {
    $_SESSION['success'] = "Booking berhasil dibatalkan.";
} else {
    $_SESSION['error'] = "Booking gagal dibatalkan.";
}

header("Location: ../user/riwayat_booking.php");
exit;

?>