<?php
// Pastikan Anda telah menginisialisasi $pdo (koneksi database) dan sesi (misalnya $_SESSION['user_id']) di file utama Anda.

// --- DAFTAR KATEGORI LENGKAP (Sesuai ENUM database) ---
$all_categories_filter = ['Fiksi', 'Pendidikan', 'Non-Fiksi', 'Komik', 'Teknologi', 'Sejarah'];

// --- LOGIKA 1: PROSES PENGAJUAN BUKU ---
if (isset($_POST['ajukan_buku_modal'])) {
    $id_penerima = $_SESSION['user_id'];
    $id_buku = $_POST['id_buku'];
    $metode = $_POST['metode_pengambilan'];
    $alamat = ($metode == 'Diantar') ? $_POST['alamat_pengantaran'] : NULL;

    $stmt_buku = $pdo->prepare("SELECT id_user FROM buku WHERE id = ?");
    $stmt_buku->execute([$id_buku]);
    $data_buku = $stmt_buku->fetch();
    $id_donatur = $data_buku['id_user'];

    $cek = $pdo->prepare("SELECT id FROM transaksi_donasi WHERE penerima_id = ? AND buku_id = ? AND status = 'dalam_proses'");
    $cek->execute([$id_penerima, $id_buku]);

    if ($cek->rowCount() > 0) {
        $_SESSION['flash_error'] = "Anda sudah mengajukan buku ini, mohon tunggu verifikasi.";
    } else {
        $sql = "INSERT INTO transaksi_donasi (donatur_id, buku_id, penerima_id, tanggal_donor, jumlah_buku, status, metode_pengambilan, alamat_pengantaran, created_at) 
                 VALUES (?, ?, ?, NOW(), 1, 'dalam_proses', ?, ?, NOW())";

        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$id_donatur, $id_buku, $id_penerima, $metode, $alamat])) {
            $_SESSION['flash_success'] = "Buku berhasil diajukan! Menunggu verifikasi.";
        }
    }
    echo "<script>window.location='index.php?page=area_penerima';</script>";
    exit;
}

// --- LOGIKA 2: UBAH METODE ---
if (isset($_POST['ubah_metode_modal'])) {
    $id_transaksi_ubah = $_POST['id_transaksi_ubah'];
    $metode_baru = $_POST['metode_pengambilan_ubah'];
    $alamat_baru = ($metode_baru == 'Diantar') ? $_POST['alamat_pengantaran_ubah'] : NULL;

    $sql_update = "UPDATE transaksi_donasi 
                    SET metode_pengambilan = ?, alamat_pengantaran = ? 
                    WHERE id = ? AND penerima_id = ? AND status = 'dalam_proses'";

    $stmt_update = $pdo->prepare($sql_update);
    if ($stmt_update->execute([$metode_baru, $alamat_baru, $id_transaksi_ubah, $_SESSION['user_id']])) {
        $_SESSION['flash_success'] = "Metode pengambilan berhasil diperbarui!";
    } else {
        $_SESSION['flash_error'] = "Gagal mengubah metode. Pastikan status masih 'Dalam Proses'.";
    }
    echo "<script>window.location='index.php?page=area_penerima#pills-riwayat';</script>";
    exit;
}


// --- LOGIKA 3: LOAD DATA KATALOG ---
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : '';

$sql = "SELECT * FROM buku WHERE status_donasi = 'Diterima' AND stok > 0";
$params = [];

