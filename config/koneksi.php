<?php

date_default_timezone_set('Asia/Jakarta');

$host = "localhost";
$user = "root";
$pass = "";
$db   = "project_akhir_rpl";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

/*
    Auto-update jadwal yang sudah masuk/lewat waktu
*/
require_once __DIR__ . '/../includes/auto_update_jadwal.php';
jalankanAutoUpdateJadwal($conn);

/*
    Auto-cancel booking yang melewati batas pembayaran
*/
require_once __DIR__ . '/../includes/auto_cancel.php';
jalankanAutoCancelBooking($conn);

/*
    Reminder booking terkonfirmasi
*/
require_once __DIR__ . '/../includes/reminder_booking.php';
jalankanReminderBooking($conn);

?>