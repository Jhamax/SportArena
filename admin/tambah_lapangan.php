<?php

require_once __DIR__ . '/../includes/auth_admin.php';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';

?>

<style>
    .admin-page {
        background: #f8fafc;
        padding: 42px 0 70px;
    }

    .form-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 24px;
        padding: 30px;
    }

    .form-label {
        font-weight: 600;
        color: #374151;
    }

    .form-control,
    .form-select {
        border-radius: 14px;
        padding: 12px 14px;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #198754;
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.15);
    }
</style>

<main class="flex-grow-1">

    <section class="admin-page">
        <div class="container">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="fw-bold mb-1">Tambah Lapangan</h1>
                    <p class="text-secondary mb-0">
                        Isi data lapangan sesuai kebutuhan sistem.
                    </p>
                </div>

                <a href="lapangan.php" class="btn btn-outline-secondary">
                    Kembali
                </a>
            </div>

            <div class="form-card">

                <form action="../process/lapangan_process.php" method="POST" enctype="multipart/form-data">

                    <input type="hidden" name="aksi" value="tambah">

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Lapangan</label>
                            <input 
                                type="text" 
                                name="nama_lapangan" 
                                class="form-control" 
                                placeholder="Contoh: Garuda Futsal Court"
                                required
                            >
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jenis Olahraga</label>
                            <select name="jenis_olahraga" class="form-select" required>
                                <option value="">Pilih jenis olahraga</option>
                                <option value="Futsal">Futsal</option>
                                <option value="Badminton">Badminton</option>
                                <option value="Basket">Basket</option>
                                <option value="Padel">Padel</option>
                            </select>
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Harga Per Jam</label>
                            <input 
                                type="number" 
                                name="harga_per_jam" 
                                class="form-control" 
                                placeholder="Contoh: 120000"
                                min="0"
                                required
                            >
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status Ketersediaan</label>
                            <select name="status_ketersediaan" class="form-select" required>
                                <option value="tersedia">Tersedia</option>
                                <option value="tidak tersedia">Tidak Tersedia</option>
                            </select>
                        </div>

                    </div>

                    <div class="mb-3">
                        <label class="form-label">Fasilitas</label>
                        <textarea 
                            name="fasilitas" 
                            class="form-control" 
                            rows="3"
                            placeholder="Contoh: Parkir, toilet, ruang tunggu, lampu malam"
                        ></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea 
                            name="deskripsi" 
                            class="form-control" 
                            rows="4"
                            placeholder="Tuliskan deskripsi singkat lapangan"
                        ></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Foto Lapangan</label>
                        <input 
                            type="file" 
                            name="foto" 
                            class="form-control"
                            accept="image/jpeg,image/png,image/jpg,image/webp"
                        >
                        <small class="text-secondary">
                            Format: JPG, JPEG, PNG, WEBP. Maksimal 2MB.
                        </small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success px-4">
                            Simpan Lapangan
                        </button>

                        <a href="lapangan.php" class="btn btn-outline-secondary px-4">
                            Batal
                        </a>
                    </div>

                </form>

            </div>

        </div>
    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php include __DIR__ . '/../includes/scripts.php'; ?>