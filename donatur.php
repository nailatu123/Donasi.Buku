<?php
/**
 * File: views/donatur.php
 * Halaman Manajemen Donatur untuk Admin
 * FIX FINAL: Menggunakan redirect JavaScript untuk mengatasi Warning Header dari sidebar.php.
 */

// --- 1. LOGIKA UTAMA: HAPUS DONATUR ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'donatur'");
        $stmt->execute([$id]);
        $pdo->commit();
        $_SESSION['flash_success'] = "Akun donatur berhasil dihapus.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['flash_error'] = "Gagal menghapus donatur: " . $e->getMessage();
    }
    // Ganti header() dengan JS Redirect yang aman
    echo "<script>window.location.href='index.php?page=donatur&tab=akun';</script>";
    exit;
}

// --- 2. LOGIKA UTAMA: TAMBAH/EDIT DONATUR ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['action']) && ($_GET['action'] == 'tambah' || $_GET['action'] == 'edit')) {

    $action = $_GET['action'];
    $id = $_POST['id'] ?? null;
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $alamat = trim($_POST['alamat'] ?? '');
    $kontak = trim($_POST['no_wa'] ?? '');
    $redirect_tab = 'akun';

    try {
        if (empty($username))
            throw new Exception("Username wajib diisi!");

        $query_check = "SELECT COUNT(*) FROM users WHERE username = ?";
        if ($action == 'edit')
            $query_check .= " AND id != ?";
        $check = $pdo->prepare($query_check);
        $check_params = [$username];
        if ($action == 'edit')
            $check_params[] = $id;
        $check->execute($check_params);

        if ($check->fetchColumn() > 0) {
            throw new Exception("Username sudah digunakan. Mohon gunakan username lain.");
        }

        // --- PROSES INSERT ---
        if ($action == 'tambah') {
            if (empty($password))
                throw new Exception("Password wajib diisi!");

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, alamat, no_wa, created_at) VALUES (?, ?, 'donatur', ?, ?, NOW())");
            $stmt->execute([$username, $hashed_password, $alamat, $kontak]);

            $_SESSION['flash_success'] = "Donatur baru <b>" . htmlspecialchars($username) . "</b> berhasil ditambahkan!";

            // --- PROSES UPDATE ---
        } elseif ($action == 'edit') {

            $updates = ["username = ?", "alamat = ?", "no_wa = ?"];
            $params = [$username, $alamat, $kontak];

            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $updates[] = "password_hash = ?";
                $params[] = $hashed_password;
            }

            $query = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            $params[] = $id;

            $update_stmt = $pdo->prepare($query);
            $update_stmt->execute($params);

            $_SESSION['flash_success'] = "Data donatur <b>" . htmlspecialchars($username) . "</b> berhasil diperbarui!";
        }

    } catch (Exception $e) {
        $_SESSION['flash_error'] = $e->getMessage();
        $_SESSION['form_data'] = $_POST;
    }

    // Jika sukses, lakukan redirect menggunakan JS
    if (!isset($_SESSION['flash_error']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
        echo "<script>window.location.href='index.php?page=donatur&tab=" . $redirect_tab . "';</script>";
        exit;
    }
}

// --- 3. LOGIKA UTAMA: VERIFIKASI BUKU ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'verifikasi') {
    $id_buku = $_POST['id'];
    $status = $_POST['status']; // 'approved' atau 'rejected'

    try {
        $pdo->beginTransaction();

        if ($status == 'approved') {
            $stmt = $pdo->prepare("UPDATE buku SET status_donasi = 'Diterima' WHERE id = ?");
            $stmt->execute([$id_buku]);
            $_SESSION['flash_success'] = "Buku berhasil disetujui.";
        } elseif ($status == 'rejected') {
            $stmt = $pdo->prepare("UPDATE buku SET status_donasi = 'Ditolak' WHERE id = ?");
            $stmt->execute([$id_buku]);
            $_SESSION['flash_success'] = "Buku berhasil ditolak.";
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['flash_error'] = "Gagal memproses: " . $e->getMessage();
    }
    // Redirect menggunakan JS (agar flash message muncul di page load berikutnya)
    echo "<script>window.location.href='index.php?page=donatur&tab=validasi';</script>";
    exit;
}


// --- 4. PENGAMBILAN DATA UNTUK TAMPILAN ---

$action = $_GET['action'] ?? 'list';
$active_tab = $_GET['tab'] ?? 'akun';

