<?php

function jalankanAutoUpdateJadwal($conn)
{
    date_default_timezone_set('Asia/Jakarta');

    /*
        Jadwal otomatis dibuat tidak tersedia jika:
        - status masih tersedia
        - tanggal + jam_mulai sudah lewat atau sudah masuk waktu sekarang

        Contoh:
        Sekarang 10:05
        Jadwal 10:00 - 12:00
        Maka jadwal dianggap sudah berjalan dan tidak bisa dibooking lagi.
    */
    $stmt = mysqli_prepare($conn, "
        UPDATE data_jadwal_lapangan
        SET status_jadwal = 'tidak tersedia'
        WHERE status_jadwal = 'tersedia'
        AND CONCAT(tanggal, ' ', jam_mulai) <= NOW()
    ");

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

?>