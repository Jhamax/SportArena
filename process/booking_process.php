<?php

require_once __DIR__ . '/../includes/auth_user.php';
require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_POST['booking'])) {
    header("Location: ../user/lapangan.php");
    exit;
}

$id_user = $_SESSION['id_user'];

$id_lapangan = (int) ($_POST['id_lapangan'] ?? 0);
$tanggal_booking = trim($_POST['tanggal_booking'] ?? '');
$jam_mulai = trim($_POST['jam_mulai'] ?? '');
$durasi = (int) ($_POST['durasi'] ?? 0);

/* =========================
   VALIDASI INPUT
========================= */
if ($id_lapangan <= 0 || $tanggal_booking == "" || $jam_mulai == "" || $durasi <= 0) {
    $_SESSION['error'] = "Data booking tidak lengkap.";
    header("Location: ../user/lapangan.php");
    exit;
}

if (!in_array($durasi, [1, 2, 3, 4])) {
    $_SESSION['error'] = "Durasi booking tidak valid.";
    header("Location: ../user/booking.php?id=" . $id_lapangan);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_booking)) {
    $_SESSION['error'] = "Format tanggal booking tidak valid.";
    header("Location: ../user/booking.php?id=" . $id_lapangan);
    exit;
}

if ($tanggal_booking < date('Y-m-d')) {
    $_SESSION['error'] = "Tanggal booking tidak boleh kurang dari hari ini.";
    header("Location: ../user/booking.php?id=" . $id_lapangan);
    exit;
}

$jam_mulai_full = strlen($jam_mulai) === 5 ? $jam_mulai . ":00" : $jam_mulai;

if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $jam_mulai_full)) {
    $_SESSION['error'] = "Format jam mulai tidak valid.";
    header("Location: ../user/booking.php?id=" . $id_lapangan);
    exit;
}

$datetime_mulai = $tanggal_booking . " " . $jam_mulai_full;
$timestamp_mulai = strtotime($datetime_mulai);
$timestamp_selesai = strtotime($datetime_mulai . " +{$durasi} hour");
$jam_selesai = date('H:i:s', $timestamp_selesai);

if (!$timestamp_mulai || !$timestamp_selesai) {
    $_SESSION['error'] = "Tanggal atau jam booking tidak valid.";
    header("Location: ../user/booking.php?id=" . $id_lapangan);
    exit;
}

/*
    Cegah booking pada jam yang sudah lewat atau sedang berlangsung.
    Contoh:
    Sekarang 10:05
    User tidak boleh booking jam 10:00.
*/
if ($timestamp_mulai <= time()) {
    $_SESSION['error'] = "Jam booking sudah lewat atau sedang berlangsung. Silakan pilih jadwal lain.";
    header("Location: ../user/booking.php?id=" . $id_lapangan);
    exit;
}

if ($jam_selesai <= $jam_mulai_full) {
    $_SESSION['error'] = "Jam booking tidak valid.";
    header("Location: ../user/booking.php?id=" . $id_lapangan);
    exit;
}

