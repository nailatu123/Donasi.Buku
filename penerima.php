<?php
/**
 * File: views/penerima.php
 * Halaman Manajemen Penerima (Redesigned for Premium Clean SaaS)
 */

// --- 1. LOGIKA UTAMA (POST/GET) ---

// Handle Approve / Finish / Hapus Request
if (isset($_GET['act']) && isset($_GET['id'])) {
    $id_transaksi = $_GET['id'];
    $act = $_GET['act'];

    try {
        if ($act == 'approve') {
            $id_buku = $_GET['id_buku'];
            $pdo->beginTransaction();

            $stmtUpdateStok = $pdo->prepare("UPDATE buku SET stok = stok - 1 WHERE id = ? AND stok > 0");
            $stmtUpdateStok->execute([$id_buku]);

            if ($stmtUpdateStok->rowCount() > 0) {
                $pdo->prepare("UPDATE transaksi_donasi SET status = 'diterima' WHERE id = ?")->execute([$id_transaksi]);
                $pdo->commit();
                $_SESSION['flash_success'] = "Permintaan disetujui! Stok berhasil dikurangi.";
            } else {
                $pdo->rollBack();
                $_SESSION['flash_error'] = "Gagal menyetujui: Stok buku ini sudah habis!";
            }
        } elseif ($act == 'finish') {
            $pdo->prepare("UPDATE transaksi_donasi SET status = 'selesai' WHERE id = ?")->execute([$id_transaksi]);
            $_SESSION['flash_success'] = "Transaksi ditandai selesai.";
        } elseif ($act == 'hapus') {
            $pdo->prepare("DELETE FROM transaksi_donasi WHERE id = ?")->execute([$id_transaksi]);
            $_SESSION['flash_success'] = "Permintaan berhasil dihapus/ditolak.";
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction())
            $pdo->rollBack();
        $_SESSION['flash_error'] = "Terjadi kesalahan: " . $e->getMessage();
    }

    echo "<script>window.location='index.php?page=penerima';</script>";
    exit;
}

// Handle User Management (Add/Edit/Delete)
if ($_SERVER['REQUEST_METHOD'] == 'POST' || isset($_GET['hapus_user'])) {

    // Hapus
    if (isset($_GET['hapus_user'])) {
        $id_user = $_GET['hapus_user'];
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id_user]);
        $_SESSION['flash_success'] = "Akun penerima berhasil dihapus permanently.";
        echo "<script>window.location='index.php?page=penerima';</script>";
        exit;
    }

    // Tambah
    if (isset($_POST['add_user'])) {
        try {
            $sql = "INSERT INTO users (username, password_hash, role, status, alamat, no_wa, created_at) VALUES (?, ?, 'penerima', 'aktif', ?, ?, NOW())";
            $pdo->prepare($sql)->execute([
                $_POST['username'],
                password_hash($_POST['password'], PASSWORD_DEFAULT),
                $_POST['alamat'],
                $_POST['no_wa']
            ]);
            $_SESSION['flash_success'] = "Akun penerima <b>" . htmlspecialchars($_POST['username']) . "</b> berhasil dibuat!";
        } catch (Exception $e) {
            $_SESSION['flash_error'] = "Gagal menambah akun: " . $e->getMessage();
        }
        echo "<script>window.location='index.php?page=penerima';</script>";
        exit;
    }

    // Edit
    if (isset($_POST['edit_user'])) {
        try {
            $params = [$_POST['username'], $_POST['no_wa'], $_POST['alamat'], $_POST['status_akun'], 'penerima'];
            $sql = "UPDATE users SET username=?, no_wa=?, alamat=?, status=?, role=?, updated_at=NOW()";

            if (!empty($_POST['password'])) {
                $sql .= ", password_hash=?";
                $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }
            $sql .= " WHERE id=?";
            $params[] = $_POST['id_user'];

            $pdo->prepare($sql)->execute($params);
            $_SESSION['flash_success'] = "Data akun <b>" . htmlspecialchars($_POST['username']) . "</b> berhasil diperbarui.";
        } catch (Exception $e) {
            $_SESSION['flash_error'] = "Gagal update akun: " . $e->getMessage();
        }
        echo "<script>window.location='index.php?page=penerima';</script>";
        exit;
    }
}

