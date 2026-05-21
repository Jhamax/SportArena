<?php

require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/koneksi.php';

$id_lapangan_filter = (int) ($_GET['id_lapangan'] ?? 0);
$tanggal_filter = trim($_GET['tanggal'] ?? '');

if ($tanggal_filter != '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_filter)) {
    $tanggal_filter = '';
}

/* =========================
   PAGINATION
========================= */
$limit = 10;
$page = (int) ($_GET['page'] ?? 1);

if ($page < 1) {
    $page = 1;
}

/* Helper URL pagination */
function buildJadwalPageUrl($page_number, $id_lapangan_filter, $tanggal_filter)
{
    $params = [];

    if ($id_lapangan_filter != 0) {
        $params['id_lapangan'] = $id_lapangan_filter;
    }

    if ($tanggal_filter != '') {
        $params['tanggal'] = $tanggal_filter;
    }

    $params['page'] = $page_number;

    return 'jadwal_lapangan.php?' . http_build_query($params);
}

/* Ambil data lapangan untuk dropdown */
$data_lapangan = [];

$query_lapangan = mysqli_query($conn, "
    SELECT id_lapangan, nama_lapangan, jenis_olahraga
    FROM data_lapangan
    ORDER BY nama_lapangan ASC
");

while ($row = mysqli_fetch_assoc($query_lapangan)) {
    $data_lapangan[] = $row;
}

/* Mode edit */
$edit_data = null;

if (isset($_GET['edit'])) {
    $id_jadwal_edit = (int) $_GET['edit'];

    $stmt_edit = mysqli_prepare($conn, "
        SELECT 
            j.id_jadwal,
            j.id_lapangan,
            j.tanggal,
            j.jam_mulai,
            j.jam_selesai,
            j.status_jadwal,
            l.nama_lapangan,
            l.jenis_olahraga
        FROM data_jadwal_lapangan j
        JOIN data_lapangan l ON j.id_lapangan = l.id_lapangan
        WHERE j.id_jadwal = ?
        LIMIT 1
    ");

    mysqli_stmt_bind_param($stmt_edit, "i", $id_jadwal_edit);
    mysqli_stmt_execute($stmt_edit);
    $result_edit = mysqli_stmt_get_result($stmt_edit);
    $edit_data = mysqli_fetch_assoc($result_edit);
    mysqli_stmt_close($stmt_edit);
}

/* Hitung total jadwal sesuai filter */
$total_jadwal = 0;

$stmt_total = mysqli_prepare($conn, "
    SELECT COUNT(*)
    FROM data_jadwal_lapangan j
    JOIN data_lapangan l ON j.id_lapangan = l.id_lapangan
    WHERE
        (? = 0 OR j.id_lapangan = ?)
        AND (? = '' OR j.tanggal = ?)
");

mysqli_stmt_bind_param(
    $stmt_total,
    "iiss",
    $id_lapangan_filter,
    $id_lapangan_filter,
    $tanggal_filter,
    $tanggal_filter
);

mysqli_stmt_execute($stmt_total);
mysqli_stmt_bind_result($stmt_total, $total_jadwal);
mysqli_stmt_fetch($stmt_total);
mysqli_stmt_close($stmt_total);

$total_halaman = ceil($total_jadwal / $limit);

if ($total_halaman < 1) {
    $total_halaman = 1;
}

if ($page > $total_halaman) {
    $page = $total_halaman;
}

$offset = ($page - 1) * $limit;

/* Ambil data jadwal sesuai filter + pagination */
$stmt_jadwal = mysqli_prepare($conn, "
    SELECT 
        j.id_jadwal,
        j.id_lapangan,
        j.tanggal,
        j.jam_mulai,
        j.jam_selesai,
        j.status_jadwal,
        l.nama_lapangan,
        l.jenis_olahraga
    FROM data_jadwal_lapangan j
    JOIN data_lapangan l ON j.id_lapangan = l.id_lapangan
    WHERE
        (? = 0 OR j.id_lapangan = ?)
        AND (? = '' OR j.tanggal = ?)
    ORDER BY j.tanggal DESC, j.jam_mulai ASC
    LIMIT ? OFFSET ?
");

mysqli_stmt_bind_param(
    $stmt_jadwal,
    "iissii",
    $id_lapangan_filter,
    $id_lapangan_filter,
    $tanggal_filter,
    $tanggal_filter,
    $limit,
    $offset
);

mysqli_stmt_execute($stmt_jadwal);
$result_jadwal = mysqli_stmt_get_result($stmt_jadwal);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';

?>

<style>
    .admin-page {
        background: #f8fafc;
        padding: 42px 0 70px;
    }

    .page-hero {
        background: linear-gradient(135deg, #0f172a, #166534);
        color: white;
        border-radius: 28px;
        padding: 34px;
        margin-bottom: 28px;
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.18);
    }

    .page-hero p {
        color: #d1fae5;
    }

    .form-card,
    .filter-card,
    .content-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 24px;
        padding: 26px;
    }

    .form-control,
    .form-select {
        border-radius: 14px;
        padding: 12px 14px;
        min-height: 54px;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #198754;
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.15);
    }

    .btn-filter-jadwal {
        height: 54px;
        border-radius: 14px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 18px;
    }

    .jadwal-tabs {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 6px;
        display: flex;
        gap: 6px;
    }

    .jadwal-tabs .nav-item {
        flex: 1;
    }

    .jadwal-tabs .nav-link {
        width: 100%;
        border-radius: 12px;
        color: #64748b;
        font-weight: 700;
        font-size: 14px;
    }

    .jadwal-tabs .nav-link.active {
        background: #198754;
        color: #ffffff;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 7px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.2;
        white-space: nowrap;
        text-align: center;
    }

    .status-tersedia {
        background: #dcfce7;
        color: #166534;
    }

    .status-tidak {
        background: #fee2e2;
        color: #991b1b;
    }

    .empty-state {
        border: 1px dashed #cbd5e1;
        border-radius: 18px;
        padding: 34px;
        text-align: center;
        background: #f8fafc;
        color: #64748b;
    }

    .helper-box {
        background: #ecfdf5;
        border: 1px solid #bbf7d0;
        color: #166534;
        border-radius: 18px;
        padding: 16px;
        font-size: 14px;
    }

    .pagination-wrap {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 26px;
    }

    .pagination-link {
        min-width: 42px;
        height: 42px;
        border-radius: 12px;
        border: 1px solid #d1d5db;
        background: white;
        color: #166534;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        transition: 0.2s ease;
        padding: 0 12px;
    }

    .pagination-link:hover {
        background: #ecfdf5;
        border-color: #198754;
        color: #166534;
    }

    .pagination-link.active {
        background: #198754;
        border-color: #198754;
        color: white;
    }

    .pagination-link.disabled {
        background: #f3f4f6;
        color: #9ca3af;
        pointer-events: none;
    }

    .table-meta {
        color: #64748b;
        font-size: 14px;
    }

    .bulk-action-box {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 14px;
        margin-top: 16px;
    }

    .bulk-action-box .btn {
        border-radius: 10px;
        font-weight: 600;
    }
</style>

<main class="flex-grow-1">

    <section class="admin-page">
        <div class="container">

            <div class="page-hero">
                <div class="row align-items-center gy-3">

                    <div class="col-lg-8">
                        <span class="badge bg-light text-success px-3 py-2 mb-3">
                            Kelola Jadwal Lapangan
                        </span>

                        <h1 class="fw-bold mb-2">
                            Atur slot waktu operasional setiap lapangan.
                        </h1>

                        <p class="mb-0">
                            Admin dapat menambah jadwal manual atau generate banyak slot sekaligus
                            agar proses booking lebih jelas dan tidak bentrok.
                        </p>
                    </div>

                    <div class="col-lg-4 text-lg-end">
                        <a href="dashboard.php" class="btn btn-light fw-semibold px-4">
                            Kembali ke Dashboard
                        </a>
                    </div>

                </div>
            </div>

            <?php if (isset($_SESSION['success'])) : ?>
                <div class="alert alert-success">
                    <?= $_SESSION['success']; ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])) : ?>
                <div class="alert alert-danger">
                    <?= $_SESSION['error']; ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="row g-4">

                <!-- FORM JADWAL -->
                <div class="col-lg-4">

                    <div class="form-card">

                        <div class="mb-4">
                            <h4 class="fw-bold mb-2">
                                Form Jadwal Lapangan
                            </h4>

                            <p class="text-secondary mb-0">
                                Tambahkan jadwal secara manual atau generate banyak slot sekaligus.
                            </p>
                        </div>

                        <ul class="nav nav-pills jadwal-tabs mb-4" id="jadwalTab" role="tablist">

                            <li class="nav-item" role="presentation">
                                <button
                                    class="nav-link active"
                                    id="manual-tab"
                                    data-bs-toggle="pill"
                                    data-bs-target="#manual"
                                    type="button"
                                    role="tab">
                                    <?= $edit_data ? 'Edit Jadwal' : 'Tambah Manual'; ?>
                                </button>
                            </li>

                            <li class="nav-item" role="presentation">
                                <button
                                    class="nav-link"
                                    id="generate-tab"
                                    data-bs-toggle="pill"
                                    data-bs-target="#generate"
                                    type="button"
                                    role="tab">
                                    Generate
                                </button>
                            </li>

                        </ul>

                        <div class="tab-content" id="jadwalTabContent">

                            <!-- TAB TAMBAH / EDIT MANUAL -->
                            <div
                                class="tab-pane fade show active"
                                id="manual"
                                role="tabpanel">

                                <form action="../process/jadwal_process.php" method="POST">

                                    <input type="hidden" name="aksi" value="<?= $edit_data ? 'edit' : 'tambah'; ?>">

                                    <?php if ($edit_data) : ?>
                                        <input type="hidden" name="id_jadwal" value="<?= $edit_data['id_jadwal']; ?>">
                                    <?php endif; ?>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Lapangan</label>
                                        <select name="id_lapangan" class="form-select" required>
                                            <option value="">Pilih lapangan</option>

                                            <?php foreach ($data_lapangan as $lapangan) : ?>
                                                <option
                                                    value="<?= $lapangan['id_lapangan']; ?>"
                                                    <?= $edit_data && $edit_data['id_lapangan'] == $lapangan['id_lapangan'] ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($lapangan['nama_lapangan']); ?>
                                                    - <?= htmlspecialchars($lapangan['jenis_olahraga']); ?>
                                                </option>
                                            <?php endforeach; ?>

                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Tanggal</label>
                                        <input
                                            type="date"
                                            name="tanggal"
                                            class="form-control"
                                            value="<?= $edit_data ? htmlspecialchars($edit_data['tanggal']) : ''; ?>"
                                            required>
                                    </div>

                                    <div class="row">

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-semibold">Jam Mulai</label>
                                            <input
                                                type="time"
                                                name="jam_mulai"
                                                class="form-control"
                                                value="<?= $edit_data ? date('H:i', strtotime($edit_data['jam_mulai'])) : ''; ?>"
                                                required>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-semibold">Jam Selesai</label>
                                            <input
                                                type="time"
                                                name="jam_selesai"
                                                class="form-control"
                                                value="<?= $edit_data ? date('H:i', strtotime($edit_data['jam_selesai'])) : ''; ?>"
                                                required>
                                        </div>

                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Status Jadwal</label>
                                        <select name="status_jadwal" class="form-select" required>
                                            <option value="tersedia" <?= $edit_data && $edit_data['status_jadwal'] == 'tersedia' ? 'selected' : ''; ?>>
                                                Tersedia
                                            </option>
                                            <option value="tidak tersedia" <?= $edit_data && $edit_data['status_jadwal'] == 'tidak tersedia' ? 'selected' : ''; ?>>
                                                Tidak Tersedia
                                            </option>
                                        </select>

                                        <small class="text-secondary">
                                            Gunakan “Tidak Tersedia” untuk menutup slot tertentu, misalnya maintenance.
                                        </small>
                                    </div>

                                    <div class="d-grid gap-2">

                                        <button type="submit" class="btn btn-success">
                                            <?= $edit_data ? 'Simpan Perubahan' : 'Tambah Jadwal'; ?>
                                        </button>

                                        <?php if ($edit_data) : ?>
                                            <a href="jadwal_lapangan.php" class="btn btn-outline-secondary">
                                                Batal Edit
                                            </a>
                                        <?php endif; ?>

                                    </div>

                                </form>

                            </div>

                            <!-- TAB GENERATE OTOMATIS -->
                            <div
                                class="tab-pane fade"
                                id="generate"
                                role="tabpanel">

                                <div class="helper-box mb-4">
                                    Gunakan fitur ini jika admin ingin membuat banyak slot jadwal sekaligus,
                                    misalnya dari jam 07:00 sampai 18:00.
                                </div>

                                <form action="../process/jadwal_process.php" method="POST">

                                    <input type="hidden" name="aksi" value="generate">

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Lapangan</label>
                                        <select name="id_lapangan" class="form-select" required>
                                            <option value="">Pilih lapangan</option>

                                            <?php foreach ($data_lapangan as $lapangan) : ?>
                                                <option value="<?= $lapangan['id_lapangan']; ?>">
                                                    <?= htmlspecialchars($lapangan['nama_lapangan']); ?>
                                                    - <?= htmlspecialchars($lapangan['jenis_olahraga']); ?>
                                                </option>
                                            <?php endforeach; ?>

                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Tanggal</label>
                                        <input
                                            type="date"
                                            name="tanggal"
                                            class="form-control"
                                            required>
                                    </div>

                                    <div class="row">

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-semibold">Jam Mulai</label>
                                            <input
                                                type="time"
                                                name="jam_mulai"
                                                class="form-control"
                                                required>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-semibold">Jam Selesai</label>
                                            <input
                                                type="time"
                                                name="jam_selesai"
                                                class="form-control"
                                                required>
                                        </div>

                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Durasi Per Slot</label>
                                        <select name="durasi_slot" class="form-select" required>
                                            <option value="1">1 Jam</option>
                                            <option value="2">2 Jam</option>
                                            <option value="3">3 Jam</option>
                                            <option value="4">4 Jam</option>
                                        </select>

                                        <small class="text-secondary">
                                            Saran: gunakan 1 jam agar user lebih fleksibel memilih jadwal.
                                        </small>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Status Jadwal</label>
                                        <select name="status_jadwal" class="form-select" required>
                                            <option value="tersedia">Tersedia</option>
                                            <option value="tidak tersedia">Tidak Tersedia</option>
                                        </select>
                                    </div>

                                    <button
                                        type="submit"
                                        class="btn btn-success w-100"
                                        onclick="return confirm('Generate jadwal otomatis sesuai rentang waktu ini?');">
                                        Generate Jadwal
                                    </button>

                                </form>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- DATA JADWAL -->
                <div class="col-lg-8">

                    <div class="filter-card mb-4">

                        <form method="GET" action="jadwal_lapangan.php">

                            <div class="row g-3 align-items-end">

                                <div class="col-md-5">
                                    <label class="form-label fw-semibold">Filter Lapangan</label>
                                    <select name="id_lapangan" class="form-select">
                                        <option value="0">Semua Lapangan</option>

                                        <?php foreach ($data_lapangan as $lapangan) : ?>
                                            <option
                                                value="<?= $lapangan['id_lapangan']; ?>"
                                                <?= $id_lapangan_filter == $lapangan['id_lapangan'] ? 'selected' : ''; ?>>
                                                <?= htmlspecialchars($lapangan['nama_lapangan']); ?>
                                            </option>
                                        <?php endforeach; ?>

                                    </select>
                                </div>

                                <div class="col-md-5">
                                    <label class="form-label fw-semibold">Filter Tanggal</label>
                                    <input
                                        type="date"
                                        name="tanggal"
                                        class="form-control"
                                        value="<?= htmlspecialchars($tanggal_filter); ?>">
                                </div>

                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-success w-100 btn-filter-jadwal">
                                        Filter
                                    </button>
                                </div>

                            </div>

                            <?php if ($id_lapangan_filter != 0 || $tanggal_filter != '') : ?>
                                <div class="mt-3">
                                    <a href="jadwal_lapangan.php" class="btn btn-sm btn-outline-secondary">
                                        Reset Filter
                                    </a>
                                </div>
                            <?php endif; ?>

                        </form>

                    </div>

                    <div class="content-card">

                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                            <div>
                                <h4 class="fw-bold mb-1">
                                    Data Jadwal Lapangan
                                </h4>

                                <p class="text-secondary mb-0">
                                    Daftar slot jadwal yang telah diatur oleh admin.
                                </p>

                                <div class="bulk-action-box">

                                    <small class="text-secondary d-block mb-2">
                                        Aksi massal jadwal. Untuk hapus tersedia/tidak tersedia/semua, gunakan filter lapangan atau tanggal terlebih dahulu.
                                    </small>

                                    <form
                                        action="../process/jadwal_process.php"
                                        method="POST"
                                        class="d-flex gap-2 flex-wrap">
                                        <input type="hidden" name="filter_id_lapangan" value="<?= $id_lapangan_filter; ?>">
                                        <input type="hidden" name="filter_tanggal" value="<?= htmlspecialchars($tanggal_filter); ?>">

                                        <button
                                            type="submit"
                                            name="aksi"
                                            value="hapus_jadwal_lewat"
                                            class="btn btn-sm btn-outline-secondary"
                                            onclick="return confirm('Hapus jadwal yang sudah lewat dan tidak memiliki booking aktif?');">
                                            Hapus Jadwal Lewat
                                        </button>

                                        <button
                                            type="submit"
                                            name="aksi"
                                            value="hapus_jadwal_tidak_tersedia"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Hapus jadwal tidak tersedia sesuai filter?');">
                                            Hapus Tidak Tersedia
                                        </button>

                                        <button
                                            type="submit"
                                            name="aksi"
                                            value="hapus_jadwal_tersedia"
                                            class="btn btn-sm btn-outline-warning"
                                            onclick="return confirm('Hapus jadwal tersedia sesuai filter?');">
                                            Hapus Tersedia
                                        </button>

                                        <button
                                            type="submit"
                                            name="aksi"
                                            value="hapus_semua_jadwal_filter"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Hapus semua jadwal sesuai filter? Jadwal dengan booking aktif tidak akan dihapus.');">
                                            Hapus Semua Sesuai Filter
                                        </button>

                                    </form>

                                </div>

                                <?php if ($total_jadwal > 0) : ?>
                                    <div class="table-meta mt-1">
                                        Menampilkan halaman <?= $page; ?> dari <?= $total_halaman; ?>.
                                        Total <?= $total_jadwal; ?> jadwal.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (mysqli_num_rows($result_jadwal) > 0) : ?>

                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Lapangan</th>
                                            <th>Tanggal</th>
                                            <th>Jam</th>
                                            <th>Status</th>
                                            <th width="160">Aksi</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php while ($jadwal = mysqli_fetch_assoc($result_jadwal)) : ?>

                                            <tr>
                                                <td>
                                                    <div class="fw-bold">
                                                        <?= htmlspecialchars($jadwal['nama_lapangan']); ?>
                                                    </div>

                                                    <small class="text-secondary">
                                                        <?= htmlspecialchars($jadwal['jenis_olahraga']); ?>
                                                    </small>
                                                </td>

                                                <td>
                                                    <?= date('d M Y', strtotime($jadwal['tanggal'])); ?>
                                                </td>

                                                <td>
                                                    <?= date('H:i', strtotime($jadwal['jam_mulai'])); ?>
                                                    -
                                                    <?= date('H:i', strtotime($jadwal['jam_selesai'])); ?>
                                                </td>

                                                <td>
                                                    <?php if ($jadwal['status_jadwal'] == 'tersedia') : ?>
                                                        <span class="status-badge status-tersedia">
                                                            Tersedia
                                                        </span>
                                                    <?php else : ?>
                                                        <span class="status-badge status-tidak">
                                                            Tidak Tersedia
                                                        </span>
                                                    <?php endif; ?>
                                                </td>

                                                <td>
                                                    <div class="d-flex gap-2 flex-wrap">

                                                        <a
                                                            href="jadwal_lapangan.php?edit=<?= $jadwal['id_jadwal']; ?>"
                                                            class="btn btn-sm btn-warning">
                                                            Edit
                                                        </a>

                                                        <form
                                                            action="../process/jadwal_process.php"
                                                            method="POST"
                                                            onsubmit="return confirm('Yakin ingin menghapus jadwal ini?');">
                                                            <input type="hidden" name="aksi" value="hapus">
                                                            <input type="hidden" name="id_jadwal" value="<?= $jadwal['id_jadwal']; ?>">

                                                            <button type="submit" class="btn btn-sm btn-danger">
                                                                Hapus
                                                            </button>
                                                        </form>

                                                    </div>
                                                </td>
                                            </tr>

                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if ($total_halaman > 1) : ?>

                                <div class="pagination-wrap">

                                    <?php if ($page > 1) : ?>
                                        <a
                                            href="<?= buildJadwalPageUrl($page - 1, $id_lapangan_filter, $tanggal_filter); ?>"
                                            class="pagination-link">
                                            &laquo;
                                        </a>
                                    <?php else : ?>
                                        <span class="pagination-link disabled">
                                            &laquo;
                                        </span>
                                    <?php endif; ?>

                                    <?php for ($i = 1; $i <= $total_halaman; $i++) : ?>

                                        <a
                                            href="<?= buildJadwalPageUrl($i, $id_lapangan_filter, $tanggal_filter); ?>"
                                            class="pagination-link <?= $i == $page ? 'active' : ''; ?>">
                                            <?= $i; ?>
                                        </a>

                                    <?php endfor; ?>

                                    <?php if ($page < $total_halaman) : ?>
                                        <a
                                            href="<?= buildJadwalPageUrl($page + 1, $id_lapangan_filter, $tanggal_filter); ?>"
                                            class="pagination-link">
                                            &raquo;
                                        </a>
                                    <?php else : ?>
                                        <span class="pagination-link disabled">
                                            &raquo;
                                        </span>
                                    <?php endif; ?>

                                </div>

                            <?php endif; ?>

                        <?php else : ?>

                            <div class="empty-state">
                                <i class="bi bi-calendar-x fs-1 d-block mb-3"></i>

                                <h5 class="fw-bold">
                                    Belum ada jadwal lapangan
                                </h5>

                                <p class="mb-0">
                                    Tambahkan jadwal agar sistem dapat mengecek ketersediaan slot booking.
                                </p>
                            </div>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>
    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php include __DIR__ . '/../includes/scripts.php'; ?>