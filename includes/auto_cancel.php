<?php

function jalankanAutoCancelBooking($conn)
{
    $stmt = mysqli_prepare($conn, "
        SELECT 
            b.id_booking,
            b.id_user,
            b.kode_booking,
            b.batas_waktu_pembayaran
        FROM data_booking b
        JOIN data_pembayaran p ON b.id_booking = p.id_booking
        WHERE b.status_booking = 'Menunggu Pembayaran'
        AND p.status_pembayaran = 'Belum Dibayar'
        AND b.batas_waktu_pembayaran IS NOT NULL
        AND b.batas_waktu_pembayaran < NOW()
    ");

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $data_expired = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $data_expired[] = $row;
    }

    if (count($data_expired) == 0) {
        return;
    }

    foreach ($data_expired as $booking) {

        $status_booking = "Dibatalkan";

        $stmt_update = mysqli_prepare($conn, "
            UPDATE data_booking
            SET status_booking = ?
            WHERE id_booking = ?
            AND status_booking = 'Menunggu Pembayaran'
        ");

        mysqli_stmt_bind_param(
            $stmt_update,
            "si",
            $status_booking,
            $booking['id_booking']
        );

        $update = mysqli_stmt_execute($stmt_update);

        if ($update) {

            $judul = "Booking Dibatalkan Otomatis";
            $pesan = "Booking dengan kode {$booking['kode_booking']} dibatalkan otomatis karena melewati batas waktu pembayaran.";

            $stmt_notif = mysqli_prepare($conn, "
                INSERT INTO data_notifikasi
                (id_user, id_booking, judul, pesan)
                VALUES (?, ?, ?, ?)
            ");

            mysqli_stmt_bind_param(
                $stmt_notif,
                "iiss",
                $booking['id_user'],
                $booking['id_booking'],
                $judul,
                $pesan
            );

            mysqli_stmt_execute($stmt_notif);
        }
    }
}

?>