// --- 2. DATA PREPARATION ---

// Requests
$query_req = "SELECT t.*, b.judul, b.gambar, u.username AS nama_penerima
              FROM transaksi_donasi t
              JOIN buku b ON t.buku_id = b.id
              JOIN users u ON t.penerima_id = u.id
              ORDER BY FIELD(t.status, 'dalam_proses','diterima','selesai'), t.created_at DESC";
$requests = $pdo->query($query_req)->fetchAll(PDO::FETCH_ASSOC);

// Users
$users_penerima = $pdo->query("SELECT * FROM users WHERE role = 'penerima' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

?>

<style>
    /* Tab Styling Overrides for Clean Look */
    .nav-tabs .nav-link {
        border: none;
        color: #6B7280;
        font-weight: 500;
        border-bottom: 2px solid transparent;
        padding-left: 0;
        padding-right: 0;
        margin-right: 2rem;
        background: transparent !important;
    }

    .nav-tabs .nav-link:hover {
        color: var(--primary);
        border-color: transparent;
    }

    .nav-tabs .nav-link.active {
        color: var(--primary) !important;
        font-weight: 700 !important;
        border-bottom: 2px solid var(--primary) !important;
        background: transparent !important;
    }
</style>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h5 class="text-muted small fw-bold text-uppercase mb-1" style="letter-spacing: 1px;">Distribusi</h5>
            <h2 class="font-heading fw-bold mb-0">Manajemen Penerima</h2>
        </div>
    </div>

    <!-- TABS -->
    <ul class="nav nav-tabs mb-4 border-bottom" id="pills-tab" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" id="tab-validasi" data-bs-toggle="pill" data-bs-target="#content-validasi">
                Validasi Penyaluran
                <?php if (count(array_filter($requests, fn($r) => $r['status'] == 'dalam_proses')) > 0): ?>
                    <span
                        class="badge bg-danger rounded-pill ms-2"><?= count(array_filter($requests, fn($r) => $r['status'] == 'dalam_proses')) ?></span>
                <?php endif; ?>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="tab-akun" data-bs-toggle="pill" data-bs-target="#content-akun">
                Data Akun Penerima
            </button>
        </li>
    </ul>

    <div class="tab-content" id="pills-tabContent">

        <!-- TAB 1: VALIDASI -->
        <div class="tab-pane fade show active" id="content-validasi">
            <div class="card border-0 shadow-card">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold">Daftar Permintaan Masuk</h6>
                </div>
                <div>
                    <table class="table table-custom align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4" style="width: 40%">Detail Buku</th>
                                <th style="width: 20%">Penerima</th>
                                <th style="width: 15%">Tanggal</th>
                                <th style="width: 15%">Status</th>
                                <th class="text-end pe-4" style="width: 10%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($requests):
                                foreach ($requests as $req): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-start gap-3">
                                                <div class="rounded-2 shadow-sm border overflow-hidden flex-shrink-0"
                                                    style="width: 50px; height: 75px;">
                                                    <img src="assets/img/<?= htmlspecialchars($req['gambar']) ?>"
                                                        class="w-100 h-100 object-fit-cover">
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark text-truncate" style="max-width: 200px;">
                                                        <?= htmlspecialchars($req['judul']) ?>
                                                    </div>
                                                    <div class="text-muted small">ID: #REQ-<?= $req['id'] ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-light d-flex justify-content-center align-items-center text-muted small border flex-shrink-0"
                                                    style="width: 28px; height: 28px;">
                                                    <span
                                                        class="fw-bold small"><?= strtoupper(substr($req['nama_penerima'], 0, 1)) ?></span>
                                                </div>
                                                <span
                                                    class="text-dark small fw-medium"><?= htmlspecialchars($req['nama_penerima']) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-muted small"><?= date('d M Y', strtotime($req['created_at'])) ?>
                                            </div>
                                            <div class="text-muted text-xs"><?= date('H:i', strtotime($req['created_at'])) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($req['status'] == 'dalam_proses'): ?>
                                                <span
                                                    class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2">Menunggu</span>
                                            <?php elseif ($req['status'] == 'diterima'): ?>
                                                <span
                                                    class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2">Siap
                                                    Ambil</span>
                                            <?php else: ?>
                                                <span
                                                    class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2">Selesai</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light border-0 text-muted" type="button"
                                                    data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg">
                                                    <?php if ($req['status'] == 'dalam_proses'): ?>
                                                        <li><a class="dropdown-item text-success" href="#"
                                                                onclick="confirmLink(event, 'index.php?page=penerima&act=approve&id=<?= $req['id'] ?>&id_buku=<?= $req['buku_id'] ?>', 'Setujui Permintaan?', 'Stok buku akan berkurang otomatis.')"><i
                                                                    class="bi bi-check-lg me-2"></i>Setujui</a></li>
                                                        <li><a class="dropdown-item text-danger" href="#"
                                                                onclick="confirmLink(event, 'index.php?page=penerima&act=hapus&id=<?= $req['id'] ?>', 'Tolak Permintaan?', 'Data request akan dihapus.')"><i
                                                                    class="bi bi-x-lg me-2"></i>Tolak</a></li>
                                                    <?php elseif ($req['status'] == 'diterima'): ?>
                                                        <li><a class="dropdown-item text-primary" href="#"
                                                                onclick="confirmLink(event, 'index.php?page=penerima&act=finish&id=<?= $req['id'] ?>', 'Selesaikan Transaksi?', 'Pastikan buku sudah diambil penerima.')"><i
                                                                    class="bi bi-check2-all me-2"></i>Tandai Selesai</a></li>
                                                    <?php else: ?>
                                                        <li><span class="dropdown-item-text text-muted small">Tidak ada aksi</span>
                                                        </li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">Belum ada permintaan masuk.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 2: AKUN -->
        <div class="tab-pane fade" id="content-akun">
            <div class="card border-0 shadow-card">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">Data Akun Penerima</h6>
                    <button class="btn btn-sm btn-primary-custom shadow-sm" data-bs-toggle="modal"
                        data-bs-target="#modalTambah">
                        <i class="bi bi-plus-lg me-1"></i>Tambah Baru
                    </button>
                </div>
                <div>
                    <table class="table table-custom align-middle mb-0">
                        <thead>
                            <tr class="bg-light">
                                <th class="ps-4" style="width: 30%;">Profil Penerima</th>
                                <th style="width: 20%;">WhatsApp</th>
                                <th style="width: 30%;">Alamat Lengkap</th>
                                <th style="width: 10%;">Status</th>
                                <th class="text-end pe-4" style="width: 10%;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($users_penerima):
                                foreach ($users_penerima as $u): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-3 py-2">
                                                <div class="rounded-circle bg-primary bg-opacity-10 d-flex justify-content-center align-items-center text-primary fw-bold"
                                                    style="width: 42px; height: 42px; font-size: 16px;">
                                                    <?= strtoupper(substr($u['username'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($u['username']) ?>
                                                    </div>
                                                    <div class="text-muted small" style="font-size: 11px;">
                                                        <i class="bi bi-clock-history me-1"></i>Bergabung
                                                        <?= date('d M Y', strtotime($u['created_at'])) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2 text-dark">
                                                <i class="bi bi-whatsapp text-success"></i>
                                                <span
                                                    class="font-monospace fw-medium"><?= htmlspecialchars($u['no_wa']) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-muted small lh-sm text-wrap pe-3">
                                                <?= htmlspecialchars($u['alamat']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($u['status'] == 'aktif'): ?>
                                                <span
                                                    class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-2">
                                                    <i class="bi bi-check-circle me-1"></i>Aktif
                                                </span>
                                            <?php else: ?>
                                                <span
                                                    class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-3 py-2">
                                                    <i class="bi bi-slash-circle me-1"></i>Blokir
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="dropdown">
                                                <button class="btn btn-icon btn-light border-0 text-muted rounded-circle"
                                                    type="button" data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg p-2 rounded-3">
                                                    <li>
                                                        <a class="dropdown-item rounded-2 py-2 btn-edit" href="#"
                                                            data-bs-toggle="modal" data-bs-target="#modalEdit"
                                                            data-id="<?= $u['id'] ?>"
                                                            data-username="<?= htmlspecialchars($u['username']) ?>"
                                                            data-wa="<?= htmlspecialchars($u['no_wa']) ?>"
                                                            data-alamat="<?= htmlspecialchars($u['alamat']) ?>"
                                                            data-status="<?= htmlspecialchars($u['status']) ?>">
                                                            <i class="bi bi-pencil-square me-2 text-primary"></i>Edit Data
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item rounded-2 py-2 text-danger" href="#"
                                                            onclick="confirmLink(event, 'index.php?page=penerima&hapus_user=<?= $u['id'] ?>', 'Hapus Akun?', 'Tindakan ini permanen.')">
                                                            <i class="bi bi-trash me-2"></i>Hapus Akun
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <div class="opacity-50 mb-2">
                                            <i class="bi bi-people" style="font-size: 3rem;"></i>
                                        </div>
                                        <div class="text-muted fw-bold">Belum ada data penerima.</div>
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

<!-- MODALS -->
<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Tambah Penerima</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Username</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">WhatsApp</label>
                        <input type="text" class="form-control" name="no_wa" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Alamat</label>
                        <textarea class="form-control" name="alamat" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <button class="btn btn-primary-custom w-100 mt-3" name="add_user">Simpan Data</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Edit Penerima</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST">
                    <input type="hidden" name="id_user" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Username</label>
                        <input type="text" class="form-control" name="username" id="edit_username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">WhatsApp</label>
                        <input type="text" class="form-control" name="no_wa" id="edit_wa" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Alamat</label>
                        <textarea class="form-control" name="alamat" id="edit_alamat" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Status</label>
                        <select class="form-select" name="status_akun" id="edit_status">
                            <option value="aktif">Aktif</option>
                            <option value="blokir">Blokir</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Password Baru <span
                                class="fw-normal text-muted">(Opsional)</span></label>
                        <input type="password" class="form-control" name="password"
                            placeholder="Biarkan kosong jika tetap">
                    </div>
                    <button class="btn btn-primary-custom w-100 mt-3" name="edit_user">Update Data</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPTS & SWEETALERT -->
<script>
    // SweetAlert Handling
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

    // Global Confirm Link
    function confirmLink(e, url, title, text) {
        e.preventDefault();
        Swal.fire({
            title: title || 'Konfirmasi Tindakan',
            text: text || "Apakah Anda yakin?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#A63A3A',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Ya, Lanjutkan',
            cancelButtonText: 'Batal',
            reverseButtons: true,
            customClass: { popup: 'rounded-4 shadow-lg border-0', confirmButton: 'btn btn-danger px-4 rounded-3', cancelButton: 'btn btn-light px-4 rounded-3 text-muted border-0' }
        }).then((result) => {
            if (result.isConfirmed) window.location.href = url;
        });
    }

    // Modal Edit Filler
    document.addEventListener('DOMContentLoaded', function () {
        const editButtons = document.querySelectorAll('.btn-edit');
        editButtons.forEach(btn => {
            btn.addEventListener('click', function () {
                document.getElementById('edit_id').value = this.dataset.id;
                document.getElementById('edit_username').value = this.dataset.username;
                document.getElementById('edit_wa').value = this.dataset.wa;
                document.getElementById('edit_alamat').value = this.dataset.alamat;
                document.getElementById('edit_status').value = this.dataset.status;
            });
        });
    });
</script>