if ($action == 'list'):
    // Data Donatur
    $query_users = "SELECT * FROM users WHERE role = 'donatur' ORDER BY created_at DESC";
    $data_donatur = $pdo->query($query_users)->fetchAll(PDO::FETCH_ASSOC);

    // Data Buku Pending
    $query_buku_validasi = "SELECT b.id, b.judul, b.gambar, b.kondisi, u.username AS nama_donatur 
                            FROM buku b 
                            JOIN users u ON b.id_user = u.id 
                            WHERE b.status_donasi = 'Pending' 
                            ORDER BY b.created_at DESC";
    $data_buku_pending = $pdo->query($query_buku_validasi)->fetchAll(PDO::FETCH_ASSOC);

elseif ($action == 'edit' && isset($_GET['id'])):
    // Ambil Data User Tunggal (Fix "Undefined Variable")
    $id_edit = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'donatur'");
    $stmt->execute([$id_edit]);
    $donatur = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$donatur) {
        echo "<script>alert('Data donatur tidak ditemukan!'); window.location.href='index.php?page=donatur';</script>";
        exit;
    }
endif;
?>

<!-- Content of donatur.php -->
<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h5 class="text-muted small fw-bold text-uppercase mb-1" style="letter-spacing: 1px;">Manajemen Data</h5>
            <h2 class="font-heading fw-bold mb-0">Donatur & Validasi</h2>
        </div>
        <?php if ($action == 'list'): ?>
            <a href="index.php?page=donatur&action=tambah"
                class="btn btn-primary-custom shadow-sm d-flex align-items-center gap-2 px-4">
                <i class="bi bi-plus-lg text-xs"></i>
                <span>Donatur Baru</span>
            </a>
        <?php endif; ?>
    </div>

    <!-- 
       [REMOVED] Old Bootstrap Alerts 
       Flash Messages are now handled by SweetAlert2 at the bottom of the script 
    -->

    <?php if ($action == 'list'): ?>

        <!-- Main Card Wrapper -->
        <div class="card border-0 shadow-card">

            <!-- TAB STYLE OVERRIDES -->
            <style>
                .nav-link.active {
                    color: var(--primary) !important;
                    font-weight: 700 !important;
                    border-bottom: 3px solid var(--primary) !important;
                    background: transparent !important;
                }

                .nav-link {
                    color: var(--text-muted);
                    transition: all 0.3s ease;
                }

                .nav-link:hover {
                    color: var(--primary);
                }
            </style>

            <!-- Exclusive Tabs -->
            <div class="card-header bg-white border-bottom px-4 pt-3 pb-0">
                <ul class="nav nav-tabs card-header-tabs border-0 gap-3" id="pills-tab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link py-3 border-0 <?= ($active_tab == 'akun') ? 'active' : '' ?>"
                            id="pills-akun-tab" data-bs-toggle="pill" data-bs-target="#pills-akun" type="button" role="tab">
                            <i class="bi bi-people me-2"></i>Data Akun
                        </button>
                    </li>
                    <li class="nav-item position-relative">
                        <button class="nav-link py-3 border-0 <?= ($active_tab == 'validasi') ? 'active' : '' ?>"
                            id="pills-validasi-tab" data-bs-toggle="pill" data-bs-target="#pills-validasi" type="button"
                            role="tab">
                            <i class="bi bi-check-circle me-2"></i>Validasi Buku
                            <?php if (count($data_buku_pending) > 0): ?>
                                <span class="position-absolute ms-1 translate-middle badge rounded-pill bg-danger"
                                    style="font-size: 0.6rem; top: 10px;"><?= count($data_buku_pending) ?></span>
                            <?php endif; ?>
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-0">
                <div class="tab-content" id="pills-tabContent">

                    <!-- TAB 1: DATA DONATUR -->
                    <div class="tab-pane fade <?= ($active_tab == 'akun') ? 'show active' : '' ?>" id="pills-akun"
                        role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-custom mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th class="ps-4" style="width: 5%">No</th>
                                        <th style="width: 30%">Donatur Profile</th>
                                        <th style="width: 20%">Kontak</th>
                                        <th style="width: 30%">Alamat</th>
                                        <th class="text-end pe-4" style="width: 15%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1;
                                    if ($data_donatur):
                                        foreach ($data_donatur as $d): ?>
                                            <tr>
                                                <td class="ps-4 text-muted small"><?= $no++ ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm flex-shrink-0"
                                                            style="width: 40px; height: 40px; background: linear-gradient(135deg, #A63A3A, #8F3030);">
                                                            <?= strtoupper(substr($d['username'], 0, 1)) ?>
                                                        </div>
                                                        <div style="min-width: 0;">
                                                            <div class="fw-bold text-dark text-truncate">
                                                                <?= htmlspecialchars($d['username']) ?>
                                                            </div>
                                                            <div class="text-muted small">ID: #USR-<?= $d['id'] ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if (!empty($d['no_wa'])): ?>
                                                        <span
                                                            class="d-inline-flex align-items-center text-muted small bg-light px-2 py-1 rounded">
                                                            <i
                                                                class="bi bi-whatsapp text-success me-2"></i><?= htmlspecialchars($d['no_wa']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted small">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-muted small">
                                                    <?= htmlspecialchars(substr($d['alamat'] ?? '-', 0, 50)) . (strlen($d['alamat'] ?? '') > 50 ? '...' : '') ?>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-light border-0 text-muted" type="button"
                                                            data-bs-toggle="dropdown">
                                                            <i class="bi bi-three-dots-vertical"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg">
                                                            <li>
                                                                <h6 class="dropdown-header text-uppercase small">Aksi</h6>
                                                            </li>
                                                            <li><a class="dropdown-item small"
                                                                    href="index.php?page=donatur&action=edit&id=<?= $d['id'] ?>">Edit
                                                                    Data</a></li>
                                                            <li>
                                                                <hr class="dropdown-divider">
                                                            </li>
                                                            <!-- Button Delete with SweetAlert (via JS function) -->
                                                            <li><a class="dropdown-item small text-danger" href="javascript:void(0)"
                                                                    onclick="confirmLink(event, 'index.php?page=donatur&hapus=<?= $d['id'] ?>', 'Hapus donatur ini?', 'Data user akan dihapus permanen.')">
                                                                    Hapus User
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">Belum ada data donatur saat ini.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 2: VALIDASI BUKU -->
                    <div class="tab-pane fade <?= ($active_tab == 'validasi') ? 'show active' : '' ?>" id="pills-validasi"
                        role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-custom mb-0 align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Buku & Request</th>
                                        <th>Donatur</th>
                                        <th>Kondisi</th>
                                        <th class="text-end pe-4">Tindakan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1;
                                    if ($data_buku_pending):
                                        foreach ($data_buku_pending as $b): ?>
                                            <tr>
                                                <td class="ps-4 py-3">
                                                    <div class="d-flex align-items-start gap-3">
                                                        <div class="rounded-2 shadow-sm border overflow-hidden flex-shrink-0"
                                                            style="width: 50px; height: 70px;">
                                                            <img src="assets/img/<?= htmlspecialchars($b['gambar']) ?>"
                                                                class="w-100 h-100 object-fit-cover">
                                                        </div>
                                                        <div>
                                                            <h6 class="fw-bold text-dark mb-1 text-truncate"
                                                                style="max-width: 200px;"><?= htmlspecialchars($b['judul']) ?></h6>
                                                            <span class="badge bg-light text-secondary border fw-medium px-2 py-1"
                                                                style="font-size: 0.7rem;">
                                                                REQ #<?= $b['id'] ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white small fw-bold shadow-sm flex-shrink-0"
                                                            style="width: 32px; height: 32px; background: var(--primary);">
                                                            <?= strtoupper(substr($b['nama_donatur'], 0, 1)) ?>
                                                        </div>
                                                        <span
                                                            class="text-dark fw-medium small"><?= htmlspecialchars($b['nama_donatur']) ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $k = $b['kondisi'];
                                                    $badge_label = ($k == 'baru' ? 'Baru' : ($k == 'layak' ? 'Layak Baca' : 'Kurang Baik'));
                                                    ?>
                                                    <span
                                                        class="badge bg-warning bg-opacity-10 text-warning px-2 py-1 rounded-pill fw-bold border border-warning border-opacity-25 small">
                                                        <?= $badge_label ?>
                                                    </span>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <form method="POST" action="index.php?page=donatur&tab=validasi"
                                                        class="d-inline-flex gap-2">
                                                        <input type="hidden" name="action" value="verifikasi">
                                                        <input type="hidden" name="id" value="<?= $b['id'] ?>">

                                                        <!-- Cleaner Buttons -->
                                                        <button type="button"
                                                            class="btn btn-success btn-sm px-3 shadow-none fw-medium"
                                                            onclick="confirmFormSubmit(this, 'approved', 'Terima dan tayangkan buku ini?')">
                                                            Terima
                                                        </button>

                                                        <button type="button"
                                                            class="btn btn-outline-danger btn-sm px-3 shadow-none fw-medium"
                                                            onclick="confirmFormSubmit(this, 'rejected', 'Tolak donasi ini?')">
                                                            Tolak
                                                        </button>

                                                        <input type="hidden" name="status" id="status_input_<?= $b['id'] ?>">
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5">
                                                <div class="d-flex flex-column align-items-center justify-content-center"
                                                    style="min-height: 200px;">
                                                    <div class="bg-light rounded-circle p-3 mb-3">
                                                        <i class="bi bi-check-lg fs-1 text-muted"></i>
                                                    </div>
                                                    <h6 class="fw-bold text-dark">Tidak ada permintaan</h6>
                                                    <p class="text-muted small mb-0">Semua donasi buku telah divalidasi.</p>
                                                </div>
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

    <?php elseif ($action == 'tambah' || $action == 'edit'): ?>

        <!-- Form Section -->
        <?php $is_edit = ($action == 'edit'); ?>
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card border-0 shadow-card">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold"><?= $is_edit ? 'Edit Profile Donatur' : 'Daftar Donatur Baru' ?></h6>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="index.php?page=donatur&action=<?= $action ?>">
                            <?php if ($is_edit): ?><input type="hidden" name="id"
                                    value="<?= htmlspecialchars($donatur['id']) ?>"><?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Username</label>
                                <input type="text" class="form-control" name="username"
                                    value="<?= $is_edit ? htmlspecialchars($donatur['username']) : ($post_username ?? '') ?>"
                                    required>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted text-uppercase">No. WhatsApp</label>
                                    <input type="text" class="form-control" name="no_wa"
                                        value="<?= $is_edit ? htmlspecialchars($donatur['no_wa']) : ($post_kontak ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Password</label>
                                    <input type="password" class="form-control" name="password" <?= $is_edit ? '' : 'required' ?> placeholder="<?= $is_edit ? 'Isi untuk ubah' : 'Min. 6 karakter' ?>">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-bold text-muted text-uppercase">Alamat Domisili</label>
                                <textarea class="form-control" name="alamat"
                                    rows="3"><?= $is_edit ? htmlspecialchars($donatur['alamat']) : ($post_alamat ?? '') ?></textarea>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <a href="index.php?page=donatur" class="text-muted text-decoration-none small">Kembali</a>
                                <button type="submit" class="btn btn-primary-custom px-4 shadow-sm">Simpan Data</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<!-- SWEET ALERT LOGIC -->
<script>
    // 1. Handle PHP Flash Messages
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

    // 2. Generic Confirm Delete (for Links)
    function confirmLink(e, url, title, text) {
        e.preventDefault();
        Swal.fire({
            title: title || 'Apakah Anda yakin?',
            text: text || "Tindakan ini tidak dapat dibatalkan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#A63A3A',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal',
            reverseButtons: true,
            customClass: {
                popup: 'rounded-4 shadow-lg border-0',
                confirmButton: 'btn btn-danger px-4 rounded-3',
                cancelButton: 'btn btn-light px-4 rounded-3 text-muted border-0'
            },
            buttonsStyling: false // Use Bootstrap buttons
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    }

    // 3. Generic Confirm Form Submission (for Approve/Reject)
    function confirmFormSubmit(btn, statusValue, question) {
        // Find parent form
        let form = btn.closest('form');
        // Find input for status
        let statusInput = form.querySelector('input[name="status"]');
        statusInput.value = statusValue; // Set value manually since we prevented default submit

        const isReject = statusValue === 'rejected';

        Swal.fire({
            title: isReject ? 'Tolak Buku?' : 'Terima Buku?',
            text: question,
            icon: isReject ? 'warning' : 'question',
            showCancelButton: true,
            confirmButtonColor: isReject ? '#A63A3A' : '#10B981',
            cancelButtonColor: '#6B7280',
            confirmButtonText: isReject ? 'Ya, Tolak' : 'Ya, Terima',
            cancelButtonText: 'Batal',
            reverseButtons: true,
            customClass: {
                popup: 'rounded-4 shadow-lg border-0',
                confirmButton: `btn ${isReject ? 'btn-danger' : 'btn-success'} px-4 rounded-3`,
                cancelButton: 'btn btn-light px-4 rounded-3 text-muted border-0'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }

    // Keep Tab State
    document.addEventListener('DOMContentLoaded', function () {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('tab') === 'validasi') {
            const tabEl = document.querySelector('#pills-validasi-tab');
            if (tabEl) new bootstrap.Tab(tabEl).show();
        }
    });
</script>