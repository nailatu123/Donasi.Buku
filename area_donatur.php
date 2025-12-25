<?php
// Pastikan koneksi database ($pdo) dan session sudah dimulai di file utama Anda.

// --- LOGIKA PHP (Form Simpan Donasi) ---
if (isset($_POST['simpan_donasi'])) {
    try {
        // Ambil ID User dari Session
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("Sesi anda berakhir. Silakan login kembali.");
        }
        $id_user = $_SESSION['user_id'];
        
        // Ambil data dari form
        $judul = trim($_POST['judul']);
        $penulis = trim($_POST['penulis']);
        $penerbit = trim($_POST['penerbit']);
        $tahun = trim($_POST['tahun']);
        $kategori = $_POST['kategori'] ?? 'Lainnya'; 
        $kondisi = $_POST['kondisi'];
        $jumlah = $_POST['jumlah']; 
        
        $gambar = 'default.jpg';
        
        // Proses Upload Gambar
        if (!empty($_FILES['gambar']['name'])) {
            $target_dir = "assets/img/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            
            $file_extension = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $nama_baru = uniqid('book_', true) . '.' . $file_extension;
            
            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target_dir . $nama_baru)) {
                $gambar = $nama_baru;
            } else {
                throw new Exception("Gagal mengupload gambar. Cek permission folder.");
            }
        }

        // Simpan data ke database
        $sql = "INSERT INTO buku (id_user, judul, penulis, penerbit, tahun_terbit, kategori, kondisi, jumlah, gambar, status_donasi, stok, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, NOW())";
        $stmt = $pdo->prepare($sql);
        
        $result = $stmt->execute([$id_user, $judul, $penulis, $penerbit, $tahun, $kategori, $kondisi, $jumlah, $gambar, $jumlah]);
        
        if ($result) {
            $_SESSION['flash_success'] = "Buku berhasil didonasikan! Menunggu verifikasi admin.";
        }
    } catch (Exception $e) {
        $_SESSION['flash_error'] = "Gagal: " . $e->getMessage();
    }
    // Redirect clean
    echo "<script>window.location='index.php?page=area_donatur';</script>";
    exit;
}

// --- LOGIKA PENGELOMPOKAN RIWAYAT BERDASARKAN KATEGORI ---
$id_user = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM buku WHERE id_user = ? ORDER BY created_at DESC");
$stmt->execute([$id_user]);
$riwayat_buku_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Daftar Kategori LENGKAP untuk Form Input (Sesuai ENUM database) ---
$categories = [
    ['id' => '0', 'nama' => 'Pilih Kategori'],
    ['id' => '1', 'nama' => 'Fiksi'],
    ['id' => '2', 'nama' => 'Pendidikan'],
    ['id' => '3', 'nama' => 'Non-Fiksi'],
    ['id' => '4', 'nama' => 'Komik'],
    ['id' => '5', 'nama' => 'Teknologi'],
    ['id' => '6', 'nama' => 'Sejarah'],
];
?>

