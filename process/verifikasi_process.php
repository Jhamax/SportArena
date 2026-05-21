<?php

require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_POST['id_pembayaran']) || !isset($_POST['aksi'])) {
    header("Location: ../admin/verifikasi_pembayaran.php");
    exit;
}

$id_pembayaran = (int) $_POST['id_pembayaran'];
$aksi = $_POST['aksi'];

if (!in_array($aksi, ['terima', 'tolak'])) {
    $_SESSION['error'] = "Aksi verifikasi tidak valid.";
    header("Location: ../admin/verifikasi_pembayaran.php");
    exit;
}

/* Ambil data pembayaran + booking */
$stmt = mysqli_prepare($conn, "
    SELECT 
        pay.id_pembayaran,
        pay.status_pembayaran,
        pay.bukti_pembayaran,

        b.id_booking,
        b.kode_booking,
        b.id_user,
        b.status_booking
    FROM data_pembayaran pay
    JOIN data_booking b ON pay.id_booking = b.id_booking
    WHERE pay.id_pembayaran = ?
    LIMIT 1
");

mysqli_stmt_bind_param($stmt, "i", $id_pembayaran);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    $_SESSION['error'] = "Data pembayaran tidak ditemukan.";
    header("Location: ../admin/verifikasi_pembayaran.php");
    exit;
}

if ($data['status_pembayaran'] !== 'Menunggu Verifikasi') {
    $_SESSION['error'] = "Pembayaran ini sudah diproses sebelumnya.";
    header("Location: ../admin/verifikasi_pembayaran.php?id=" . $id_pembayaran);
    exit;
}

if (empty($data['bukti_pembayaran'])) {
    $_SESSION['error'] = "Bukti pembayaran belum tersedia.";
    header("Location: ../admin/verifikasi_pembayaran.php?id=" . $id_pembayaran);
    exit;
}

/* =========================
   TERIMA PEMBAYARAN
========================= */
if ($aksi === 'terima') {

    $status_pembayaran = "Berhasil";
    $status_booking = "Terkonfirmasi";

    $stmt_update_payment = mysqli_prepare($conn, "
        UPDATE data_pembayaran SET
            status_pembayaran = ?,
            catatan_admin = NULL
        WHERE id_pembayaran = ?
    ");

    mysqli_stmt_bind_param(
        $stmt_update_payment,
        "si",
        $status_pembayaran,
        $id_pembayaran
    );

    $update_payment = mysqli_stmt_execute($stmt_update_payment);

    $stmt_update_booking = mysqli_prepare($conn, "
        UPDATE data_booking SET
            status_booking = ?
        WHERE id_booking = ?
    ");

    mysqli_stmt_bind_param(
        $stmt_update_booking,
        "si",
        $status_booking,
        $data['id_booking']
    );

    $update_booking = mysqli_stmt_execute($stmt_update_booking);

    $judul = "Pembayaran Diterima";
    $pesan = "Pembayaran untuk booking {$data['kode_booking']} telah diterima. Booking kamu sudah terkonfirmasi.";

    $stmt_notif = mysqli_prepare($conn, "
        INSERT INTO data_notifikasi
        (id_user, id_booking, judul, pesan)
        VALUES (?, ?, ?, ?)
    ");

    mysqli_stmt_bind_param(
        $stmt_notif,
        "iiss",
        $data['id_user'],
        $data['id_booking'],
        $judul,
        $pesan
    );

    mysqli_stmt_execute($stmt_notif);

    if ($update_payment && $update_booking) {
        $_SESSION['success'] = "Pembayaran berhasil diterima. Booking sudah terkonfirmasi.";
    } else {
        $_SESSION['error'] = "Gagal memverifikasi pembayaran.";
    }

    header("Location: ../admin/verifikasi_pembayaran.php");
    exit;
}

/* =========================
   TOLAK PEMBAYARAN
   Catatan:
   Booking tidak dibuat final Ditolak.
   User boleh upload ulang bukti.
========================= */
if ($aksi === 'tolak') {

    $catatan_admin = trim($_POST['catatan_admin'] ?? '');

    if ($catatan_admin == "") {
        $_SESSION['error'] = "Catatan penolakan wajib diisi.";
        header("Location: ../admin/verifikasi_pembayaran.php?id=" . $id_pembayaran);
        exit;
    }

    $status_pembayaran = "Ditolak";
    $status_booking = "Ditolak";

    $stmt_update_payment = mysqli_prepare($conn, "
        UPDATE data_pembayaran SET
            status_pembayaran = ?,
            catatan_admin = ?
        WHERE id_pembayaran = ?
    ");

    mysqli_stmt_bind_param(
        $stmt_update_payment,
        "ssi",
        $status_pembayaran,
        $catatan_admin,
        $id_pembayaran
    );

    $update_payment = mysqli_stmt_execute($stmt_update_payment);

    $stmt_update_booking = mysqli_prepare($conn, "
        UPDATE data_booking SET
            status_booking = ?
        WHERE id_booking = ?
    ");

    mysqli_stmt_bind_param(
        $stmt_update_booking,
        "si",
        $status_booking,
        $data['id_booking']
    );

    $update_booking = mysqli_stmt_execute($stmt_update_booking);

    $judul = "Pembayaran Ditolak";
    $pesan = "Pembayaran untuk booking {$data['kode_booking']} ditolak. Catatan admin: {$catatan_admin}.";

    $stmt_notif = mysqli_prepare($conn, "
        INSERT INTO data_notifikasi
        (id_user, id_booking, judul, pesan)
        VALUES (?, ?, ?, ?)
    ");

    mysqli_stmt_bind_param(
        $stmt_notif,
        "iiss",
        $data['id_user'],
        $data['id_booking'],
        $judul,
        $pesan
    );

    mysqli_stmt_execute($stmt_notif);

    if ($update_payment && $update_booking) {
        $_SESSION['success'] = "Pembayaran berhasil ditolak. User dapat mengupload ulang bukti pembayaran.";
    } else {
        $_SESSION['error'] = "Gagal menolak pembayaran.";
    }

    header("Location: ../admin/verifikasi_pembayaran.php");
    exit;
}

header("Location: ../admin/verifikasi_pembayaran.php");
exit;
