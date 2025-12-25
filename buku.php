<?php
/**
 * File: views/buku.php
 * Halaman Manajemen Buku (Refined: Consistent Icons + Status Filter)
 */

// --- 1. SETUP & UTILS ---
$all_categories = ['Pendidikan', 'Fiksi', 'Non-Fiksi', 'Komik', 'Teknologi', 'Sejarah', 'Lainnya'];
$kategori_valid = array_merge(['Semua'], $all_categories);

// Filter Parameters
$search_query = isset($_GET['cari']) ? trim($_GET['cari']) : '';
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : 'Semua';
if (!in_array($kategori_filter, $kategori_valid)) $kategori_filter = 'Semua';

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'Semua';
$valid_status = ['Semua', 'Pending', 'Diterima', 'Ditolak'];
if (!in_array($status_filter, $valid_status)) $status_filter = 'Semua';


// --- 2. LOGIKA CRUD (POST HANDLERS) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['aksi']) && ($_POST['aksi'] == 'tambah' || $_POST['aksi'] == 'edit')) {
            $aksi = $_POST['aksi'];
            $id = $_POST['id'] ?? null;
            $judul = trim($_POST['judul']);
            $penulis = trim($_POST['penulis']);
            $penerbit = trim($_POST['penerbit']);
            $tahun = $_POST['tahun'];
            $kategori = $_POST['kategori'];
            $kondisi = $_POST['kondisi'];
            $jumlah = $_POST['jumlah'];
            $stok = $_POST['stok'];
            
            // Upload Gambar
            $gambar = ($aksi == 'edit') ? $_POST['gambar_lama'] : 'default.jpg';
            if (!empty($_FILES['gambar']['name'])) {
                $target_dir = "assets/img/";
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                
                $ekstensi = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
                $nama_baru = time() . '.' . $ekstensi;
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target_dir . $nama_baru)) {
                    $gambar = $nama_baru;
                }
            }

            if ($aksi == 'tambah') {
                $sql = "INSERT INTO buku (id_user, judul, penulis, penerbit, tahun_terbit, kategori, kondisi, jumlah, gambar, isbn, stok, status_donasi, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Diterima', NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$_SESSION['user_id'], $judul, $penulis, $penerbit, $tahun, $kategori, $kondisi, $jumlah, $gambar, '-', $stok]);
                $_SESSION['flash_success'] = "Buku <b>" . htmlspecialchars($judul) . "</b> berhasil ditambahkan!";
            
            } elseif ($aksi == 'edit') {
                $sql = "UPDATE buku SET judul=?, penulis=?, penerbit=?, tahun_terbit=?, kategori=?, kondisi=?, jumlah=?, stok=?, gambar=? WHERE id=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$judul, $penulis, $penerbit, $tahun, $kategori, $kondisi, $jumlah, $stok, $gambar, $id]);
                $_SESSION['flash_success'] = "Data buku <b>" . htmlspecialchars($judul) . "</b> berhasil diperbarui!";
            }
            
            echo "<script>window.location='index.php?page=buku';</script>";
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['flash_error'] = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// --- 3. LOGIKA AKSI GET (HAPUS/TERIMA/TOLAK) ---
if (isset($_GET['act']) || isset($_GET['aksi'])) {
    
    // Verifikasi (Terima/Tolak)
    if (isset($_GET['act']) && isset($_GET['id'])) {
        $id_buku = $_GET['id'];
        $act = $_GET['act'];
        $status_baru = ($act == 'terima') ? 'Diterima' : 'Ditolak';
        
        $stmt = $pdo->prepare("UPDATE buku SET status_donasi = ? WHERE id = ?");
        $stmt->execute([$status_baru, $id_buku]);
        $_SESSION['flash_success'] = "Status donasi berhasil diubah menjadi: <b>$status_baru</b>";
        echo "<script>window.location='index.php?page=buku';</script>";
        exit;
    }

    // Hapus
    if (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus') {
        $id = $_GET['id'];
        $stmt = $pdo->prepare("DELETE FROM buku WHERE id=?");
        $stmt->execute([$id]);
        $_SESSION['flash_success'] = "Data buku berhasil dihapus permanen.";
        echo "<script>window.location='index.php?page=buku';</script>";
        exit;
    }
}

// --- 4. DATA PREPARATION ---