/* =========================
   AMBIL DATA LAPANGAN
========================= */
$stmt_lapangan = mysqli_prepare($conn, "
    SELECT 
        id_lapangan,
        nama_lapangan,
        jenis_olahraga,
        harga_per_jam,
        status_ketersediaan
    FROM data_lapangan
    WHERE id_lapangan = ?
    LIMIT 1
");

mysqli_stmt_bind_param($stmt_lapangan, "i", $id_lapangan);
mysqli_stmt_execute($stmt_lapangan);
$result_lapangan = mysqli_stmt_get_result($stmt_lapangan);
$lapangan = mysqli_fetch_assoc($result_lapangan);
mysqli_stmt_close($stmt_lapangan);

if (!$lapangan) {
    $_SESSION['error'] = "Data lapangan tidak ditemukan.";
    header("Location: ../user/lapangan.php");
    exit;
}

if ($lapangan['status_ketersediaan'] !== 'tersedia') {
    $_SESSION['error'] = "Lapangan sedang tidak tersedia.";
    header("Location: ../user/detail_lapangan.php?id=" . $id_lapangan);
    exit;
}

$total_biaya = $durasi * $lapangan['harga_per_jam'];

/* =========================
   CEK JADWAL TERSEDIA
   Jika user booking 2 jam,
   sistem mengecek slot per jam.
========================= */
$id_jadwal = null;

for ($i = 0; $i < $durasi; $i++) {

    $segmen_mulai_timestamp = strtotime($datetime_mulai . " +{$i} hour");
    $segmen_selesai_timestamp = strtotime($datetime_mulai . " +" . ($i + 1) . " hour");

    $segmen_mulai = date('H:i:s', $segmen_mulai_timestamp);
    $segmen_selesai = date('H:i:s', $segmen_selesai_timestamp);

    $stmt_jadwal = mysqli_prepare($conn, "
        SELECT id_jadwal
        FROM data_jadwal_lapangan
        WHERE id_lapangan = ?
        AND tanggal = ?
        AND status_jadwal = 'tersedia'
        AND jam_mulai <= ?
        AND jam_selesai >= ?
        ORDER BY jam_mulai ASC
        LIMIT 1
    ");

    mysqli_stmt_bind_param(
        $stmt_jadwal,
        "isss",
        $id_lapangan,
        $tanggal_booking,
        $segmen_mulai,
        $segmen_selesai
    );

    mysqli_stmt_execute($stmt_jadwal);
    $result_jadwal = mysqli_stmt_get_result($stmt_jadwal);
    $jadwal = mysqli_fetch_assoc($result_jadwal);
    mysqli_stmt_close($stmt_jadwal);

    if (!$jadwal) {
        $_SESSION['error'] = "Jadwal lapangan tidak tersedia lengkap untuk durasi yang dipilih.";
        header("Location: ../user/booking.php?id=" . $id_lapangan);
        exit;
    }

    /*
        Untuk kolom id_jadwal di data_booking,
        cukup simpan id_jadwal pertama.
        Pengecekan durasi tetap dilakukan per jam di atas.
    */
    if ($id_jadwal === null) {
        $id_jadwal = (int) $jadwal['id_jadwal'];
    }
}

/* =========================
   CEK JADWAL TIDAK TERSEDIA
========================= */
$stmt_jadwal_tidak = mysqli_prepare($conn, "
    SELECT COUNT(*)
    FROM data_jadwal_lapangan
    WHERE id_lapangan = ?
    AND tanggal = ?
    AND status_jadwal = 'tidak tersedia'
    AND jam_mulai < ?
    AND jam_selesai > ?
");

mysqli_stmt_bind_param(
    $stmt_jadwal_tidak,
    "isss",
    $id_lapangan,
    $tanggal_booking,
    $jam_selesai,
    $jam_mulai_full
);

mysqli_stmt_execute($stmt_jadwal_tidak);

$jumlah_jadwal_tidak = 0;
mysqli_stmt_bind_result($stmt_jadwal_tidak, $jumlah_jadwal_tidak);
mysqli_stmt_fetch($stmt_jadwal_tidak);
mysqli_stmt_close($stmt_jadwal_tidak);

if ($jumlah_jadwal_tidak > 0) {
    $_SESSION['error'] = "Slot waktu tersebut sedang tidak tersedia.";
    header("Location: ../user/booking.php?id=" . $id_lapangan);
    exit;
}

/* =========================
   CEK DOUBLE BOOKING
========================= */
$stmt_cek_booking = mysqli_prepare($conn, "
    SELECT COUNT(*)
    FROM data_booking
    WHERE id_lapangan = ?
    AND tanggal_booking = ?
    AND status_booking IN ('Menunggu Pembayaran', 'Menunggu Verifikasi', 'Terkonfirmasi')
    AND jam_mulai < ?
    AND jam_selesai > ?
");

mysqli_stmt_bind_param(
    $stmt_cek_booking,
    "isss",
    $id_lapangan,
    $tanggal_booking,
    $jam_selesai,
    $jam_mulai_full
);

mysqli_stmt_execute($stmt_cek_booking);

$jumlah_bentrok = 0;
mysqli_stmt_bind_result($stmt_cek_booking, $jumlah_bentrok);
mysqli_stmt_fetch($stmt_cek_booking);
mysqli_stmt_close($stmt_cek_booking);

if ($jumlah_bentrok > 0) {
    $_SESSION['error'] = "Slot waktu sudah dibooking. Silakan pilih jam lain.";
    header("Location: ../user/booking.php?id=" . $id_lapangan);
    exit;
}

/* =========================
   SIMPAN BOOKING
========================= */
$kode_booking = "BK-" . date('Ymd') . "-" . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
$status_booking = "Menunggu Pembayaran";

/* Batas waktu pembayaran: 1 jam setelah booking dibuat */
$batas_waktu_pembayaran = date('Y-m-d H:i:s', strtotime('+1 hour'));

mysqli_begin_transaction($conn);

try {

    $stmt_booking = mysqli_prepare($conn, "
        INSERT INTO data_booking
        (
            kode_booking,
            id_user,
            id_lapangan,
            id_jadwal,
            tanggal_booking,
            jam_mulai,
            jam_selesai,
            durasi,
            total_biaya,
            status_booking,
            batas_waktu_pembayaran
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    mysqli_stmt_bind_param(
        $stmt_booking,
        "siiisssidss",
        $kode_booking,
        $id_user,
        $id_lapangan,
        $id_jadwal,
        $tanggal_booking,
        $jam_mulai_full,
        $jam_selesai,
        $durasi,
        $total_biaya,
        $status_booking,
        $batas_waktu_pembayaran
    );

    if (!mysqli_stmt_execute($stmt_booking)) {
        throw new Exception("Booking gagal disimpan.");
    }

    $id_booking = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt_booking);

    /* Buat data pembayaran awal */
    $metode_pembayaran = "Transfer Bank";
    $status_pembayaran = "Belum Dibayar";

    $stmt_pembayaran = mysqli_prepare($conn, "
        INSERT INTO data_pembayaran
        (
            id_booking,
            jumlah_pembayaran,
            metode_pembayaran,
            status_pembayaran
        )
        VALUES (?, ?, ?, ?)
    ");

    mysqli_stmt_bind_param(
        $stmt_pembayaran,
        "idss",
        $id_booking,
        $total_biaya,
        $metode_pembayaran,
        $status_pembayaran
    );

    if (!mysqli_stmt_execute($stmt_pembayaran)) {
        throw new Exception("Data pembayaran gagal dibuat.");
    }

    mysqli_stmt_close($stmt_pembayaran);

    /* Tambah notifikasi */
    $judul = "Booking Berhasil Dibuat";
    $pesan = "Booking dengan kode {$kode_booking} berhasil dibuat. Silakan lakukan pembayaran sebelum batas waktu pembayaran.";

    $stmt_notif = mysqli_prepare($conn, "
        INSERT INTO data_notifikasi
        (id_user, id_booking, judul, pesan)
        VALUES (?, ?, ?, ?)
    ");

    mysqli_stmt_bind_param(
        $stmt_notif,
        "iiss",
        $id_user,
        $id_booking,
        $judul,
        $pesan
    );

    if (!mysqli_stmt_execute($stmt_notif)) {
        throw new Exception("Notifikasi booking gagal dibuat.");
    }

    mysqli_stmt_close($stmt_notif);

    mysqli_commit($conn);

    $_SESSION['success'] = "Booking berhasil dibuat. Silakan lakukan pembayaran sebelum batas waktu.";
    header("Location: ../user/konfirmasi_booking.php?kode=" . urlencode($kode_booking));
    exit;

} catch (Exception $e) {

    mysqli_rollback($conn);

    $_SESSION['error'] = $e->getMessage();
    header("Location: ../user/booking.php?id=" . $id_lapangan);
    exit;
}

?>