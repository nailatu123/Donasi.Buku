<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT id, password_hash, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        // --- LOGIKA PEMISAH HALAMAN ---

        if ($user['role'] === 'admin') {
            header('Location: index.php?page=dashboard');
        } elseif ($user['role'] === 'penerima') {
            header('Location: index.php?page=area_penerima');
        } else { // DONATUR
            header('Location: index.php?page=area_donatur');
        }
        exit;

    } else {
        $error = "Username atau password salah.";
    }
}

// logout logic
if (!empty($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Login - Donasi Buku Bekas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        /* --- TEMA COKLAT MAHOGANY & KREM --- */
        :root {
            --color-mahogany: #A63A3A;
            /* Coklat Mahogany */
            --color-light-cream: #F7E9E3;
            /* Krem Muda */
            --color-cream-bg: #FCF5F3;
            /* Background Krem yang sangat lembut */
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--color-cream-bg) !important;
            /* Perubahan utama: untuk memastikan footer ada di bawah */
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .content-wrapper {
            flex-grow: 1;
            /* Kontainer yang mendorong footer ke bawah */
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-box {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(166, 58, 58, 0.1);
            width: 100%;
            max-width: 400px;
            border-top: 5px solid var(--color-mahogany);
        }

        /* Tombol Primary (Masuk) */
        .btn-primary {
            background-color: var(--color-mahogany);
            border-color: var(--color-mahogany);
        }

        .btn-primary:hover {
            background-color: #8F3030;
            /* Mahogany sedikit gelap untuk hover */
            border-color: #8F3030;
        }

        /* Judul */
        h2 {
            color: var(--color-mahogany);
            font-weight: 700;
        }

        /* Input field focus */
        .form-control:focus {
            border-color: var(--color-mahogany);
            box-shadow: 0 0 0 0.25rem rgba(166, 58, 58, 0.25);
        }

        /* Tautan Daftar */
        .login-box a[href="register.php"] {
            color: var(--color-mahogany) !important;
        }

        /* Gaya untuk Footer */
        .app-footer {
            background-color: var(--color-mahogany);
        }
    </style>
</head>

<body>

    <div class="content-wrapper">
        <div class="login-box mx-auto">
            <h2 class="mb-4 text-center">Login Sistem</h2>
            <?php
            if (isset($_GET['registered']) && $_GET['registered'] == 'success') {
                echo '<div class="alert alert-success">Registrasi berhasil! Silakan login.</div>';
            }
            if (isset($error))
                echo '<div class="alert alert-danger">' . $error . '</div>';
            ?>

            <form method="post">
                <div class="mb-3">
                    <label class="form-label fw-bold">Username</label>
                    <input type="text" name="username" class="form-control" required autofocus>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Password</label>
                    <div class="input-group">
                        <input type="password" name="password" class="form-control" id="login_password" required>
                        <button class="btn btn-outline-secondary toggle-password" type="button"
                            data-target="login_password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 mt-3 py-2 fw-bold">Masuk</button>

                <div class="text-center mt-4">
                    <p class="small mb-0">Belum punya akun?</p>
                    <a href="register.php" class="text-decoration-none fw-bold"
                        style="color: var(--color-mahogany);">Daftar disini</a>
                </div>

                <div class="text-center mt-2">
                    <a href="index.php" class="text-decoration-none text-muted small">&larr; Kembali ke Beranda</a>
                </div>
            </form>
        </div>
    </div>

    <footer class="text-center py-3 text-white app-footer">
        <div class="container">
            <small>
                &copy; <?php echo date("Y"); ?> <strong>Sistem Manajemen Donasi Buku Bekas</strong><br>
                <span style="font-size: 0.85em; opacity: 0.8;">
                    Kelompok: Lidya Darma, Luthfi Safitri, Nailatu Salsabilla Agustin
                </span>
            </small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Ambil semua tombol dengan class 'toggle-password'
            const toggleButtons = document.querySelectorAll('.toggle-password');

            toggleButtons.forEach(button => {
                button.addEventListener('click', function (event) {
                    event.preventDefault(); // Mencegah submit form

                    // Ambil ID dari input yang harus diubah (dari data-target)
                    const targetId = this.getAttribute('data-target');
                    const passwordInput = document.getElementById(targetId);
                    const icon = this.querySelector('i');

                    // Periksa jenis input saat ini
                    if (passwordInput.type === 'password') {
                        // Jika password, ubah ke teks dan ubah ikon
                        passwordInput.type = 'text';
                        icon.classList.remove('bi-eye');
                        icon.classList.add('bi-eye-slash'); // Ikon mata dicoret
                    } else {
                        // Jika teks, ubah kembali ke password dan ubah ikon
                        passwordInput.type = 'password';
                        icon.classList.remove('bi-eye-slash');
                        icon.classList.add('bi-eye'); // Ikon mata normal
                    }
                });
            });
        });

    </script>

</body>

</html>