// Ambil data untuk Edit
$edit_id = isset($_GET['aksi']) && $_GET['aksi'] == 'edit' ? $_GET['id'] : null;
$row_edit = null;
if ($edit_id) {
    $stmt = $pdo->prepare("SELECT * FROM buku WHERE id = ?");
    $stmt->execute([$edit_id]);
    $row_edit = $stmt->fetch();
}

// Query Tampil Data (Search & Filter)
$sql = "SELECT buku.*, users.username FROM buku LEFT JOIN users ON buku.id_user = users.id WHERE 1=1 ";
$params = [];

// 1. Search
if (!empty($search_query)) {
    $search_param = "%" . $search_query . "%";
    $sql .= " AND (buku.judul LIKE ? OR buku.penulis LIKE ? OR users.username LIKE ?) ";
    array_push($params, $search_param, $search_param, $search_param);
}
// 2. Filter Kategori
if ($kategori_filter !== 'Semua') {
    $sql .= " AND buku.kategori = ? ";
    $params[] = $kategori_filter;
}
// 3. Filter Status
if ($status_filter !== 'Semua') {
    $sql .= " AND buku.status_donasi = ? ";
    $params[] = $status_filter;
}

$sql .= " ORDER BY buku.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$buku = $stmt->fetchAll(PDO::FETCH_ASSOC);

// View Mode
$form_tambah = (isset($_GET['aksi']) && $_GET['aksi'] == 'form');
$form_edit = ($edit_id && !$form_tambah);
?>

