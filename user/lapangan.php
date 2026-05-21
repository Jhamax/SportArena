<?php

require_once __DIR__ . '/../includes/auth_user.php';
require_once __DIR__ . '/../config/koneksi.php';

$keyword = trim($_GET['keyword'] ?? '');
$jenis = trim($_GET['jenis'] ?? '');
$status = trim($_GET['status'] ?? '');

$allowed_jenis = ['Futsal', 'Badminton', 'Basket', 'Padel'];
$allowed_status = ['tersedia', 'tidak tersedia'];

if (!in_array($jenis, $allowed_jenis)) {
    $jenis = '';
}

if (!in_array($status, $allowed_status)) {
    $status = '';
}

$keyword_like = '%' . $keyword . '%';

$stmt = mysqli_prepare($conn, "
    SELECT 
        id_lapangan,
        nama_lapangan,
        jenis_olahraga,
        harga_per_jam,
        fasilitas,
        deskripsi,
        foto,
        status_ketersediaan
    FROM data_lapangan
    WHERE
        (? = '' OR nama_lapangan LIKE ? OR fasilitas LIKE ? OR deskripsi LIKE ?)
        AND (? = '' OR jenis_olahraga = ?)
        AND (? = '' OR status_ketersediaan = ?)
    ORDER BY id_lapangan DESC
");

mysqli_stmt_bind_param(
    $stmt,
    "ssssssss",
    $keyword,
    $keyword_like,
    $keyword_like,
    $keyword_like,
    $jenis,
    $jenis,
    $status,
    $status
);

mysqli_stmt_execute($stmt);
$result_lapangan = mysqli_stmt_get_result($stmt);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';

?>

<style>
    .field-page {
        background: #f8fafc;
        padding: 42px 0 70px;
    }

    .page-hero {
        background: linear-gradient(135deg, #166534, #0f172a);
        color: white;
        border-radius: 28px;
        padding: 34px;
        margin-bottom: 28px;
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.18);
    }

    .page-hero p {
        color: #d1fae5;
    }

    .filter-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 24px;
        padding: 24px;
        margin-bottom: 28px;
    }

    .form-control,
    .form-select {
        border-radius: 14px;
        padding: 12px 14px;
    }

    .field-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 24px;
        overflow: hidden;
        height: 100%;
        transition: 0.25s ease;
    }

    .field-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
        border-color: #198754;
    }

    .field-img {
        height: 210px;
        width: 100%;
        object-fit: cover;
        background: linear-gradient(135deg, #16a34a, #0f172a);
    }

    .field-placeholder {
        height: 210px;
        background: linear-gradient(135deg, #16a34a, #0f172a);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 42px;
    }

    .field-type {
        display: inline-block;
        padding: 7px 12px;
        border-radius: 999px;
        background: #ecfdf5;
        color: #166534;
        font-size: 12px;
        font-weight: 700;
    }

    .status-badge {
        display: inline-block;
        padding: 7px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
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
        background: white;
        border: 1px dashed #cbd5e1;
        border-radius: 24px;
        padding: 42px;
        text-align: center;
        color: #64748b;
    }

    .btn-filter-lapangan {
        height: 50px;
        border-radius: 14px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 18px;
    }
</style>

<main class="flex-grow-1">

    <section class="field-page">
        <div class="container">

            <div class="page-hero">
                <div class="row align-items-center gy-3">
                    <div class="col-lg-8">
                        <span class="badge bg-light text-success px-3 py-2 mb-3">
                            Daftar Lapangan
                        </span>

                        <h1 class="fw-bold mb-2">
                            Pilih lapangan olahraga yang ingin kamu booking.
                        </h1>

                        <p class="mb-0">
                            Cari lapangan berdasarkan nama, jenis olahraga, fasilitas,
                            dan status ketersediaan sebelum melakukan reservasi.
                        </p>
                    </div>

                    <div class="col-lg-4 text-lg-end">
                        <a href="dashboard.php" class="btn btn-light fw-semibold px-4">
                            Kembali ke Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <div class="filter-card">
                <form method="GET" action="lapangan.php">

                    <div class="row g-3 align-items-end">

                        <div class="col-lg-4">
                            <label class="form-label fw-semibold">Cari Lapangan</label>
                            <input
                                type="text"
                                name="keyword"
                                class="form-control"
                                placeholder="Cari nama, fasilitas, atau deskripsi"
                                value="<?= htmlspecialchars($keyword); ?>">
                        </div>

                        <div class="col-lg-3">
                            <label class="form-label fw-semibold">Jenis Olahraga</label>
                            <select name="jenis" class="form-select">
                                <option value="">Semua Jenis</option>
                                <option value="Futsal" <?= $jenis == 'Futsal' ? 'selected' : ''; ?>>Futsal</option>
                                <option value="Badminton" <?= $jenis == 'Badminton' ? 'selected' : ''; ?>>Badminton</option>
                                <option value="Basket" <?= $jenis == 'Basket' ? 'selected' : ''; ?>>Basket</option>
                                <option value="Padel" <?= $jenis == 'Padel' ? 'selected' : ''; ?>>Padel</option>
                            </select>
                        </div>

                        <div class="col-lg-3">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="tersedia" <?= $status == 'tersedia' ? 'selected' : ''; ?>>Tersedia</option>
                                <option value="tidak tersedia" <?= $status == 'tidak tersedia' ? 'selected' : ''; ?>>Tidak Tersedia</option>
                            </select>
                        </div>

                        <div class="col-lg-2">
                            <button type="submit" class="btn btn-success w-100 btn-filter-lapangan">
                                Filter
                            </button>
                        </div>

                    </div>

                    <?php if ($keyword != '' || $jenis != '' || $status != '') : ?>
                        <div class="mt-3">
                            <a href="lapangan.php" class="btn btn-sm btn-outline-secondary">
                                Reset Filter
                            </a>
                        </div>
                    <?php endif; ?>

                </form>
            </div>

            <?php if (mysqli_num_rows($result_lapangan) > 0) : ?>

                <div class="row g-4">

                    <?php while ($lapangan = mysqli_fetch_assoc($result_lapangan)) : ?>

                        <div class="col-md-6 col-lg-4">

                            <div class="field-card">

                                <?php if (!empty($lapangan['foto'])) : ?>
                                    <img
                                        src="../<?= htmlspecialchars($lapangan['foto']); ?>"
                                        class="field-img"
                                        alt="<?= htmlspecialchars($lapangan['nama_lapangan']); ?>">
                                <?php else : ?>
                                    <div class="field-placeholder">
                                        <i class="bi bi-dribbble"></i>
                                    </div>
                                <?php endif; ?>

                                <div class="p-4">

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="field-type">
                                            <?= htmlspecialchars($lapangan['jenis_olahraga']); ?>
                                        </span>

                                        <?php if ($lapangan['status_ketersediaan'] == 'tersedia') : ?>
                                            <span class="status-badge status-tersedia">
                                                Tersedia
                                            </span>
                                        <?php else : ?>
                                            <span class="status-badge status-tidak">
                                                Tidak Tersedia
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <h5 class="fw-bold mb-2">
                                        <?= htmlspecialchars($lapangan['nama_lapangan']); ?>
                                    </h5>

                                    <p class="text-secondary small mb-3">
                                        <?= htmlspecialchars($lapangan['deskripsi'] ?: 'Deskripsi lapangan belum tersedia.'); ?>
                                    </p>

                                    <p class="text-secondary small mb-3">
                                        <strong>Fasilitas:</strong>
                                        <?= htmlspecialchars($lapangan['fasilitas'] ?: '-'); ?>
                                    </p>

                                    <div class="d-flex justify-content-between align-items-center mt-4">

                                        <div>
                                            <div class="text-secondary small">Harga</div>
                                            <div class="fw-bold text-success">
                                                Rp<?= number_format($lapangan['harga_per_jam'], 0, ',', '.'); ?>/jam
                                            </div>
                                        </div>

                                        <a
                                            href="detail_lapangan.php?id=<?= $lapangan['id_lapangan']; ?>"
                                            class="btn btn-success">
                                            Detail
                                        </a>

                                    </div>

                                </div>

                            </div>

                        </div>

                    <?php endwhile; ?>

                </div>

            <?php else : ?>

                <div class="empty-state">
                    <i class="bi bi-search fs-1 d-block mb-3"></i>
                    <h4 class="fw-bold">Lapangan tidak ditemukan</h4>
                    <p class="mb-3">
                        Tidak ada lapangan yang sesuai dengan pencarian atau filter yang kamu pilih.
                    </p>
                    <a href="lapangan.php" class="btn btn-success">
                        Tampilkan Semua Lapangan
                    </a>
                </div>

            <?php endif; ?>

        </div>
    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php include __DIR__ . '/../includes/scripts.php'; ?>