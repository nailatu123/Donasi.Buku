<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$error = '';
$old_input = [
    'role' => '',
    'username' => '',
    'kontak' => '',
    'alamat' => ''
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Ambil & Sanitasi Input
    $role = $_POST['role'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $no_wa = trim($_POST['no_wa'] ?? ''); // Ubah dari 'kontak' ke 'no_wa' biar sesuai DB
    $alamat = trim($_POST['alamat'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Simpan input lama biar gak hilang pas error
    $old_input['role'] = $role;
    $old_input['username'] = $username;
    $old_input['kontak'] = $no_wa; // Mapping ke key 'kontak' untuk tampilan value
    $old_input['alamat'] = $alamat;

    // 2. Validasi Sederhana
    if (empty($username) || empty($no_wa) || empty($alamat) || empty($password)) {
        $error = "Semua kolom wajib diisi!";
    } elseif ($password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        // 3. Cek Username Kembar
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            $error = "Username sudah digunakan, pilih yang lain.";
        } else {
            // 4. Proses Insert
            try {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $status = 'aktif'; // Default status

                $sql = "INSERT INTO users (username, password_hash, role, no_wa, alamat, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())";
                $stmtInsert = $pdo->prepare($sql);
                $stmtInsert->execute([$username, $password_hash, $role, $no_wa, $alamat, $status]);

                // Redirect ke Login dengan pesan sukses
                header("Location: login.php?registered=success");
                exit;

            } catch (PDOException $e) {
                $error = "Terjadi kesalahan sistem: " . $e->getMessage();
            }
        }
    }
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Daftar Akun - Donasi Buku Bekas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --color-mahogany: #A63A3A;
            --color-mahogany-dark: #8F3030;
            --color-cream-bg: #FCF5F3;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--color-cream-bg) !important;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .content-wrapper {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 50px 0;
        }

        .login-box {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(166, 58, 58, 0.1);
            width: 100%;
            max-width: 450px;
            border-top: 5px solid var(--color-mahogany);
        }

        .btn-primary {
            background-color: var(--color-mahogany);
            border-color: var(--color-mahogany);
        }

        .btn-primary:hover {
            background-color: var(--color-mahogany-dark);
            border-color: var(--color-mahogany-dark);
        }

        h2 {
            color: var(--color-mahogany);
            font-weight: 700;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--color-mahogany);
            box-shadow: 0 0 0 0.25rem rgba(166, 58, 58, 0.25);
        }

        .login-box a[href="login.php"] {
            color: var(--color-mahogany) !important;
        }

        .input-group>.btn-outline-secondary {
            border-color: #ced4da;
            /* Agar match dengan border input */
            z-index: 10;
            /* Ensure button is clickable */
            cursor: pointer;
        }
    </style>
</head>

<body>

    <div class="content-wrapper">
        <div class="login-box mx-auto">
            <h2 class="mb-2 text-center">Daftar Akun</h2>
            <p class="text-center text-muted mb-4">Bergabunglah bersama kami.</p>

            <?php if (isset($error))
                echo '<div class="alert alert-danger">' . $error . '</div>'; ?>

            <form method="post">
                <div class="mb-3">
                    <label class="form-label fw-bold">Daftar Sebagai</label>
                    <select name="role" class="form-select" required>
                        <option value="donatur" <?php echo (($old_input['role'] ?? '') == 'donatur') ? 'selected' : ''; ?>>Donatur
                            (Saya ingin menyumbang buku)</option>
                        <option value="penerima" <?php echo (($old_input['role'] ?? '') == 'penerima') ? 'selected' : ''; ?>>
                            Penerima (Saya ingin mencari buku)</option>
                    </select>
                </div>

                <h5 class="mt-4 mb-3 text-secondary">Data Diri</h5>

                <div class="mb-3">
                    <label class="form-label fw-bold">No. Kontak (WA)</label>
                    <input type="text" name="no_wa" class="form-control" required
                        placeholder="Nomor WhatsApp aktif Anda"
                        value="<?php echo htmlspecialchars($old_input['kontak'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Alamat Lengkap</label>
                    <textarea name="alamat" class="form-control" rows="3" required
                        placeholder="Alamat lengkap untuk pengiriman/pengambilan"><?php echo htmlspecialchars($old_input['alamat'] ?? ''); ?></textarea>
                </div>

                <h5 class="mt-4 mb-3 text-secondary">Data Akun</h5>

                <div class="mb-3">
                    <label class="form-label fw-bold">Username</label>
                    <input type="text" name="username" class="form-control" required placeholder="Buat username unik"
                        value="<?php echo htmlspecialchars($old_input['username'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Password</label>
                    <div class="input-group">
                        <input type="password" name="password" class="form-control" id="password" required
                            placeholder="Minimal 6 karakter" minlength="6">
                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Konfirmasi Password</label>
                    <div class="input-group">
                        <input type="password" name="confirm_password" class="form-control" id="confirm_password"
                            required placeholder="Ulangi password">
                        <button class="btn btn-outline-secondary toggle-password" type="button"
                            data-target="confirm_password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" name="register" class="btn btn-primary w-100 mt-2 py-2 fw-bold">Daftar
                    Sekarang</button>

                <div class="text-center mt-4">
                    <p class="small mb-0">Sudah punya akun?</p>
                    <a href="login.php" class="text-decoration-none fw-bold" style="color: var(--color-mahogany);">Login
                        disini</a>
                </div>
                <div class="text-center mt-2">
                    <a href="index.php" class="text-decoration-none small text-muted">&larr; Kembali ke Beranda</a>
                </div>
            </form>
        </div>
    </div>

    <footer class="bg-white py-3 border-top">
        <div class="container text-center">
            <p class="text-muted small mb-0">
                &copy; <?php echo date("Y"); ?>
                Kelompok 7 [Lidya Darma, Luthfi Safitri, Nailatu Salsabilla Agustin].
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggleButtons = document.querySelectorAll('.toggle-password');

            toggleButtons.forEach(button => {
                button.addEventListener('click', function (e) {
                    // Prevent any form submission weirdness (though type="button" handles it)
                    e.preventDefault();

                    const targetId = this.getAttribute('data-target');
                    const inputElement = document.getElementById(targetId);
                    const iconElement = this.querySelector('i');

                    if (inputElement.type === 'password') {
                        inputElement.type = 'text';
                        iconElement.classList.remove('bi-eye');
                        iconElement.classList.add('bi-eye-slash');
                    } else {
                        inputElement.type = 'password';
                        iconElement.classList.remove('bi-eye-slash');
                        iconElement.classList.add('bi-eye');
                    }
                });
            });
        });
    </script>
</body>

</html>