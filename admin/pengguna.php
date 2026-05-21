<?php

require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/koneksi.php';

$keyword = trim($_GET['keyword'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

$allowed_status = ['aktif', 'nonaktif'];

if (!in_array($status_filter, $allowed_status)) {
    $status_filter = '';
}

$keyword_like = '%' . $keyword . '%';

/* =========================
   PAGINATION
========================= */
$limit = 10;
$page = (int) ($_GET['page'] ?? 1);

if ($page < 1) {
    $page = 1;
}

function buildPenggunaPageUrl($page_number, $keyword, $status_filter)
{
    $params = [];

    if ($keyword != '') {
        $params['keyword'] = $keyword;
    }

    if ($status_filter != '') {
        $params['status'] = $status_filter;
    }

    $params['page'] = $page_number;

    return 'pengguna.php?' . http_build_query($params);
}

/* Hitung total pengguna */
$total_pengguna = 0;

$stmt_total = mysqli_prepare($conn, "
    SELECT COUNT(*)
    FROM data_pengguna p
    WHERE p.role = 'user'
    AND (? = '' OR p.nama LIKE ? OR p.email LIKE ? OR p.no_hp LIKE ?)
    AND (? = '' OR p.status_akun = ?)
");

mysqli_stmt_bind_param(
    $stmt_total,
    "ssssss",
    $keyword,
    $keyword_like,
    $keyword_like,
    $keyword_like,
    $status_filter,
    $status_filter
);

mysqli_stmt_execute($stmt_total);
mysqli_stmt_bind_result($stmt_total, $total_pengguna);
mysqli_stmt_fetch($stmt_total);
mysqli_stmt_close($stmt_total);

$total_halaman = ceil($total_pengguna / $limit);

if ($total_halaman < 1) {
    $total_halaman = 1;
}

if ($page > $total_halaman) {
    $page = $total_halaman;
}

$offset = ($page - 1) * $limit;

/* Ambil data pengguna */
$stmt = mysqli_prepare($conn, "
    SELECT 
        p.id_user,
        p.nama,
        p.email,
        p.no_hp,
        p.role,
        p.status_akun,
        p.created_at,
        COUNT(b.id_booking) AS total_booking
    FROM data_pengguna p
    LEFT JOIN data_booking b ON p.id_user = b.id_user
    WHERE p.role = 'user'
    AND (? = '' OR p.nama LIKE ? OR p.email LIKE ? OR p.no_hp LIKE ?)
    AND (? = '' OR p.status_akun = ?)
    GROUP BY 
        p.id_user,
        p.nama,
        p.email,
        p.no_hp,
        p.role,
        p.status_akun,
        p.created_at
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
");

mysqli_stmt_bind_param(
    $stmt,
    "ssssssii",
    $keyword,
    $keyword_like,
    $keyword_like,
    $keyword_like,
    $status_filter,
    $status_filter,
    $limit,
    $offset
);

mysqli_stmt_execute($stmt);
$result_pengguna = mysqli_stmt_get_result($stmt);

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

    .btn-filter-pengguna {
        height: 54px;
        border-radius: 14px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 18px;
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

    .status-aktif {
        background: #dcfce7;
        color: #166534;
    }

    .status-nonaktif {
        background: #fee2e2;
        color: #991b1b;
    }

    .role-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 7px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        background: #e0f2fe;
        color: #075985;
    }

    .action-box {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        min-width: 270px;
    }

    .action-box .btn,
    .action-box form,
    .action-box form button {
        width: 100%;
    }

    .action-delete {
        grid-column: 1 / -1;
    }

    .action-box .btn {
        border-radius: 10px;
        font-weight: 600;
    }

    .empty-state {
        border: 1px dashed #cbd5e1;
        border-radius: 18px;
        padding: 34px;
        text-align: center;
        background: #f8fafc;
        color: #64748b;
    }

    .table-meta {
        color: #64748b;
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

    @media (max-width: 992px) {
        .action-box {
            min-width: 190px;
        }
    }
</style>

<main class="flex-grow-1">

    <section class="admin-page">
        <div class="container">

            <div class="page-hero">
                <div class="row align-items-center gy-3">

                    <div class="col-lg-8">
                        <span class="badge bg-light text-success px-3 py-2 mb-3">
                            Kelola Pengguna
                        </span>

                        <h1 class="fw-bold mb-2">
                            Kelola akun pengguna sistem.
                        </h1>

                        <p class="mb-0">
                            Admin dapat melihat data pengguna, mengaktifkan atau menonaktifkan akun,
                            reset password, dan menghapus akun pengguna tertentu.
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

            <div class="filter-card mb-4">

                <form method="GET" action="pengguna.php">

                    <div class="row g-3 align-items-end">

                        <div class="col-lg-6">
                            <label class="form-label fw-semibold">Cari Pengguna</label>
                            <input 
                                type="text" 
                                name="keyword" 
                                class="form-control"
                                placeholder="Cari nama, email, atau nomor HP"
                                value="<?= htmlspecialchars($keyword); ?>"
                            >
                        </div>

                        <div class="col-lg-4">
                            <label class="form-label fw-semibold">Status Akun</label>
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="aktif" <?= $status_filter == 'aktif' ? 'selected' : ''; ?>>
                                    Aktif
                                </option>
                                <option value="nonaktif" <?= $status_filter == 'nonaktif' ? 'selected' : ''; ?>>
                                    Nonaktif
                                </option>
                            </select>
                        </div>

                        <div class="col-lg-2">
                            <button type="submit" class="btn btn-success w-100 btn-filter-pengguna">
                                Filter
                            </button>
                        </div>

                    </div>

                    <?php if ($keyword != '' || $status_filter != '') : ?>
                        <div class="mt-3">
                            <a href="pengguna.php" class="btn btn-sm btn-outline-secondary">
                                Reset Filter
                            </a>
                        </div>
                    <?php endif; ?>

                </form>

            </div>

            <div class="content-card">

                <div class="mb-4">
                    <h4 class="fw-bold mb-1">
                        Data Pengguna
                    </h4>

                    <p class="text-secondary mb-0">
                        Daftar akun pengguna yang melakukan registrasi pada sistem.
                    </p>

                    <?php if ($total_pengguna > 0) : ?>
                        <div class="table-meta mt-1">
                            Menampilkan halaman <?= $page; ?> dari <?= $total_halaman; ?>.
                            Total <?= $total_pengguna; ?> pengguna.
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (mysqli_num_rows($result_pengguna) > 0) : ?>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>No HP</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Total Booking</th>
                                    <th>Terdaftar</th>
                                    <th width="250">Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php while ($pengguna = mysqli_fetch_assoc($result_pengguna)) : ?>

                                    <?php
                                    $status_akun = strtolower($pengguna['status_akun']);
                                    $status_class = $status_akun == 'aktif' ? 'status-aktif' : 'status-nonaktif';
                                    $status_label = $status_akun == 'aktif' ? 'Aktif' : 'Nonaktif';
                                    ?>

                                    <tr>
                                        <td class="fw-bold">
                                            <?= htmlspecialchars($pengguna['nama']); ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($pengguna['email']); ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($pengguna['no_hp'] ?: '-'); ?>
                                        </td>

                                        <td>
                                            <span class="role-badge">
                                                <?= htmlspecialchars(ucfirst($pengguna['role'])); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <span class="status-badge <?= $status_class; ?>">
                                                <?= $status_label; ?>
                                            </span>
                                        </td>

                                        <td class="fw-bold">
                                            <?= (int) $pengguna['total_booking']; ?>
                                        </td>

                                        <td>
                                            <?= date('d M Y', strtotime($pengguna['created_at'])); ?>
                                        </td>

                                        <td>
                                            <div class="action-box">

                                                <?php if ($status_akun == 'aktif') : ?>
                                                    <form 
                                                        action="../process/pengguna_process.php" 
                                                        method="POST"
                                                        onsubmit="return confirm('Yakin ingin menonaktifkan akun ini?');"
                                                    >
                                                        <input type="hidden" name="aksi" value="nonaktifkan">
                                                        <input type="hidden" name="id_user" value="<?= $pengguna['id_user']; ?>">

                                                        <button type="submit" class="btn btn-sm btn-warning">
                                                            Nonaktifkan
                                                        </button>
                                                    </form>
                                                <?php else : ?>
                                                    <form 
                                                        action="../process/pengguna_process.php" 
                                                        method="POST"
                                                        onsubmit="return confirm('Yakin ingin mengaktifkan akun ini?');"
                                                    >
                                                        <input type="hidden" name="aksi" value="aktifkan">
                                                        <input type="hidden" name="id_user" value="<?= $pengguna['id_user']; ?>">

                                                        <button type="submit" class="btn btn-sm btn-success">
                                                            Aktifkan
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <form 
                                                    action="../process/pengguna_process.php" 
                                                    method="POST"
                                                    onsubmit="return confirm('Reset password pengguna ini ke password default?');"
                                                >
                                                    <input type="hidden" name="aksi" value="reset_password">
                                                    <input type="hidden" name="id_user" value="<?= $pengguna['id_user']; ?>">

                                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                                        Reset Password
                                                    </button>
                                                </form>

                                                <form 
                                                    action="../process/pengguna_process.php" 
                                                    method="POST"
                                                    class="action-delete"
                                                    onsubmit="return confirm('Yakin ingin menghapus akun pengguna ini? Data yang berkaitan bisa ikut terdampak.');"
                                                >
                                                    <input type="hidden" name="aksi" value="hapus">
                                                    <input type="hidden" name="id_user" value="<?= $pengguna['id_user']; ?>">

                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
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
                                    href="<?= buildPenggunaPageUrl($page - 1, $keyword, $status_filter); ?>" 
                                    class="pagination-link"
                                >
                                    &laquo;
                                </a>
                            <?php else : ?>
                                <span class="pagination-link disabled">
                                    &laquo;
                                </span>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_halaman; $i++) : ?>

                                <a 
                                    href="<?= buildPenggunaPageUrl($i, $keyword, $status_filter); ?>" 
                                    class="pagination-link <?= $i == $page ? 'active' : ''; ?>"
                                >
                                    <?= $i; ?>
                                </a>

                            <?php endfor; ?>

                            <?php if ($page < $total_halaman) : ?>
                                <a 
                                    href="<?= buildPenggunaPageUrl($page + 1, $keyword, $status_filter); ?>" 
                                    class="pagination-link"
                                >
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
                        <i class="bi bi-people fs-1 d-block mb-3"></i>

                        <h5 class="fw-bold">
                            Data pengguna tidak ditemukan
                        </h5>

                        <p class="mb-0">
                            Belum ada pengguna yang sesuai dengan filter pencarian.
                        </p>
                    </div>

                <?php endif; ?>

            </div>

        </div>
    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php include __DIR__ . '/../includes/scripts.php'; ?>