<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// --- [1] LOGIKA LOGOUT ---
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

// ==========================================================
// [2] JIKA BELUM LOGIN -> TAMPILKAN LANDING PAGE (Coklat Mahogany & Krem)
// ==========================================================
if (!isset($_SESSION['user_id'])) {
    ?>
    <!DOCTYPE html>
    <html lang="id">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sistem Informasi Donasi Buku Bekas</title>

        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
            rel="stylesheet">

        <style>
            /* --- CSS LANDING PAGE (TEMA COKLAT MAHOGANY & KREM) --- */

            /* Definisikan Warna Baru sebagai Variabel */
            :root {
                --color-mahogany: #A63A3A;
                /* Coklat Mahogany */
                --color-light-cream: #F7E9E3;
                /* Krem Muda */
                --color-text: #333;
                --color-white: #ffffff;
            }

            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-family: 'Poppins', sans-serif;
                scroll-behavior: smooth;
            }

            body {
                overflow-x: hidden;
                color: var(--color-text);
            }

            /* Navbar */
            nav {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 20px 60px;
                background-color: var(--color-white);
                position: sticky;
                top: 0;
                z-index: 100;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            }

            .logo {
                font-size: 26px;
                font-weight: 800;
                color: var(--color-mahogany);
                text-transform: uppercase;
            }

            /* Mahogany */
            .nav-links {
                display: flex;
                gap: 30px;
                list-style: none;
            }

            .nav-links a {
                text-decoration: none;
                color: var(--color-mahogany);
                font-weight: 600;
                font-size: 14px;
                transition: 0.3s;
            }

            .nav-links a:hover {
                color: #8F3030;
            }

            /* Mahogany sedikit gelap untuk hover */

            /* Hero Section (Background Mahogany Gradient) */
            .hero {
                height: 90vh;
                width: 100%;
                display: flex;
                align-items: center;
                padding: 0 60px;
                position: relative;
                /* Gradasi Mahogany agar tulisan putih terbaca jelas */
                background: linear-gradient(to right, rgba(166, 58, 58, 0.95) 30%, rgba(166, 58, 58, 0.6) 100%),
                    url('https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80');
                background-size: cover;
                background-position: center;
            }

            .hero-content {
                max-width: 600px;
                z-index: 2;
                color: var(--color-white);
            }

            .sub-title {
                font-size: 20px;
                font-weight: 600;
                color: var(--color-light-cream);
                letter-spacing: 2px;
                display: block;
                margin-bottom: 10px;
                text-transform: uppercase;
            }

            .main-title {
                font-size: 54px;
                font-weight: 800;
                color: var(--color-white);
                line-height: 1.1;
                margin-bottom: 20px;
                text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            }

            .hero p {
                font-size: 16px;
                line-height: 1.6;
                margin-bottom: 30px;
                font-weight: 400;
                color: #f8f9fa;
                max-width: 90%;
            }

            /* Tombol */
            .btn-daftar {
                display: inline-block;
                padding: 14px 40px;
                background-color: var(--color-light-cream);
                color: var(--color-mahogany);
                text-decoration: none;
                border-radius: 50px;
                font-weight: 700;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
                transition: 0.3s;
            }

            .btn-daftar:hover {
                background-color: var(--color-white);
                transform: translateY(-2px);
            }

            /* Sections */
            .section {
                padding: 80px 60px;
            }

            .section-title {
                text-align: center;
                font-size: 32px;
                font-weight: 700;
                color: var(--color-mahogany);
                margin-bottom: 50px;
            }

            #tentang {
                background-color: var(--color-white);
            }

            /* Cards */
            .features-grid {
                display: flex;
                gap: 30px;
                justify-content: center;
                flex-wrap: wrap;
            }

            .feature-card {
                background: var(--color-white);
                border: 1px solid var(--color-light-cream);
                padding: 30px;
                border-radius: 15px;
                width: 300px;
                text-align: center;
                box-shadow: 0 5px 20px rgba(0, 0, 0, 0.03);
                transition: 0.3s;
            }

            .feature-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 25px rgba(166, 58, 58, 0.15);
            }

            .icon {
                font-size: 40px;
                margin-bottom: 20px;
                display: block;
            }

            .feature-card h3 {
                margin-bottom: 10px;
                color: var(--color-mahogany);
            }

            .feature-card p {
                font-size: 14px;
                color: #666;
                line-height: 1.5;
            }

            /* Steps */
            #syarat {
                background-color: #FCF5F3;
            }

            /* Warna background krem lebih gelap */
            .steps-container {
                display: flex;
                gap: 20px;
                justify-content: center;
                flex-wrap: wrap;
            }

            .step-box {
                background: var(--color-white);
                padding: 30px;
                border-radius: 10px;
                width: 250px;
                position: relative;
                border-left: 5px solid var(--color-mahogany);
            }

            .step-number {
                position: absolute;
                top: -15px;
                right: -15px;
                background: var(--color-mahogany);
                color: var(--color-white);
                width: 40px;
                height: 40px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
            }

            .step-box h3 {
                color: var(--color-mahogany);
                font-size: 18px;
                margin-bottom: 10px;
            }

            .step-box p {
                font-size: 13px;
                color: #555;
            }

            footer {
                background-color: var(--color-mahogany);
                color: var(--color-white);
                text-align: center;
                padding: 20px;
                font-size: 14px;
            }
        </style>
    </head>

    <body>
        <nav>
            <div class="logo">DONASIBUKU.</div>
            <ul class="nav-links">
                <li><a href="#home">Beranda</a></li>
                <li><a href="#tentang">Tentang</a></li>
                <li><a href="#syarat">Syarat Donasi</a></li>
                <li><a href="login.php">Masuk / Daftar</a></li>
            </ul>
        </nav>
        <section id="home" class="hero">
            <div class="hero-content">
                <span class="sub-title">Sistem Informasi</span>
                <h1 class="main-title">Donasi Buku Bekas,<br>Tebar Manfaat.</h1>
                <p>Wadah digital bagi mahasiswa dan masyarakat umum untuk mendonasikan buku, berkonsultasi ketersediaan
                    buku, dan melakukan distribusi ilmu secara cepat.</p>
                <a href="login.php" class="btn-daftar">Mulai Donasi</a>
            </div>
        </section>
        <section id="tentang" class="section">
            <h2 class="section-title">Mengapa Donasi Di Sini?</h2>
            <div class="features-grid">
                <div class="feature-card"><span class="icon">📚</span>
                    <h3>Mudah & Cepat</h3>
                    <p>Proses donasi buku dilakukan secara online tanpa ribet.</p>
                </div>
                <div class="feature-card"><span class="icon">🔍</span>
                    <h3>Transparan</h3>
                    <p>Anda dapat memantau status buku yang didonasikan.</p>
                </div>
                <div class="feature-card"><span class="icon">🤝</span>
                    <h3>Bermanfaat Luas</h3>
                    <p>Buku akan disalurkan kepada yang membutuhkan.</p>
                </div>
            </div>
        </section>
        <section id="syarat" class="section">
            <h2 class="section-title">Syarat & Cara Donasi</h2>
            <div class="steps-container">
                <div class="step-box">
                    <div class="step-number">1</div>
                    <h3>Kondisi Layak</h3>
                    <p>Buku harus dalam kondisi layak baca.</p>
                </div>
                <div class="step-box">
                    <div class="step-number">2</div>
                    <h3>Buku Edukasi</h3>
                    <p>Diutamakan buku pelajaran atau referensi.</p>
                </div>
                <div class="step-box">
                    <div class="step-number">3</div>
                    <h3>Login & Upload</h3>
                    <p>Daftar akun, lalu isi formulir donasi.</p>
                </div>
                <div class="step-box">
                    <div class="step-number">4</div>
                    <h3>Konfirmasi</h3>
                    <p>Tunggu admin memverifikasi donasi Anda.</p>
                </div>
            </div>
        </section>
        <footer>
            <p>
                &copy; 2025 <strong>Kelompok 7</strong><br>
                <span style="font-size: 12px;">
                    Lidya Septia Nita Darmawati, Luthfi Safitri, Nailatu Salsabilla Agustin
                </span>
            </p>
        </footer>
    </body>

    </html>
    <?php
    exit;
}

