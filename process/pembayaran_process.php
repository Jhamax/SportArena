<?php

require_once __DIR__ . '/../includes/auth_user.php';
require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_POST['upload_pembayaran'])) {
    header("Location: ../user/riwayat_booking.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$kode_booking = trim($_POST['kode_booking'] ?? '');

if ($kode_booking == "") {
    $_SESSION['error'] = "Kode booking tidak valid.";
    header("Location: ../user/riwayat_booking.php");
    exit;
}

/* Ambil data booking */
$stmt = mysqli_prepare($conn, "
    SELECT 
        b.id_booking,
        b.kode_booking,
        b.status_booking,

        p.id_pembayaran,
        p.bukti_pembayaran,
        p.status_pembayaran
    FROM data_booking b
    JOIN data_pembayaran p ON b.id_booking = p.id_booking
    WHERE b.kode_booking = ?
    AND b.id_user = ?
    LIMIT 1
");

mysqli_stmt_bind_param($stmt, "si", $kode_booking, $id_user);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    $_SESSION['error'] = "Data booking tidak ditemukan.";
    header("Location: ../user/riwayat_booking.php");
    exit;
}

/*
    Upload bukti hanya boleh kalau booking masih Menunggu Pembayaran.
    Jadi kalau booking sudah Ditolak/Dibatalkan/Terkonfirmasi, upload ditolak.
*/
if ($data['status_booking'] !== 'Menunggu Pembayaran') {
    $_SESSION['error'] = "Booking ini tidak dapat mengupload bukti pembayaran karena status booking sudah " . $data['status_booking'] . ".";
    header("Location: ../user/riwayat_booking.php");
    exit;
}

if ($data['status_pembayaran'] !== 'Belum Dibayar') {
    $_SESSION['error'] = "Bukti pembayaran tidak dapat diupload karena status pembayaran sudah " . $data['status_pembayaran'] . ".";
    header("Location: ../user/riwayat_booking.php");
    exit;
}

/* Validasi file */
if (!isset($_FILES['bukti_pembayaran']) || $_FILES['bukti_pembayaran']['error'] === UPLOAD_ERR_NO_FILE) {
    $_SESSION['error'] = "Bukti pembayaran wajib diupload.";
    header("Location: ../user/upload_pembayaran.php?kode=" . urlencode($kode_booking));
    exit;
}

$file = $_FILES['bukti_pembayaran'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error'] = "Upload bukti pembayaran gagal.";
    header("Location: ../user/upload_pembayaran.php?kode=" . urlencode($kode_booking));
    exit;
}

$max_size = 3 * 1024 * 1024;

if ($file['size'] > $max_size) {
    $_SESSION['error'] = "Ukuran file maksimal 3MB.";
    header("Location: ../user/upload_pembayaran.php?kode=" . urlencode($kode_booking));
    exit;
}

$allowed_ext = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
$file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($file_ext, $allowed_ext)) {
    $_SESSION['error'] = "Format file harus JPG, JPEG, PNG, WEBP, atau PDF.";
    header("Location: ../user/upload_pembayaran.php?kode=" . urlencode($kode_booking));
    exit;
}

$upload_dir = __DIR__ . '/../uploads/pembayaran/';

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$new_filename = 'bukti_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
$destination = $upload_dir . $new_filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    $_SESSION['error'] = "Gagal menyimpan bukti pembayaran.";
    header("Location: ../user/upload_pembayaran.php?kode=" . urlencode($kode_booking));
    exit;
}

$file_path = 'uploads/pembayaran/' . $new_filename;

/* Hapus bukti lama kalau ada */
if (!empty($data['bukti_pembayaran'])) {
    $old_file = __DIR__ . '/../' . $data['bukti_pembayaran'];

    if (file_exists($old_file)) {
        unlink($old_file);
    }
}

/* Update pembayaran */
$status_pembayaran = "Menunggu Verifikasi";
$tanggal_pembayaran = date('Y-m-d');

$stmt_update_payment = mysqli_prepare($conn, "
    UPDATE data_pembayaran SET
        bukti_pembayaran = ?,
        tanggal_pembayaran = ?,
        status_pembayaran = ?,
        catatan_admin = NULL
    WHERE id_pembayaran = ?
");

mysqli_stmt_bind_param(
    $stmt_update_payment,
    "sssi",
    $file_path,
    $tanggal_pembayaran,
    $status_pembayaran,
    $data['id_pembayaran']
);

$update_payment = mysqli_stmt_execute($stmt_update_payment);

/* Update status booking */
$status_booking = "Menunggu Verifikasi";

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

/* Tambah notifikasi user */
$judul = "Bukti Pembayaran Dikirim";
$pesan = "Bukti pembayaran untuk booking $kode_booking berhasil diupload dan sedang menunggu verifikasi admin.";

$stmt_notif = mysqli_prepare($conn, "
    INSERT INTO data_notifikasi
    (id_user, id_booking, judul, pesan)
    VALUES (?, ?, ?, ?)
");

mysqli_stmt_bind_param(
    $stmt_notif,
    "iiss",
    $id_user,
    $data['id_booking'],
    $judul,
    $pesan
);

mysqli_stmt_execute($stmt_notif);

if ($update_payment && $update_booking) {
    $_SESSION['success'] = "Bukti pembayaran berhasil diupload. Menunggu verifikasi admin.";
} else {
    $_SESSION['error'] = "Bukti pembayaran gagal diproses.";
}

header("Location: ../user/konfirmasi_booking.php?kode=" . urlencode($kode_booking));
exit;

?>