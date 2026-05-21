<?php

function jalankanReminderBooking($conn)
{
    date_default_timezone_set('Asia/Jakarta');

    $waktu_sekarang = date('Y-m-d H:i:s');

    /*
        Reminder dibuat jika booking akan dimulai dalam 1 jam ke depan.
        Kalau mau 30 menit, ganti '+1 hour' jadi '+30 minutes'.
    */
    $batas_reminder = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $stmt = mysqli_prepare($conn, "
        SELECT 
            b.id_booking,
            b.kode_booking,
            b.id_user,
            b.tanggal_booking,
            b.jam_mulai,
            b.jam_selesai,

            l.nama_lapangan,
            l.jenis_olahraga
        FROM data_booking b
        JOIN data_lapangan l ON b.id_lapangan = l.id_lapangan
        WHERE b.status_booking = 'Terkonfirmasi'
        AND TIMESTAMP(b.tanggal_booking, b.jam_mulai) BETWEEN ? AND ?
    ");

    mysqli_stmt_bind_param(
        $stmt,
        "ss",
        $waktu_sekarang,
        $batas_reminder
    );

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($booking = mysqli_fetch_assoc($result)) {

        $judul_reminder = "Reminder Booking";
        $jumlah_notifikasi = 0;

        $stmt_cek = mysqli_prepare($conn, "
            SELECT COUNT(*)
            FROM data_notifikasi
            WHERE id_user = ?
            AND id_booking = ?
            AND judul = ?
        ");

        mysqli_stmt_bind_param(
            $stmt_cek,
            "iis",
            $booking['id_user'],
            $booking['id_booking'],
            $judul_reminder
        );

        mysqli_stmt_execute($stmt_cek);
        mysqli_stmt_bind_result($stmt_cek, $jumlah_notifikasi);
        mysqli_stmt_fetch($stmt_cek);
        mysqli_stmt_close($stmt_cek);

        if ($jumlah_notifikasi > 0) {
            continue;
        }

        $tanggal = date('d M Y', strtotime($booking['tanggal_booking']));
        $jam_mulai = date('H:i', strtotime($booking['jam_mulai']));
        $jam_selesai = date('H:i', strtotime($booking['jam_selesai']));

        $pesan = "Booking {$booking['kode_booking']} untuk {$booking['nama_lapangan']} ({$booking['jenis_olahraga']}) akan berlangsung pada {$tanggal} pukul {$jam_mulai} - {$jam_selesai}. Pastikan datang sesuai jadwal.";

        $stmt_insert = mysqli_prepare($conn, "
            INSERT INTO data_notifikasi
            (id_user, id_booking, judul, pesan)
            VALUES (?, ?, ?, ?)
        ");

        mysqli_stmt_bind_param(
            $stmt_insert,
            "iiss",
            $booking['id_user'],
            $booking['id_booking'],
            $judul_reminder,
            $pesan
        );

        mysqli_stmt_execute($stmt_insert);
        mysqli_stmt_close($stmt_insert);
    }

    mysqli_stmt_close($stmt);
}

?>