<?php
// views/dashboard.php

// 1. QUERY: Total Counts
$totalDonatur = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'donatur'")->fetchColumn();
$totalPenerima = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'penerima'")->fetchColumn();
$totalBuku = $pdo->query("SELECT COUNT(*) FROM buku")->fetchColumn();
$totalPending = $pdo->query("SELECT COUNT(*) FROM buku WHERE status_donasi = 'Pending'")->fetchColumn();

// 2. QUERY: Recent Activity (Latest 5 Books)
$recent_books = $pdo->query("
    SELECT b.id, b.judul, b.gambar, b.status_donasi, b.created_at, u.username 
    FROM buku b 
    JOIN users u ON b.id_user = u.id 
    ORDER BY b.created_at DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// 3. QUERY: Category Stats (For visual distribution)
$cat_stats = $pdo->query("
    SELECT kategori, COUNT(*) as total 
    FROM buku 
    GROUP BY kategori 
    ORDER BY total DESC 
    LIMIT 4
")->fetchAll(PDO::FETCH_ASSOC);

?>
<div class="container-fluid px-0">

    <!-- 1. Page Header -->
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <h5 class="text-muted small fw-bold text-uppercase mb-1" style="letter-spacing: 1px;">Overview</h5>
            <h2 class="font-heading fw-bold mb-0">Dashboard Admin</h2>
        </div>
        <div>
            <span class="text-muted small bg-white px-3 py-2 rounded-pill shadow-sm border border-light">
                <i class="bi bi-calendar3 me-2"></i><?= date('l, d F Y') ?>
            </span>
        </div>
    </div>

    <!-- 2. Hero Metrics Grid (Clean & Exclusive) -->
    <div class="row g-4 mb-5">
        <!-- Metric: Total Buku -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 p-3">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center justify-content-center bg-light rounded-circle"
                            style="width: 48px; height: 48px;">
                            <i class="bi bi-book text-muted fs-5"></i>
                        </div>
                        <span class="badge bg-light text-muted rounded-pill fw-normal px-3 py-2 border">Total
                            Buku</span>
                    </div>
                    <div>
                        <h2 class="fw-bold text-dark mb-0"><?= number_format($totalBuku) ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric: Total Donatur -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 p-3">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center justify-content-center bg-light rounded-circle"
                            style="width: 48px; height: 48px;">
                            <i class="bi bi-people text-muted fs-5"></i>
                        </div>
                        <span class="badge bg-light text-muted rounded-pill fw-normal px-3 py-2 border">Donatur</span>
                    </div>
                    <div>
                        <h2 class="fw-bold text-dark mb-0"><?= number_format($totalDonatur) ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric: Total Penerima -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 p-3">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center justify-content-center bg-light rounded-circle"
                            style="width: 48px; height: 48px;">
                            <i class="bi bi-person-check text-muted fs-5"></i>
                        </div>
                        <span class="badge bg-light text-muted rounded-pill fw-normal px-3 py-2 border">Penerima</span>
                    </div>
                    <div>
                        <h2 class="fw-bold text-dark mb-0"><?= number_format($totalPenerima) ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric: Pending Actions (Exclusive Accent) -->
        <div class="col-12 col-sm-6 col-xl-3">
            <!-- Full Red Background as requested -->
            <div class="card h-100 p-3"
                style="background-color: var(--primary); color: white; border: none; box-shadow: 0 4px 20px rgba(166, 58, 58, 0.3);">
                <div class="card-body d-flex flex-column justify-content-between position-relative overflow-hidden">
                    <!-- Decorative Circle Behind -->
                    <div class="position-absolute"
                        style="top: -10px; right: -10px; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%;">
                    </div>

                    <div class="d-flex align-items-center justify-content-between mb-4 position-relative z-1">
                        <div class="d-flex align-items-center justify-content-center rounded-circle shadow-sm"
                            style="width: 48px; height: 48px; background-color: rgba(255,255,255,0.25);">
                            <i class="bi bi-bell-fill fs-5" style="color: white;"></i>
                        </div>
                        <span class="badge rounded-pill fw-normal px-3 py-2 border border-light border-opacity-25"
                            style="background-color: rgba(0,0,0,0.1); color: white; backdrop-filter: blur(4px);">
                            Perlu Validasi
                        </span>
                    </div>
                    <div class="position-relative z-1">
                        <h2 class="fw-bold mb-0 text-white display-6"><?= number_format($totalPending) ?></h2>
                        <small class="text-white-50">Permintaan menunggu tindakan Anda</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Clean Split Layout -->
    <div class="row g-4">

        <!-- Recent Activity Table -->
        <div class="col-12 col-lg-8">
            <div class="card h-100 p-0 overflow-hidden">
                <div
                    class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-uppercase text-muted small" style="letter-spacing: 1px;">Aktivitas
                        Terkini</h6>
                    <a href="index.php?page=buku" class="text-decoration-none small text-muted">Lihat Semua</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom align-middle mb-0">
                        <thead class="bg-white">
                            <tr>
                                <th class="ps-4 text-muted fw-normal border-0">Buku</th>
                                <th class="text-muted fw-normal border-0">Donatur</th>
                                <th class="text-muted fw-normal border-0">Status</th>
                                <th class="text-end pe-4 text-muted fw-normal border-0">Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_books):
                                foreach ($recent_books as $book): ?>
                                    <tr>
                                        <td class="ps-4 py-3 border-light">
                                            <div class="d-flex align-items-center gap-3">
                                                <!-- Standardized Book Cover: 60x85px (Larger), Cover Fit, No Shrink, With Border -->
                                                <div class="rounded shadow-sm border flex-shrink-0 overflow-hidden position-relative"
                                                    style="width: 60px; height: 85px; background-color: #f8f9fa;">
                                                    <img src="assets/img/<?= htmlspecialchars($book['gambar']) ?>"
                                                        class="w-100 h-100" style="object-fit: cover; object-position: center;"
                                                        alt="book">
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark text-truncate"
                                                        style="max-width: 240px; font-size: 1rem;">
                                                        <?= htmlspecialchars($book['judul']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 border-light">
                                            <span class="text-muted small"><?= htmlspecialchars($book['username']) ?></span>
                                        </td>
                                        <td class="py-3 border-light">
                                            <?php
                                            if ($book['status_donasi'] == 'Pending')
                                                echo '<span class="px-2 py-1 rounded bg-warning bg-opacity-10 text-warning small fw-bold">Pending</span>';
                                            elseif ($book['status_donasi'] == 'Diterima')
                                                echo '<span class="px-2 py-1 rounded bg-success bg-opacity-10 text-success small fw-bold">Approved</span>';
                                            else
                                                echo '<span class="px-2 py-1 rounded bg-danger bg-opacity-10 text-danger small fw-bold">Rejected</span>';
                                            ?>
                                        </td>
                                        <td class="text-end pe-4 py-3 border-light text-muted small">
                                            <?= date('d M', strtotime($book['created_at'])) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">Belum ada aktivitas.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sidebar Actions -->
        <div class="col-12 col-lg-4">
            <!-- Action Card -->
            <div class="card p-4 mb-4 border-0">
                <div class="d-flex align-items-center mb-4">
                    <div class="flex-grow-1">
                        <h6 class="fw-bold mb-1">Butuh Tindakan</h6>
                        <p class="text-muted small mb-0">Validasi donasi yang masuk dengan cepat.</p>
                    </div>
                    <span class="badge bg-danger rounded-pill fs-6"><?= $totalPending ?></span>
                </div>
                <!-- Sleek Button -->
                <a href="index.php?page=donatur&tab=validasi"
                    class="btn btn-primary-custom w-100 mb-2 py-2 shadow-none">
                    Validasi Sekarang
                </a>
                <a href="index.php?page=buku&aksi=form" class="btn btn-light w-100 py-2 border-0 text-muted">
                    + Tambah Buku Manual
                </a>
            </div>

            <!-- Distribution (Mini) -->
            <div class="card p-4 border-0">
                <h6 class="fw-bold mb-2 small text-uppercase text-muted" style="letter-spacing: 0.5px;">Statistik
                    Kategori</h6>
                <p class="text-muted small mb-4">Sebaran jumlah buku berdasarkan kategori.</p>

                <?php if ($cat_stats):
                    foreach ($cat_stats as $stat):
                        $percent = ($stat['total'] / $totalBuku) * 100;
                        ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="d-flex align-items-center gap-2">
                                    <span class="d-inline-block rounded-circle"
                                        style="width: 8px; height: 8px; background-color: var(--primary);"></span>
                                    <span class="text-dark fw-medium small"><?= htmlspecialchars($stat['kategori']) ?></span>
                                </span>
                                <span class="badge bg-light text-muted border fw-normal"><?= $stat['total'] ?> Buku</span>
                            </div>
                            <div class="progress" style="height: 6px; background-color: #F3F4F6; border-radius: 4px;">
                                <div class="progress-bar" role="progressbar"
                                    style="width: <?= $percent ?>%; background-color: var(--primary); border-radius: 4px;"
                                    aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>