// ==========================================================
// [3] JIKA SUDAH LOGIN -> CEK HALAMAN
// ==========================================================
// ... (Bagian ini tidak diubah karena fokus pada Landing Page)
$view = $_GET['page'] ?? 'dashboard';

// A. JIKA HALAMAN KHUSUS USER (DONATUR / PENERIMA)
if ($view === 'area_donatur' || $view === 'area_penerima') {

    echo '<!DOCTYPE html><html lang="id"><head>';
    echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>Area Pengguna</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">';
    echo '<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">';

    // INCLUDE CSS CUSTOM (Supaya variabel warna Maroon/Pink terbaca)
    echo '<link rel="stylesheet" href="assets/css/custom.css?v=' . time() . '">';

    echo '<style>body{font-family:"Poppins",sans-serif; background:#f8f9fa;}</style>';
    // JS SCRIPTS (Bootstrap Bundle & SweetAlert2) moved to HEAD to ensure availability
    echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>';
    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
    echo '</head><body>';

    // Load View Sesuai Role
    if ($view === 'area_donatur') {
        include __DIR__ . '/../views/area_donatur.php';
    } elseif ($view === 'area_penerima') {
        include __DIR__ . '/../views/area_penerima.php';
    }

    echo '</body></html>';

    echo '</body></html>';
    exit; // Stop disini, jangan load footer admin
}

// B. JIKA HALAMAN ADMIN (Dashboard, Buku, dll)
include_once __DIR__ . '/../views/layout/header.php';

switch ($view) {
    case 'dashboard':
        include __DIR__ . '/../views/dashboard.php';
        break;
    case 'donatur':
        include __DIR__ . '/../views/donatur.php';
        break;
    case 'buku':
        include __DIR__ . '/../views/buku.php';
        break;
    case 'penerima':
        include __DIR__ . '/../views/penerima.php';
        break;
    default:
        include __DIR__ . '/../views/dashboard.php';
}

include_once __DIR__ . '/../views/layout/footer.php';
?>