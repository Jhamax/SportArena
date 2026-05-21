<?php

require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/koneksi.php';

function redirectWithMessage($type, $message, $url = "../admin/jadwal_lapangan.php")
{
    $_SESSION[$type] = $message;
    header("Location: " . $url);
    exit;
}

function cekLapanganAda($conn, $id_lapangan)
{
    $stmt = mysqli_prepare($conn, "
        SELECT COUNT(*)
        FROM data_lapangan
        WHERE id_lapangan = ?
    ");

    mysqli_stmt_bind_param($stmt, "i", $id_lapangan);
    mysqli_stmt_execute($stmt);

    $jumlah = 0;
    mysqli_stmt_bind_result($stmt, $jumlah);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    return $jumlah > 0;
}

function cekKonflikJadwal($conn, $id_lapangan, $tanggal, $jam_mulai, $jam_selesai, $exclude_id = 0)
{
    $stmt = mysqli_prepare($conn, "
        SELECT COUNT(*)
        FROM data_jadwal_lapangan
        WHERE id_lapangan = ?
        AND tanggal = ?
        AND id_jadwal != ?
        AND jam_mulai < ?
        AND jam_selesai > ?
    ");

    mysqli_stmt_bind_param(
        $stmt,
        "isiss",
        $id_lapangan,
        $tanggal,
        $exclude_id,
        $jam_selesai,
        $jam_mulai
    );

    mysqli_stmt_execute($stmt);

    $jumlah = 0;
    mysqli_stmt_bind_result($stmt, $jumlah);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    return $jumlah > 0;
}

function cekBookingBentrok($conn, $id_lapangan, $tanggal, $jam_mulai, $jam_selesai)
{
    $stmt = mysqli_prepare($conn, "
        SELECT COUNT(*)
        FROM data_booking
        WHERE id_lapangan = ?
        AND tanggal_booking = ?
        AND status_booking IN ('Menunggu Pembayaran', 'Menunggu Verifikasi', 'Terkonfirmasi')
        AND jam_mulai < ?
        AND jam_selesai > ?
    ");

    mysqli_stmt_bind_param(
        $stmt,
        "isss",
        $id_lapangan,
        $tanggal,
        $jam_selesai,
        $jam_mulai
    );

    mysqli_stmt_execute($stmt);

    $jumlah = 0;
    mysqli_stmt_bind_result($stmt, $jumlah);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    return $jumlah > 0;
}

if (!isset($_POST['aksi'])) {
    header("Location: ../admin/jadwal_lapangan.php");
    exit;
}

$aksi = $_POST['aksi'];

/* =========================
   HAPUS JADWAL MASSAL
========================= */
if (
    $aksi === 'hapus_jadwal_lewat' ||
    $aksi === 'hapus_jadwal_tidak_tersedia' ||
    $aksi === 'hapus_jadwal_tersedia' ||
    $aksi === 'hapus_semua_jadwal_filter'
) {
    $filter_id_lapangan = (int) ($_POST['filter_id_lapangan'] ?? 0);
    $filter_tanggal = trim($_POST['filter_tanggal'] ?? '');

    if ($filter_tanggal != '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_tanggal)) {
        $filter_tanggal = '';
    }

    $redirect_url = "../admin/jadwal_lapangan.php";

    $params = [];

    if ($filter_id_lapangan > 0) {
        $params['id_lapangan'] = $filter_id_lapangan;
    }

    if ($filter_tanggal != '') {
        $params['tanggal'] = $filter_tanggal;
    }

    if (!empty($params)) {
        $redirect_url .= "?" . http_build_query($params);
    }

    /*
        Selain hapus jadwal lewat, aksi massal lain wajib memakai filter.
        Ini supaya admin tidak tidak sengaja menghapus seluruh jadwal.
    */
    if (
        $aksi !== 'hapus_jadwal_lewat' &&
        $filter_id_lapangan <= 0 &&
        $filter_tanggal == ''
    ) {
        redirectWithMessage(
            "error",
            "Gunakan filter lapangan atau tanggal terlebih dahulu sebelum melakukan hapus massal.",
            $redirect_url
        );
    }

    $kondisi_aksi = "";

    if ($aksi === 'hapus_jadwal_lewat') {
        /*
            Hapus jadwal yang benar-benar sudah selesai.
            Contoh:
            Sekarang 13:00
            Jadwal 07:00 - 08:00 boleh dihapus.
        */
        $kondisi_aksi = "AND TIMESTAMP(j.tanggal, j.jam_selesai) < NOW()";
    } elseif ($aksi === 'hapus_jadwal_tidak_tersedia') {
        $kondisi_aksi = "AND j.status_jadwal = 'tidak tersedia'";
    } elseif ($aksi === 'hapus_jadwal_tersedia') {
        $kondisi_aksi = "AND j.status_jadwal = 'tersedia'";
    } elseif ($aksi === 'hapus_semua_jadwal_filter') {
        $kondisi_aksi = "";
    }

    $sql = "
        DELETE j
        FROM data_jadwal_lapangan j
        WHERE
            (? = 0 OR j.id_lapangan = ?)
            AND (? = '' OR j.tanggal = ?)
            $kondisi_aksi
            AND NOT EXISTS (
                SELECT 1
                FROM data_booking b
                WHERE b.id_lapangan = j.id_lapangan
                AND b.tanggal_booking = j.tanggal
                AND b.status_booking IN ('Menunggu Pembayaran', 'Menunggu Verifikasi', 'Terkonfirmasi')
                AND b.jam_mulai < j.jam_selesai
                AND b.jam_selesai > j.jam_mulai
            )
    ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "iiss",
        $filter_id_lapangan,
        $filter_id_lapangan,
        $filter_tanggal,
        $filter_tanggal
    );

    if (mysqli_stmt_execute($stmt)) {
        $jumlah_terhapus = mysqli_stmt_affected_rows($stmt);

        redirectWithMessage(
            "success",
            "Hapus jadwal massal berhasil. Total jadwal terhapus: {$jumlah_terhapus}. Jadwal dengan booking aktif tidak ikut dihapus.",
            $redirect_url
        );
    } else {
        redirectWithMessage(
            "error",
            "Gagal menghapus jadwal massal.",
            $redirect_url
        );
    }
}

/* =========================
   GENERATE JADWAL OTOMATIS
========================= */
if ($aksi === 'generate') {

    $id_lapangan = (int) ($_POST['id_lapangan'] ?? 0);
    $tanggal = trim($_POST['tanggal'] ?? '');
    $jam_mulai = trim($_POST['jam_mulai'] ?? '');
    $jam_selesai = trim($_POST['jam_selesai'] ?? '');
    $durasi_slot = (int) ($_POST['durasi_slot'] ?? 1);
    $status_jadwal = trim($_POST['status_jadwal'] ?? '');

    if ($id_lapangan <= 0 || $tanggal == "" || $jam_mulai == "" || $jam_selesai == "" || $durasi_slot <= 0 || $status_jadwal == "") {
        redirectWithMessage("error", "Semua field generate jadwal wajib diisi.");
    }

    if (!cekLapanganAda($conn, $id_lapangan)) {
        redirectWithMessage("error", "Lapangan tidak ditemukan.");
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
        redirectWithMessage("error", "Format tanggal tidak valid.");
    }

    if (!in_array($durasi_slot, [1, 2, 3, 4])) {
        redirectWithMessage("error", "Durasi slot tidak valid.");
    }

    if (!in_array($status_jadwal, ['tersedia', 'tidak tersedia'])) {
        redirectWithMessage("error", "Status jadwal tidak valid.");
    }

    $jam_mulai_full = strlen($jam_mulai) === 5 ? $jam_mulai . ":00" : $jam_mulai;
    $jam_selesai_full = strlen($jam_selesai) === 5 ? $jam_selesai . ":00" : $jam_selesai;

    $waktu_mulai = strtotime($tanggal . " " . $jam_mulai_full);
    $waktu_selesai = strtotime($tanggal . " " . $jam_selesai_full);
    $durasi_detik = $durasi_slot * 3600;

    if ($waktu_selesai <= $waktu_mulai) {
        redirectWithMessage("error", "Jam selesai harus lebih besar dari jam mulai.");
    }

    $jumlah_slot_maksimal = floor(($waktu_selesai - $waktu_mulai) / $durasi_detik);

    if ($jumlah_slot_maksimal <= 0) {
        redirectWithMessage("error", "Rentang waktu terlalu pendek untuk durasi slot yang dipilih.");
    }

    if ($jumlah_slot_maksimal > 50) {
        redirectWithMessage("error", "Jumlah slot terlalu banyak. Batasi maksimal 50 slot sekali generate.");
    }

    $berhasil = 0;
    $dilewati = 0;

    for ($waktu = $waktu_mulai; ($waktu + $durasi_detik) <= $waktu_selesai; $waktu += $durasi_detik) {

        $slot_mulai = date('H:i:s', $waktu);
        $slot_selesai = date('H:i:s', $waktu + $durasi_detik);

        $ada_konflik_jadwal = cekKonflikJadwal(
            $conn,
            $id_lapangan,
            $tanggal,
            $slot_mulai,
            $slot_selesai,
            0
        );

        $ada_booking = cekBookingBentrok(
            $conn,
            $id_lapangan,
            $tanggal,
            $slot_mulai,
            $slot_selesai
        );

        if ($ada_konflik_jadwal || $ada_booking) {
            $dilewati++;
            continue;
        }

        $stmt_insert = mysqli_prepare($conn, "
            INSERT INTO data_jadwal_lapangan
            (id_lapangan, tanggal, jam_mulai, jam_selesai, status_jadwal)
            VALUES (?, ?, ?, ?, ?)
        ");

        mysqli_stmt_bind_param(
            $stmt_insert,
            "issss",
            $id_lapangan,
            $tanggal,
            $slot_mulai,
            $slot_selesai,
            $status_jadwal
        );

        if (mysqli_stmt_execute($stmt_insert)) {
            $berhasil++;
        } else {
            $dilewati++;
        }

        mysqli_stmt_close($stmt_insert);
    }

    redirectWithMessage(
        "success",
        "Generate jadwal selesai. Berhasil: {$berhasil} slot. Dilewati: {$dilewati} slot."
    );
}

/* =========================
   TAMBAH / EDIT JADWAL
========================= */
if ($aksi === 'tambah' || $aksi === 'edit') {

    $id_jadwal = (int) ($_POST['id_jadwal'] ?? 0);
    $id_lapangan = (int) ($_POST['id_lapangan'] ?? 0);
    $tanggal = trim($_POST['tanggal'] ?? '');
    $jam_mulai = trim($_POST['jam_mulai'] ?? '');
    $jam_selesai = trim($_POST['jam_selesai'] ?? '');
    $status_jadwal = trim($_POST['status_jadwal'] ?? '');

    if ($id_lapangan <= 0 || $tanggal == "" || $jam_mulai == "" || $jam_selesai == "" || $status_jadwal == "") {
        redirectWithMessage("error", "Semua field jadwal wajib diisi.");
    }

    if (!cekLapanganAda($conn, $id_lapangan)) {
        redirectWithMessage("error", "Lapangan tidak ditemukan.");
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
        redirectWithMessage("error", "Format tanggal tidak valid.");
    }

    if (!in_array($status_jadwal, ['tersedia', 'tidak tersedia'])) {
        redirectWithMessage("error", "Status jadwal tidak valid.");
    }

    $jam_mulai_full = strlen($jam_mulai) === 5 ? $jam_mulai . ":00" : $jam_mulai;
    $jam_selesai_full = strlen($jam_selesai) === 5 ? $jam_selesai . ":00" : $jam_selesai;

    if ($jam_selesai_full <= $jam_mulai_full) {
        redirectWithMessage("error", "Jam selesai harus lebih besar dari jam mulai.");
    }

    $exclude_id = $aksi === 'edit' ? $id_jadwal : 0;

    if (cekKonflikJadwal($conn, $id_lapangan, $tanggal, $jam_mulai_full, $jam_selesai_full, $exclude_id)) {
        redirectWithMessage("error", "Jadwal bentrok dengan jadwal lain pada lapangan dan tanggal yang sama.");
    }

    if ($status_jadwal === 'tidak tersedia') {
        if (cekBookingBentrok($conn, $id_lapangan, $tanggal, $jam_mulai_full, $jam_selesai_full)) {
            redirectWithMessage("error", "Tidak bisa membuat jadwal tidak tersedia karena sudah ada booking aktif pada slot tersebut.");
        }
    }

    if ($aksi === 'tambah') {

        $stmt = mysqli_prepare($conn, "
            INSERT INTO data_jadwal_lapangan
            (id_lapangan, tanggal, jam_mulai, jam_selesai, status_jadwal)
            VALUES (?, ?, ?, ?, ?)
        ");

        mysqli_stmt_bind_param(
            $stmt,
            "issss",
            $id_lapangan,
            $tanggal,
            $jam_mulai_full,
            $jam_selesai_full,
            $status_jadwal
        );

        if (mysqli_stmt_execute($stmt)) {
            redirectWithMessage("success", "Jadwal lapangan berhasil ditambahkan.");
        } else {
            redirectWithMessage("error", "Gagal menambahkan jadwal lapangan.");
        }
    } else {

        if ($id_jadwal <= 0) {
            redirectWithMessage("error", "Data jadwal tidak valid.");
        }

        $stmt = mysqli_prepare($conn, "
            UPDATE data_jadwal_lapangan SET
                id_lapangan = ?,
                tanggal = ?,
                jam_mulai = ?,
                jam_selesai = ?,
                status_jadwal = ?
            WHERE id_jadwal = ?
        ");

        mysqli_stmt_bind_param(
            $stmt,
            "issssi",
            $id_lapangan,
            $tanggal,
            $jam_mulai_full,
            $jam_selesai_full,
            $status_jadwal,
            $id_jadwal
        );

        if (mysqli_stmt_execute($stmt)) {
            redirectWithMessage("success", "Jadwal lapangan berhasil diperbarui.");
        } else {
            redirectWithMessage("error", "Gagal memperbarui jadwal lapangan.");
        }
    }
}

/* =========================
   HAPUS JADWAL
========================= */
if ($aksi === 'hapus') {

    $id_jadwal = (int) ($_POST['id_jadwal'] ?? 0);

    if ($id_jadwal <= 0) {
        redirectWithMessage("error", "Data jadwal tidak valid.");
    }

    $stmt_jadwal = mysqli_prepare($conn, "
        SELECT id_lapangan, tanggal, jam_mulai, jam_selesai
        FROM data_jadwal_lapangan
        WHERE id_jadwal = ?
        LIMIT 1
    ");

    mysqli_stmt_bind_param($stmt_jadwal, "i", $id_jadwal);
    mysqli_stmt_execute($stmt_jadwal);
    $result_jadwal = mysqli_stmt_get_result($stmt_jadwal);
    $jadwal = mysqli_fetch_assoc($result_jadwal);

    if (!$jadwal) {
        redirectWithMessage("error", "Jadwal tidak ditemukan.");
    }

    if (cekBookingBentrok(
        $conn,
        $jadwal['id_lapangan'],
        $jadwal['tanggal'],
        $jadwal['jam_mulai'],
        $jadwal['jam_selesai']
    )) {
        redirectWithMessage("error", "Jadwal tidak bisa dihapus karena sudah memiliki booking aktif.");
    }

    $stmt = mysqli_prepare($conn, "
        DELETE FROM data_jadwal_lapangan
        WHERE id_jadwal = ?
    ");

    mysqli_stmt_bind_param($stmt, "i", $id_jadwal);

    if (mysqli_stmt_execute($stmt)) {
        redirectWithMessage("success", "Jadwal lapangan berhasil dihapus.");
    } else {
        redirectWithMessage("error", "Gagal menghapus jadwal lapangan.");
    }
}

redirectWithMessage("error", "Aksi tidak valid.");