<!-- STYLE KHUSUS AREA DONATUR (Flat Clean Design - Match Admin) -->
<style>
    :root {
        --primary: #A63A3A;
        --primary-hover: #8F3030;
        --bg-body: #FFFFFF;
        --surface: #F9FAFB;
        --text-main: #111827;
        --text-muted: #6B7280;
        --border-color: #E5E7EB;
        --radius-md: 8px;
    }

    body {
        background-color: var(--bg-body);
        font-family: 'Inter', sans-serif; /* Pastikan font ini terload atau fallback ke sans-serif */
        color: var(--text-main);
    }

    /* Layout Wrapper */
    .wrapper {
        min-height: 100vh;
        background-color: var(--bg-body);
    }

    /* Sidebar */
    .sidebar {
        width: 260px;
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        background: var(--surface);
        border-right: 1px solid var(--border-color);
        z-index: 1030;
        display: flex;
        flex-direction: column;
    }

    .sidebar-header {
        padding: 24px;
        font-weight: 800;
        font-size: 18px;
        color: var(--primary);
        display: flex;
        align-items: center;
        gap: 10px;
        border-bottom: 1px solid var(--border-color);
        background: white;
    }

    .nav-pills { padding: 16px; }

    .nav-pills .nav-link {
        color: var(--text-muted);
        font-weight: 500;
        border-radius: var(--radius-md);
        padding: 10px 16px;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: all 0.2s;
    }

    .nav-pills .nav-link:hover {
        background-color: #fff;
        color: var(--text-main);
        border: 1px solid var(--border-color);
    }

    .nav-pills .nav-link.active {
        background-color: white;
        color: var(--primary);
        font-weight: 700;
        border: 1px solid var(--primary);
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .logout-container {
        margin-top: auto;
        padding: 24px;
        border-top: 1px solid var(--border-color);
        background: white;
    }

    /* Main Content */
    .main-content {
        margin-left: 260px;
        padding: 40px;
        min-height: 100vh;
    }

    /* Header Section */
    .welcome-header h3 {
        font-weight: 800;
        color: #111;
        letter-spacing: -0.5px;
        margin-bottom: 5px;
    }

    /* Cards */
    .card-clean {
        background: white;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        overflow: hidden;
    }
    
    .card-clean .card-header {
        background: white;
        border-bottom: 1px solid var(--border-color);
        padding: 20px 25px;
    }

    .card-clean .card-body {
        padding: 25px;
    }

    /* Forms */
    .form-label {
        font-weight: 600;
        color: var(--text-main);
        font-size: 14px;
        margin-bottom: 8px;
    }

    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid var(--border-color);
        padding: 10px 15px;
        font-size: 14px;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(166, 58, 58, 0.1);
    }

    /* Buttons */
    .btn-primary-custom {
        background: var(--primary);
        color: white;
        border: 1px solid var(--primary);
        padding: 10px 24px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.2s;
    }
    
    .btn-primary-custom:hover {
        background: var(--primary-hover);
        border-color: var(--primary-hover);
    }

    .btn-outline-custom {
        background: white;
        color: var(--text-muted);
        border: 1px solid var(--border-color);
        padding: 10px 24px;
        border-radius: 8px;
        font-weight: 600;
    }

    .btn-outline-custom:hover {
        background: #f9fafb;
        color: var(--text-main);
    }

    /* History Table */
    .history-container .table thead th {
        background: #F9FAFB;
        font-weight: 600;
        color: var(--text-muted);
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 16px 24px;
        border-bottom: 1px solid var(--border-color);
    }

    .history-container .table tbody td {
        padding: 16px 24px;
        vertical-align: middle;
        color: var(--text-main);
        font-size: 14px;
        border-bottom: 1px solid var(--border-color);
    }

    .badge-status {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
</style>

<div class="wrapper">
    
    <!-- SIDEBAR -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <i class="bi bi-heart-fill text-danger"></i> <span>Donatur<span class="text-dark">Hub</span></span>
        </div>
        
        <div class="nav flex-column nav-pills mt-2" id="v-pills-tab" role="tablist">
            <button class="nav-link active" id="pills-input-tab" data-bs-toggle="pill" data-bs-target="#pills-input" type="button" role="tab">
                <i class="bi bi-plus-circle-fill"></i> Mulai Donasi
            </button>
            <button class="nav-link" id="pills-history-tab" data-bs-toggle="pill" data-bs-target="#pills-history" type="button" role="tab">
                <i class="bi bi-clock-history"></i> Riwayat Donasi
            </button>
        </div>

        <div class="logout-container">
            <a href="#" class="btn btn-outline-danger w-100 fw-bold d-flex align-items-center justify-content-center gap-2 py-2"
               style="border-radius: var(--radius-md);" onclick="confirmLogout(event)">
                <i class="bi bi-box-arrow-right"></i> Keluar
            </a>
        </div>
    </nav>


    <!-- MAIN CONTENT -->
    <div class="main-content">
        
        <!-- Welcome Header -->
        <div class="d-flex justify-content-between align-items-center mb-5 welcome-header">
            <div>
                <h3>Halo, Donatur! 👋</h3>
                <p class="text-muted mb-0">Selamat datang kembali, terima kasih telah berbagi ilmu.</p>
            </div>
            <!-- Avatar Simple -->
            <div style="width: 48px; height: 48px; background: var(--surface); color: var(--primary); border: 1px solid var(--border-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px;">
                D
            </div>
        </div>

        <div class="tab-content" id="v-pills-tabContent">
            
            <!-- TAB 1: FORM INPUT -->
            <div class="tab-pane fade show active" id="pills-input" role="tabpanel">
                <div class="card-clean shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                            <i class="bi bi-book text-muted"></i> Form Donasi Buku
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Judul Buku</label>
                                    <input type="text" name="judul" class="form-control" required placeholder="Contoh: Laskar Pelangi">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Penulis</label>
                                    <input type="text" name="penulis" class="form-control" required placeholder="Nama Pengarang">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Penerbit</label>
                                    <input type="text" name="penerbit" class="form-control" placeholder="Nama Penerbit">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Tahun Terbit</label>
                                    <input type="number" name="tahun" class="form-control" placeholder="Tahun (Contoh: 2020)">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Kategori</label>
                                    <select name="kategori" class="form-select">
                                        <?php foreach ($categories as $cat) : ?>
                                            <option value="<?= htmlspecialchars($cat['nama']); ?>" 
                                                <?= $cat['id'] == '0' ? 'disabled selected' : ''; ?>>
                                                <?= htmlspecialchars($cat['nama']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Kondisi</label>
                                    <select name="kondisi" class="form-select">
                                        <option value="baru">Baru</option>
                                        <option value="layak" selected>Layak Baca</option>
                                        <option value="kurang_baik">Kurang Baik</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Jumlah Buku</label>
                                    <input type="number" name="jumlah" class="form-control" value="1" min="1" required>
                                </div>

                                <div class="col-md-12 mb-4">
                                    <label class="form-label">Foto Cover Buku</label>
                                    <input type="file" name="gambar" class="form-control" required>
                                    <small class="text-muted">Format: JPG, PNG, JPEG. Maksimal 2MB.</small>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2 pt-3 border-top mt-2">
                                <button type="reset" class="btn btn-outline-custom">Batal</button>
                                <button type="submit" name="simpan_donasi" class="btn btn-primary-custom">
                                    <i class="bi bi-send-fill me-2"></i> Kirim Donasi
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- TAB 2: RIWAYAT -->
            <div class="tab-pane fade" id="pills-history" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Riwayat Donasi Saya</h5>
                    <button class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                </div>
                
                <div class="card-clean shadow-sm history-container">
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th class="ps-4">Detail Buku</th>
                                    <th>Kategori</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($riwayat_buku_raw) > 0) : ?>
                                    <?php foreach ($riwayat_buku_raw as $row) : ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="assets/img/<?= htmlspecialchars($row['gambar']); ?>" 
                                                     alt="Cover" class="rounded border" width="48" height="64" style="object-fit: cover;">
                                                <div>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['judul']); ?></div>
                                                    <small class="text-muted d-block"><?= htmlspecialchars($row['penulis']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border fw-normal">
                                                <?= htmlspecialchars($row['kategori'] ?? 'Umum'); ?>
                                            </span>
                                        </td>
                                        <td class="text-muted small">
                                            <?= isset($row['created_at']) ? date('d M Y', strtotime($row['created_at'])) : '-'; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $st = htmlspecialchars($row['status_donasi']);
                                            if($st == 'Pending') echo '<span class="badge-status bg-warning bg-opacity-10 text-warning border border-warning">Menunggu</span>';
                                            elseif($st == 'Diterima') echo '<span class="badge-status bg-success bg-opacity-10 text-success border border-success">Diterima</span>';
                                            else echo '<span class="badge-status bg-danger bg-opacity-10 text-danger border border-danger">Ditolak</span>';
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                                            Belum ada riwayat donasi.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<!-- SCRIPTS -->
<script>
    // 1. SweetAlert Notifikasi
    <?php if (isset($_SESSION['flash_success'])): ?>
        Swal.fire({
            icon: 'success',
            title: 'Terima Kasih!',
            text: <?= json_encode($_SESSION['flash_success']) ?>,
            showConfirmButton: true,
            confirmButtonColor: '#A63A3A',
            timer: 4000
        });
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: <?= json_encode($_SESSION['flash_error']) ?>,
            confirmButtonColor: '#A63A3A'
        });
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    // 2. Logout Confirmation
    function confirmLogout(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Keluar dari Donatur area?',
            text: "Sesi Anda akan diakhiri.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#A63A3A',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Ya, Keluar',
            cancelButtonText: 'Batal',
            customClass: {
                popup: 'rounded-4 shadow-lg border-0',
                confirmButton: 'btn btn-danger px-4 rounded-3',
                cancelButton: 'btn btn-light px-4 rounded-3 text-muted border-0'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'index.php?logout=true';
            }
        })
    }
</script>