if (!empty($search_query)) {
    $sql .= " AND (judul LIKE ? OR penulis LIKE ?)";
    $search_param = "%" . $search_query . "%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($kategori_filter) && in_array($kategori_filter, $all_categories_filter)) {
    $sql .= " AND kategori = ?";
    $params[] = $kategori_filter;
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$katalog_buku = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- LOGIKA 4: RIWAYAT SAYA ---
$id_user = $_SESSION['user_id'];
$query_history = "SELECT t.*, b.judul, b.gambar, b.penulis, u_donatur.no_wa AS telepon_donatur, u_donatur.username AS nama_donatur
                    FROM transaksi_donasi t 
                    JOIN buku b ON t.buku_id = b.id 
                    JOIN users u_donatur ON t.donatur_id = u_donatur.id 
                    WHERE t.penerima_id = ? 
                    ORDER BY t.id DESC";
$stmt = $pdo->prepare($query_history);
$stmt->execute([$id_user]);
$riwayat_transaksi = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- STYLE KHUSUS AREA PENERIMA (Flat Clean Design) -->
<style>
    :root {
        --primary: #A63A3A;
        --primary-hover: #8F3030;
        --bg-body: #FFFFFF;
        /* Pure White Body for Clean Look */
        --surface: #F9FAFB;
        /* Light Gray for Sidebar/Containers */
        --text-main: #111827;
        --text-muted: #6B7280;
        --border-color: #E5E7EB;
        --radius-md: 8px;
        /* Slightly squarer for clean look */
    }

    body {
        background-color: var(--bg-body);
        font-family: 'Inter', sans-serif;
        color: var(--text-main);
    }

    /* Layout */
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
        /* Higher than sticky header */
        display: flex;
        flex-direction: column;
    }

    /* Main Content */
    .main-content {
        margin-left: 260px;
        padding: 40px;
        min-height: 100vh;
        width: auto;
        /* Let it fill the remaining space */
    }

    /* ... Sidebar Styles ... */
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

    .nav-pills {
        padding: 16px;
    }

    .nav-pills .nav-link {
        color: var(--text-muted);
        font-weight: 500;
        border-radius: var(--radius-md);
        padding: 10px 16px;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 12px;
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

    /* Header */
    .welcome-header h3 {
        font-weight: 800;
        color: #111;
        letter-spacing: -0.5px;
    }

    /* Input & Buttons (Refined) */
    .form-control-search {
        border-radius: 50px;
        /* Rounder for modern feel */
        padding-left: 20px;
        border: 1px solid var(--border-color);
        height: 48px;
        box-shadow: none !important;
        background: white;
    }

    .form-control-search:focus {
        border-color: var(--primary);
    }

    .btn-primary-custom {
        background: var(--primary);
        color: white;
        border: 1px solid var(--primary);
        padding: 10px 24px;
        border-radius: 20px;
        font-weight: 600;
        box-shadow: none;
    }

    .btn-primary-custom:hover {
        background: var(--primary-hover);
        border-color: var(--primary-hover);
    }

    /* Filters (Refined) */
    .filter-pill {
        border: 1px solid var(--border-color);
        color: var(--text-muted);
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        background: white;
        transition: all 0.2s;
    }

    .filter-pill:hover {
        background: #f3f4f6;
        color: var(--text-main);
        border-color: #d1d5db;
    }

    .filter-pill.active {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }

    /* Book Card (Better Proportion) */
    .book-card {
        background: white;
        border-radius: var(--radius-md);
        border: 1px solid var(--border-color);
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .book-card:hover {
        border-color: var(--primary);
    }

    .book-img-wrap {
        position: relative;
        padding-top: 140%;
        /* 5:7 Aspect Ratio for Books */
        background: #f3f4f6;
        border-bottom: 1px solid var(--border-color);
        overflow: hidden;
    }

    .book-img-wrap img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .book-bd {
        padding: 16px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .book-cat {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 4px;
    }

    .book-ttl {
        font-weight: 700;
        font-size: 15px;
        color: #111;
        margin-bottom: 4px;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .book-aut {
        font-size: 13px;
        color: var(--text-muted);
        margin-bottom: 12px;
    }

    .book-ft {
        margin-top: auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 12px;
        border-top: 1px solid var(--border-color);
    }

    /* Sticky Header (Solid White, No Blur) */
    .sticky-header-custom {
        position: sticky;
        top: 0;
        z-index: 1020;
        padding: 20px 0;
        background-color: white;
        border-bottom: 1px solid var(--border-color);
        margin-bottom: 24px;
        /* Width adjustment matches padding */
        margin-left: -40px;
        margin-right: -40px;
        padding-left: 40px;
        padding-right: 40px;
    }

    /* Request History Table (Clean) */
    .history-card {
        background: white;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        overflow: hidden;
    }

    .table-custom {
        margin-bottom: 0;
    }

    .table-custom thead th {
        background: #F9FAFB;
        font-weight: 600;
        color: var(--text-muted);
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 16px 24px;
        border-bottom: 1px solid var(--border-color);
        border-top: none;
    }

    .table-custom tbody td {
        padding: 16px 24px;
        vertical-align: middle;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-main);
        font-size: 14px;
    }

    .table-custom tbody tr:last-child td {
        border-bottom: none;
    }

    .badge-status {
        font-weight: 500;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        border: 1px solid transparent;
    }
</style>

<div class="wrapper">

    <!-- SIDEBAR -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <i class="bi bi-book-half text-danger"></i> <span>Pustaka<span class="text-dark">Kita</span></span>
        </div>

        <div class="nav flex-column nav-pills mt-2" id="v-pills-tab" role="tablist">
            <button class="nav-link active" id="pills-katalog-tab" data-bs-toggle="pill" data-bs-target="#pills-katalog"
                type="button">
                <i class="bi bi-grid-fill"></i> Katalog Buku
            </button>
            <button class="nav-link" id="pills-riwayat-tab" data-bs-toggle="pill" data-bs-target="#pills-riwayat"
                type="button">
                <i class="bi bi-clock-history"></i> Riwayat Request
            </button>
        </div>

        <div class="logout-container">
            <a href="#"
                class="btn btn-outline-danger w-100 fw-bold d-flex align-items-center justify-content-center gap-2 py-2"
                style="border-radius: var(--radius-md);" onclick="confirmLogout(event)">
                <i class="bi bi-box-arrow-right"></i> Keluar
            </a>
        </div>
    </nav>


    <!-- MAIN CONTENT -->



    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- Welcome Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 welcome-header">
            <div>
                <h3>Halo, Pencari Ilmu! 👋</h3>
                <p class="text-muted mb-0">Selamat datang kembali di area penerima buku.</p>
            </div>

        </div>

        <div class="tab-content" id="v-pills-tabContent">

            <!-- 1. TAB KATALOG -->
            <div class="tab-pane fade show active" id="pills-katalog">

                <!-- Search & Filter Bar (Sticky Solid) -->
                <div class="sticky-header-custom">
                    <form method="GET" action="index.php" class="position-relative mb-3">
                        <input type="hidden" name="page" value="area_penerima">
                        <input type="text" name="search" class="form-control form-control-search"
                            placeholder="Cari judul buku atau penulis..."
                            value="<?= htmlspecialchars($search_query) ?>">
                        <button type="submit" class="btn btn-primary-custom position-absolute top-0 end-0 m-1">
                            Cari
                        </button>
                    </form>

                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="text-muted small fw-bold me-2 text-uppercase"
                            style="font-size: 11px;">Kategori:</span>
                        <a href="index.php?page=area_penerima&search=<?= htmlspecialchars($search_query) ?>"
                            class="filter-pill <?= empty($kategori_filter) ? 'active' : '' ?>">
                            Semua
                        </a>
                        <?php foreach ($all_categories_filter as $kat): ?>
                            <a href="index.php?page=area_penerima&kategori=<?= urlencode($kat) ?>&search=<?= htmlspecialchars($search_query) ?>"
                                class="filter-pill <?= ($kategori_filter === $kat) ? 'active' : '' ?>">
                                <?= htmlspecialchars($kat) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Grid Buku -->
                <div class="row g-4">
                    <?php if (count($katalog_buku) > 0): ?>
                        <?php foreach ($katalog_buku as $b): ?>
                            <div class="col-xl-3 col-lg-4 col-sm-6">
                                <div class="book-card">
                                    <div class="book-img-wrap">
                                        <img src="assets/img/<?= htmlspecialchars($b['gambar']) ?>"
                                            alt="<?= htmlspecialchars($b['judul']) ?>">
                                    </div>
                                    <div class="book-bd">
                                        <div class="book-cat"><?= htmlspecialchars($b['kategori']) ?></div>
                                        <h5 class="book-ttl" title="<?= htmlspecialchars($b['judul']) ?>">
                                            <?= htmlspecialchars($b['judul']) ?>
                                        </h5>
                                        <div class="book-aut"><?= htmlspecialchars($b['penulis']) ?></div>

                                        <div class="book-ft">
                                            <div class="d-flex align-items-center gap-1 text-muted small">
                                                <i class="bi bi-box-seam"></i> <?= $b['stok'] ?> Tersedia
                                            </div>
                                            <!-- Pemicu Modal -->
                                            <button class="btn btn-sm btn-light border fw-bold text-primary"
                                                data-bs-toggle="modal" data-bs-target="#modalRequestBuku<?= $b['id'] ?>">
                                                Ajukan
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-center py-5">
                            <div class="mb-3 opacity-25">
                                <i class="bi bi-search" style="font-size: 4rem;"></i>
                            </div>
                            <h5 class="text-muted fw-bold">Tidak ada buku ditemukan</h5>
                            <p class="text-muted small">Silakan coba kata kunci lain.</p>
                            <a href="index.php?page=area_penerima" class="btn btn-primary-custom btn-sm mt-3 px-4">Reset
                                Filter</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 2. TAB RIWAYAT -->
            <div class="tab-pane fade" id="pills-riwayat">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="fw-bold mb-0">Riwayat Permintaan</h5>
                    <button class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                </div>

                <div class="history-card">
                    <div class="table-responsive">
                        <table class="table custom-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 40%;">Detail Buku</th>
                                    <th>Tanggal</th>
                                    <th>Metode</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($riwayat_transaksi) > 0): ?>
                                    <?php foreach ($riwayat_transaksi as $rw): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-start gap-3">
                                                    <img src="assets/img/<?= htmlspecialchars($rw['gambar']); ?>"
                                                        class="rounded border" width="45" height="60"
                                                        style="object-fit: cover;">
                                                    <div>
                                                        <div class="fw-bold text-dark text-truncate" style="max-width: 250px;">
                                                            <?= htmlspecialchars($rw['judul']); ?>
                                                        </div>
                                                        <div class="text-muted small"><?= htmlspecialchars($rw['penulis']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-muted"><?= date('d M Y', strtotime($rw['created_at'])); ?></td>

                                            <td>
                                                <?php $m = $rw['metode_pengambilan']; ?>
                                                <span
                                                    class="badge-status <?= $m == 'Diantar' ? 'bg-info bg-opacity-10 text-info border-info' : 'bg-gray bg-opacity-10 text-dark border-secondary' ?>"
                                                    style="border-style: dashed;">
                                                    <?= $m ?: '-' ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?php
                                                if ($rw['status'] == 'dalam_proses') {
                                                    echo '<span class="badge-status bg-warning bg-opacity-10 text-warning border-warning">Menunggu Konfirmasi</span>';
                                                } elseif ($rw['status'] == 'diterima') {
                                                    echo '<span class="badge-status bg-success bg-opacity-10 text-success border-success">Disetujui</span>';
                                                } elseif ($rw['status'] == 'selesai') {
                                                    echo '<span class="badge-status bg-secondary bg-opacity-10 text-secondary border-secondary">Selesai</span>';
                                                } elseif ($rw['status'] == 'ditolak') {
                                                    echo '<span class="badge-status bg-danger bg-opacity-10 text-danger border-danger">Ditolak</span>';
                                                }
                                                ?>
                                            </td>

                                            <td class="text-end">
                                                <?php if ($rw['status'] == 'dalam_proses'): ?>
                                                    <button class="btn btn-sm btn-outline-dark rounded-pill px-3"
                                                        data-bs-toggle="modal" data-bs-target="#modalUbahMetode"
                                                        data-id="<?= $rw['id'] ?>"
                                                        data-metode="<?= htmlspecialchars($rw['metode_pengambilan']) ?>"
                                                        data-alamat="<?= htmlspecialchars($rw['alamat_pengantaran']) ?>">
                                                        Ubah
                                                    </button>
                                                <?php elseif ($rw['status'] == 'diterima' && !empty($rw['telepon_donatur'])):
                                                    $nomor_hp = preg_replace('/^08/', '628', $rw['telepon_donatur']);
                                                    $pesan = "Halo " . htmlspecialchars($rw['nama_donatur']) . ", saya penerima buku " . htmlspecialchars($rw['judul']);
                                                    $link_wa = "https://wa.me/{$nomor_hp}?text=" . urlencode($pesan);
                                                    ?>
                                                    <a href="<?= $link_wa ?>" target="_blank"
                                                        class="btn btn-sm btn-success rounded-pill px-3">
                                                        <i class="bi bi-whatsapp me-1"></i> WA Donatur
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted small">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="bi bi-clipboard-x mb-2 d-block" style="font-size: 24px;"></i>
                                            Belum ada riwayat permintaan.
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

<!-- Render Modals OUTSIDE the grid loop to prevent z-index issues -->
<?php if (count($katalog_buku) > 0): ?>
    <?php foreach ($katalog_buku as $b): ?>
        <!-- Modal Request for Book ID <?= $b['id'] ?> -->
        <div class="modal fade" id="modalRequestBuku<?= $b['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 rounded-3">
                    <form method="POST">
                        <input type="hidden" name="id_buku" value="<?= $b['id'] ?>">
                        <div class="modal-header border-bottom">
                            <h5 class="modal-title fw-bold" style="font-size: 16px;">Ajukan Buku</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="d-flex gap-3 mb-4 align-items-center">
                                <img src="assets/img/<?= htmlspecialchars($b['gambar']) ?>" class="rounded border" width="50"
                                    height="70" style="object-fit: cover;">
                                <div>
                                    <h6 class="fw-bold mb-1" style="font-size: 15px;"><?= htmlspecialchars($b['judul']) ?></h6>
                                    <p class="text-muted small mb-0"><?= htmlspecialchars($b['penulis']) ?></p>
                                </div>
                            </div>

                            <p class="small fw-bold text-muted text-uppercase mb-2">Pilih Metode Pengambilan</p>
                            <div class="d-grid gap-2">
                                <label
                                    class="btn btn-outline-secondary text-start border p-3 rounded-2 d-flex align-items-center gap-2">
                                    <input type="radio" name="metode_pengambilan" value="Diambil" checked
                                        class="form-check-input mt-0" onclick="toggleAlamat(false, <?= $b['id'] ?>)">
                                    <span style="font-size: 14px;">Ambil Sendiri ke Lokasi Donatur</span>
                                </label>
                                <label
                                    class="btn btn-outline-secondary text-start border p-3 rounded-2 d-flex align-items-center gap-2">
                                    <input type="radio" name="metode_pengambilan" value="Diantar" class="form-check-input mt-0"
                                        onclick="toggleAlamat(true, <?= $b['id'] ?>)">
                                    <span style="font-size: 14px;">Diantar ke Alamat Saya</span>
                                </label>
                            </div>

                            <div class="mt-3" id="alamatContainer<?= $b['id'] ?>" style="display: none;">
                                <label class="form-label small fw-bold">Alamat Lengkap</label>
                                <textarea class="form-control bg-light" name="alamat_pengantaran" rows="3"
                                    placeholder="Jalan, Nomor rumah, RT/RW..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer border-top bg-light">
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="ajukan_buku_modal" class="btn btn-primary-custom btn-sm px-4">Kirim
                                Pengajuan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Modal Ubah Metode (Global) -->
<div class="modal fade" id="modalUbahMetode" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-3">
            <form method="POST">
                <input type="hidden" name="id_transaksi_ubah" id="id_transaksi_ubah">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold" style="font-size: 16px;">Ubah Metode</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-grid gap-2">
                        <label
                            class="btn btn-outline-secondary text-start border p-3 rounded-2 d-flex align-items-center gap-2">
                            <input type="radio" name="metode_pengambilan_ubah" value="Diambil" id="radioAmbilUbah"
                                class="form-check-input mt-0">
                            <span style="font-size: 14px;">Ambil Sendiri</span>
                        </label>
                        <label
                            class="btn btn-outline-secondary text-start border p-3 rounded-2 d-flex align-items-center gap-2">
                            <input type="radio" name="metode_pengambilan_ubah" value="Diantar" id="radioAntarUbah"
                                class="form-check-input mt-0">
                            <span style="font-size: 14px;">Diantar</span>
                        </label>
                    </div>
                    <div class="mt-3" id="alamatContainerUbah" style="display: none;">
                        <textarea class="form-control bg-light" name="alamat_pengantaran_ubah" id="alamatAntarUbah"
                            rows="3" placeholder="Alamat baru..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="submit" name="ubah_metode_modal" class="btn btn-primary-custom w-100">Simpan
                        Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SCRIPTS & SWEETALERT -->
<!-- SweetAlert2 CDN (Wajib ada jika belum di-include di header utama, tapi karena ini file view parsial, asumsi header.php sudah ada. Jika belum, uncomment baris bawah) -->
<!-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> -->

<script>
    // 1. SweetAlert Notifikasi
    <?php if (isset($_SESSION['flash_success'])): ?>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: <?= json_encode($_SESSION['flash_success']) ?>,
            showConfirmButton: true,
            confirmButtonColor: '#A63A3A',
            confirmButtonText: 'Oke, Siap!',
            timer: 4000,
            timerProgressBar: true
        });
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: <?= json_encode($_SESSION['flash_error']) ?>,
            showConfirmButton: true,
            confirmButtonColor: '#A63A3A'
        });
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    // 2. Logic Toggle Alamat
    function toggleAlamat(show, id) {
        const container = document.getElementById('alamatContainer' + id);
        const input = container.querySelector('textarea');
        if (show) {
            container.style.display = 'block';
            input.setAttribute('required', 'true');
        } else {
            container.style.display = 'none';
            input.removeAttribute('required');
        }
    }

    // 3. Logic Modal Ubah Metode
    document.addEventListener('DOMContentLoaded', function () {
        const modalUbah = document.getElementById('modalUbahMetode');
        const radioAmbil = document.getElementById('radioAmbilUbah');
        const radioAntar = document.getElementById('radioAntarUbah');
        const container = document.getElementById('alamatContainerUbah');
        const inputAlamat = document.getElementById('alamatAntarUbah');

        if (modalUbah) {
            modalUbah.addEventListener('show.bs.modal', function (e) {
                const btn = e.relatedTarget;
                document.getElementById('id_transaksi_ubah').value = btn.dataset.id;
                const metode = btn.dataset.metode;

                if (metode === 'Diantar') {
                    radioAntar.checked = true;
                    container.style.display = 'block';
                    inputAlamat.value = btn.dataset.alamat;
                } else {
                    radioAmbil.checked = true;
                    container.style.display = 'none';
                }
            });

            // Event Listeners for Radio Change
            radioAmbil.addEventListener('change', () => { container.style.display = 'none'; inputAlamat.removeAttribute('required'); });
            radioAntar.addEventListener('change', () => { container.style.display = 'block'; inputAlamat.setAttribute('required', 'true'); });
        }
    });

    // 4. Logout Confirmation (Global)
    function confirmLogout(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Yakin keluar?',
            text: "Sesi Anda akan diakhiri.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#A63A3A',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Keluar!',
            cancelButtonText: 'Batal',
            customClass: {
                popup: 'rounded-4 shadow-lg border-0',
                confirmButton: 'btn btn-danger px-4 rounded-3',
                cancelButton: 'btn btn-light px-4 rounded-3 text-muted border-0'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'index.php?logout=true';
            }
        })
    }
</script>