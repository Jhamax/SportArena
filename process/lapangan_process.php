<?php

require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_POST['aksi'])) {
    header("Location: ../admin/lapangan.php");
    exit;
}

$aksi = $_POST['aksi'];

function uploadFotoLapangan($input_name)
{
    if (!isset($_FILES[$input_name]) || $_FILES[$input_name]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$input_name];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = "Upload foto gagal.";
        header("Location: ../admin/lapangan.php");
        exit;
    }

    $max_size = 2 * 1024 * 1024;

    if ($file['size'] > $max_size) {
        $_SESSION['error'] = "Ukuran foto maksimal 2MB.";
        header("Location: ../admin/lapangan.php");
        exit;
    }

    $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($file_ext, $allowed_ext)) {
        $_SESSION['error'] = "Format foto harus JPG, JPEG, PNG, atau WEBP.";
        header("Location: ../admin/lapangan.php");
        exit;
    }

    $upload_dir = __DIR__ . '/../uploads/lapangan/';

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $new_filename = 'lapangan_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
    $destination = $upload_dir . $new_filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        $_SESSION['error'] = "Gagal menyimpan foto.";
        header("Location: ../admin/lapangan.php");
        exit;
    }

    return 'uploads/lapangan/' . $new_filename;
}

/* TAMBAH LAPANGAN */
if ($aksi === 'tambah') {

    $nama_lapangan = trim($_POST['nama_lapangan']);
    $jenis_olahraga = trim($_POST['jenis_olahraga']);
    $harga_per_jam = trim($_POST['harga_per_jam']);
    $fasilitas = trim($_POST['fasilitas']);
    $deskripsi = trim($_POST['deskripsi']);
    $status_ketersediaan = trim($_POST['status_ketersediaan']);

    if ($nama_lapangan == "" || $jenis_olahraga == "" || $harga_per_jam == "" || $status_ketersediaan == "") {
        $_SESSION['error'] = "Nama lapangan, jenis olahraga, harga, dan status wajib diisi.";
        header("Location: ../admin/tambah_lapangan.php");
        exit;
    }

    if ($harga_per_jam < 0) {
        $_SESSION['error'] = "Harga tidak boleh kurang dari 0.";
        header("Location: ../admin/tambah_lapangan.php");
        exit;
    }

    $foto = uploadFotoLapangan('foto');

    $stmt = mysqli_prepare($conn, "
        INSERT INTO data_lapangan 
        (nama_lapangan, jenis_olahraga, harga_per_jam, fasilitas, deskripsi, foto, status_ketersediaan)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    mysqli_stmt_bind_param(
        $stmt,
        "ssdssss",
        $nama_lapangan,
        $jenis_olahraga,
        $harga_per_jam,
        $fasilitas,
        $deskripsi,
        $foto,
        $status_ketersediaan
    );

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Data lapangan berhasil ditambahkan.";
    } else {
        $_SESSION['error'] = "Gagal menambahkan data lapangan.";
    }

    header("Location: ../admin/lapangan.php");
    exit;
}

/* EDIT LAPANGAN */
if ($aksi === 'edit') {

    $id_lapangan = (int) $_POST['id_lapangan'];
    $nama_lapangan = trim($_POST['nama_lapangan']);
    $jenis_olahraga = trim($_POST['jenis_olahraga']);
    $harga_per_jam = trim($_POST['harga_per_jam']);
    $fasilitas = trim($_POST['fasilitas']);
    $deskripsi = trim($_POST['deskripsi']);
    $status_ketersediaan = trim($_POST['status_ketersediaan']);
    $foto_lama = trim($_POST['foto_lama']);

    if ($nama_lapangan == "" || $jenis_olahraga == "" || $harga_per_jam == "" || $status_ketersediaan == "") {
        $_SESSION['error'] = "Nama lapangan, jenis olahraga, harga, dan status wajib diisi.";
        header("Location: ../admin/edit_lapangan.php?id=" . $id_lapangan);
        exit;
    }

    $foto_baru = uploadFotoLapangan('foto');
    $foto = $foto_lama;

    if ($foto_baru !== null) {
        $foto = $foto_baru;

        if (!empty($foto_lama)) {
            $path_foto_lama = __DIR__ . '/../' . $foto_lama;

            if (file_exists($path_foto_lama)) {
                unlink($path_foto_lama);
            }
        }
    }

    $stmt = mysqli_prepare($conn, "
        UPDATE data_lapangan SET
            nama_lapangan = ?,
            jenis_olahraga = ?,
            harga_per_jam = ?,
            fasilitas = ?,
            deskripsi = ?,
            foto = ?,
            status_ketersediaan = ?
        WHERE id_lapangan = ?
    ");

    mysqli_stmt_bind_param(
        $stmt,
        "ssdssssi",
        $nama_lapangan,
        $jenis_olahraga,
        $harga_per_jam,
        $fasilitas,
        $deskripsi,
        $foto,
        $status_ketersediaan,
        $id_lapangan
    );

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Data lapangan berhasil diperbarui.";
    } else {
        $_SESSION['error'] = "Gagal memperbarui data lapangan.";
    }

    header("Location: ../admin/lapangan.php");
    exit;
}

/* HAPUS LAPANGAN */
if ($aksi === 'hapus') {

    $id_lapangan = (int) $_POST['id_lapangan'];

    $cek_booking = mysqli_prepare($conn, "SELECT COUNT(*) FROM data_booking WHERE id_lapangan = ?");
    mysqli_stmt_bind_param($cek_booking, "i", $id_lapangan);
    mysqli_stmt_execute($cek_booking);
    mysqli_stmt_bind_result($cek_booking, $jumlah_booking);
    mysqli_stmt_fetch($cek_booking);
    mysqli_stmt_close($cek_booking);

    if ($jumlah_booking > 0) {
        $_SESSION['error'] = "Lapangan tidak bisa dihapus karena sudah memiliki data booking. Ubah status menjadi tidak tersedia saja.";
        header("Location: ../admin/lapangan.php");
        exit;
    }

    $stmt_foto = mysqli_prepare($conn, "SELECT foto FROM data_lapangan WHERE id_lapangan = ?");
    mysqli_stmt_bind_param($stmt_foto, "i", $id_lapangan);
    mysqli_stmt_execute($stmt_foto);
    mysqli_stmt_bind_result($stmt_foto, $foto);
    mysqli_stmt_fetch($stmt_foto);
    mysqli_stmt_close($stmt_foto);

    $stmt = mysqli_prepare($conn, "DELETE FROM data_lapangan WHERE id_lapangan = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_lapangan);

    if (mysqli_stmt_execute($stmt)) {

        if (!empty($foto)) {
            $path_foto = __DIR__ . '/../' . $foto;

            if (file_exists($path_foto)) {
                unlink($path_foto);
            }
        }

        $_SESSION['success'] = "Data lapangan berhasil dihapus.";
    } else {
        $_SESSION['error'] = "Gagal menghapus data lapangan.";
    }

    header("Location: ../admin/lapangan.php");
    exit;
}

header("Location: ../admin/lapangan.php");
exit;

?>