<div class="container-fluid px-0">

    <!-- HEADER SECTION -->
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h5 class="text-muted small fw-bold text-uppercase mb-1" style="letter-spacing: 1px;">Inventaris</h5>
            <h2 class="font-heading fw-bold mb-0">Manajemen Buku</h2>
        </div>
        <?php if (!$form_tambah && !$form_edit): ?>
            <a href="index.php?page=buku&aksi=form" class="btn btn-primary-custom shadow-sm d-flex align-items-center gap-2 px-4">
                <i class="bi bi-plus-lg text-xs"></i>
                <span>Tambah Manual</span>
            </a>
        <?php endif; ?>
    </div>

    <!-- CONTENT: FORM OR TABLE -->
    <?php if ($form_tambah || $form_edit): ?>
        
        <!-- FORM MODIFICATION -->
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card border-0 shadow-card">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold"><?= $form_edit ? 'Edit Data Buku' : 'Tambah Buku Baru' ?></h6>
                    </div>
                    <div class="card-body p-4">
                        <form method="post" enctype="multipart/form-data" action="index.php?page=buku">
                            <input type="hidden" name="aksi" value="<?= $form_edit ? 'edit' : 'tambah' ?>">
                            <?php if ($form_edit): ?>
                                <input type="hidden" name="id" value="<?= htmlspecialchars($row_edit['id']) ?>">
                                <input type="hidden" name="gambar_lama" value="<?= htmlspecialchars($row_edit['gambar']) ?>">
                            <?php endif; ?>

                            <div class="row g-4">
                                <div class="col-md-8">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label small fw-bold text-muted text-uppercase">Judul Buku</label>
                                            <input type="text" class="form-control" name="judul" value="<?= $form_edit ? htmlspecialchars($row_edit['judul']) : '' ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold text-muted text-uppercase">Penulis</label>
                                            <input type="text" class="form-control" name="penulis" value="<?= $form_edit ? htmlspecialchars($row_edit['penulis']) : '' ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold text-muted text-uppercase">Penerbit</label>
                                            <input type="text" class="form-control" name="penerbit" value="<?= $form_edit ? htmlspecialchars($row_edit['penerbit']) : '' ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold text-muted text-uppercase">Tahun</label>
                                            <input type="number" class="form-control" name="tahun" value="<?= $form_edit ? htmlspecialchars($row_edit['tahun_terbit']) : '' ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold text-muted text-uppercase">Kategori</label>
                                            <select name="kategori" class="form-select" required>
                                                <option value="" disabled selected>Pilih...</option>
                                                <?php foreach ($all_categories as $cat): ?>
                                                    <option value="<?= htmlspecialchars($cat) ?>" <?= ($form_edit && $row_edit['kategori'] == $cat) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($cat) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold text-muted text-uppercase">Kondisi</label>
                                            <select name="kondisi" class="form-select" required>
                                                <option value="baru" <?= ($form_edit && $row_edit['kondisi'] == 'baru') ? 'selected' : '' ?>>Baru</option>
                                                <option value="layak" <?= ($form_edit && $row_edit['kondisi'] == 'layak') ? 'selected' : '' ?>>Layak</option>
                                                <option value="kurang_baik" <?= ($form_edit && $row_edit['kondisi'] == 'kurang_baik') ? 'selected' : '' ?>>Kurang Baik</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="p-3 bg-light rounded border">
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold text-muted text-uppercase">Cover Buku</label>
                                            <input type="file" class="form-control form-control-sm mb-2" name="gambar">
                                            <?php if ($form_edit && $row_edit['gambar'] && $row_edit['gambar'] != 'default.jpg'): ?>
                                                <div class="rounded overflow-hidden shadow-sm mt-2 border">
                                                    <img src="assets/img/<?= htmlspecialchars($row_edit['gambar']) ?>" class="w-100 object-fit-cover" style="height: 200px;">
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <label class="form-label small fw-bold text-muted text-uppercase">Stok</label>
                                            <input type="number" class="form-control" name="stok" value="<?= $form_edit ? htmlspecialchars($row_edit['stok']) : '1' ?>" required>
                                            <input type="hidden" name="jumlah" value="<?= $form_edit ? htmlspecialchars($row_edit['jumlah']) : '1' ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-between">
                                <a href="index.php?page=buku" class="btn btn-outline-secondary px-4">Batal</a>
                                <button type="submit" class="btn btn-primary-custom px-4 shadow-sm">
                                    <i class="bi bi-save me-2"></i>Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>

        <!-- TABLE VIEW -->
        <div class="card border-0 shadow-card">
            
            <!-- Toolbar -->
            <div class="card-header bg-white border-bottom py-3">
                <form method="get" action="index.php" class="row g-2 align-items-center">
                    <input type="hidden" name="page" value="buku">
                    
                    <!-- Search -->
                    <div class="col-12 col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" name="cari" class="form-control border-start-0 ps-0" placeholder="Cari Judul, Penulis..." value="<?= htmlspecialchars($search_query) ?>">
                        </div>
                    </div>

                    <!-- Filter Kategori -->
                    <div class="col-6 col-md-2">
                        <select class="form-select" name="kategori" onchange="this.form.submit()">
                            <option value="Semua" <?= $kategori_filter == 'Semua' ? 'selected' : '' ?>>Semua Kategori</option>
                            <?php foreach ($all_categories as $kat): ?>
                                <option value="<?= htmlspecialchars($kat) ?>" <?= $kategori_filter == $kat ? 'selected' : '' ?>><?= htmlspecialchars($kat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Filter Status (NEW) -->
                    <div class="col-6 col-md-2">
                        <select class="form-select" name="status" onchange="this.form.submit()">
                            <option value="Semua" <?= $status_filter == 'Semua' ? 'selected' : '' ?>>Semua Status</option>
                            <option value="Pending" <?= $status_filter == 'Pending' ? 'selected' : '' ?>>Menunggu</option>
                            <option value="Diterima" <?= $status_filter == 'Diterima' ? 'selected' : '' ?>>Aktif</option>
                            <option value="Ditolak" <?= $status_filter == 'Ditolak' ? 'selected' : '' ?>>Ditolak</option>
                        </select>
                    </div>

                    <!-- Reset -->
                    <div class="col-12 col-md-3 text-md-end">
                         <?php if (!empty($search_query) || $kategori_filter !== 'Semua' || $status_filter !== 'Semua'): ?>
                            <a href="index.php?page=buku" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-x-circle me-1"></i>Reset Filter
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width: 5%">No</th>
                            <th style="width: 35%">Informasi Buku</th>
                            <th style="width: 15%">Kategori</th>
                            <th style="width: 15%">Donatur</th>
                            <th style="width: 10%">Stok</th>
                            <th style="width: 10%">Status</th>
                            <th class="text-end pe-4" style="width: 10%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; if ($buku): foreach ($buku as $b): ?>
                            <tr>
                                <td class="ps-4 text-muted small"><?= $no++ ?></td>
                                <td>
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="rounded-2 shadow-sm border overflow-hidden flex-shrink-0" style="width: 50px; height: 75px;">
                                            <img src="assets/img/<?= htmlspecialchars($b['gambar']) ?>" class="w-100 h-100 object-fit-cover">
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark text-truncate" style="max-width: 250px;"><?= htmlspecialchars($b['judul']) ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($b['penulis']) ?></div>
                                            <span class="text-muted small" style="font-size: 0.75rem;"><?= htmlspecialchars($b['tahun_terbit']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border fw-medium px-2 py-1 user-select-none">
                                        <?= htmlspecialchars($b['kategori'] ?: '-') ?>
                                    </span>
                                </td>
                                
                                <!-- STANDARDIZED DONATUR ICON -->
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if ($b['username']): ?>
                                            <!-- User Avatar (Gray) -->
                                            <div class="rounded-circle bg-light d-flex justify-content-center align-items-center text-muted small border flex-shrink-0" style="width: 28px; height: 28px;">
                                                <i class="bi bi-person-fill"></i>
                                            </div>
                                            <span class="text-dark small fw-medium"><?= htmlspecialchars($b['username']) ?></span>
                                        <?php else: ?>
                                            <!-- Admin Avatar (Brand Color) -->
                                            <div class="rounded-circle bg-primary-subtle d-flex justify-content-center align-items-center text-primary small border border-primary-subtle flex-shrink-0" style="width: 28px; height: 28px;">
                                                <i class="bi bi-person-fill-gear"></i>
                                            </div>
                                            <span class="text-primary small fw-bold">Admin</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                
                                <td>
                                    <span class="fw-bold text-dark"><?= $b['stok'] ?></span>
                                </td>
                                <td>
                                    <?php
                                    if ($b['status_donasi'] == 'Pending') {
                                        echo '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2">Menunggu</span>';
                                    } elseif ($b['status_donasi'] == 'Diterima') {
                                        echo '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2">Aktif</span>';
                                    } else {
                                        echo '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2">Ditolak</span>';
                                    }
                                    ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light border-0 text-muted" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg">
                                            <?php if ($b['status_donasi'] == 'Pending'): ?>
                                                <li><a class="dropdown-item text-success" href="#" onclick="confirmLink(event, 'index.php?page=buku&act=terima&id=<?= $b['id'] ?>', 'Terima buku ini?', 'Buku akan masuk ke daftar aktif.')"><i class="bi bi-check-lg me-2"></i>Terima</a></li>
                                                <li><a class="dropdown-item text-danger" href="#" onclick="confirmLink(event, 'index.php?page=buku&act=tolak&id=<?= $b['id'] ?>', 'Tolak donasi ini?', 'Status akan berubah menjadi Ditolak.')"><i class="bi bi-x-lg me-2"></i>Tolak</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                            <?php endif; ?>
                                            
                                            <li><a class="dropdown-item" href="index.php?page=buku&aksi=edit&id=<?= $b['id'] ?>"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                            
                                            <?php if ($b['status_donasi'] != 'Pending'): ?>
                                                <li><a class="dropdown-item text-danger" href="#" onclick="confirmLink(event, 'index.php?page=buku&aksi=hapus&id=<?= $b['id'] ?>', 'Hapus buku ini?', 'Data akan dihapus permanen.')"><i class="bi bi-trash me-2"></i>Hapus</a></li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="7" class="text-center py-5 text-muted">Belum ada data buku.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php endif; ?>
</div>

<!-- SWEET ALERT LOGIC -->
<script>
    <?php if (isset($_SESSION['flash_success'])): ?>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            html: <?= json_encode($_SESSION['flash_success']) ?>,
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
            html: <?= json_encode($_SESSION['flash_error']) ?>,
            showConfirmButton: true,
            confirmButtonColor: '#A63A3A'
        });
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    function confirmLink(e, url, title, text) {
        e.preventDefault();
        Swal.fire({
            title: title || 'Apakah Anda yakin?',
            text: text || "Tindakan ini tidak dapat dibatalkan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#A63A3A',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Ya, Lanjutkan!',
            cancelButtonText: 'Batal',
            reverseButtons: true,
            customClass: {
                popup: 'rounded-4 shadow-lg border-0',
                confirmButton: 'btn btn-danger px-4 rounded-3',
                cancelButton: 'btn btn-light px-4 rounded-3 text-muted border-0'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    }
</script>