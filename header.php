<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Donasi Buku Bekas - Admin Panel</title>

    <!-- Fonts: Inter for UI, Plus Jakarta Sans for Headers -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap"
        rel="stylesheet">

    <!-- Bootstrap & Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- SweetAlert2 (Premium Popups) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- jQuery (Required for some plugins if any, good to have) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <style>
        :root {
            /* Palette: Mahogany Red & Clean SaaS Neutrals */
            --primary: #A63A3A;
            --primary-hover: #8F3030;
            --primary-subtle: #FDF2F2;

            --bg-body: #F9FAFB;
            --bg-surface: #FFFFFF;

            --text-main: #111827;
            --text-muted: #6B7280;
            --border-color: #E5E7EB;

            /* Spacing & Radius */
            --radius-md: 12px;
            --radius-lg: 16px;

            /* Shadows - Soft & Static (Exclusive Feel) */
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            /* Diffuse, clean shadow for cards */
            --shadow-card: 0 4px 24px rgba(0, 0, 0, 0.04);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        .font-heading {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-main);
            letter-spacing: -0.01em;
        }

        /* --- LAYOUT --- */
        /* Sidebar styling should be handled by sidebar.php, assuming it has its own or we style it here if needed. 
           For safety, we ensure the wrapper has margin. */
        .content-wrapper {
            margin-left: 260px;
            /* Aligns with sidebar width */
            padding: 2.5rem;
            padding-top: 100px;
            /* Clear fixed navbar */
            min-height: 100vh;
        }

        /* Navbar Styling */
        .navbar-admin {
            background-color: var(--bg-surface);
            border-bottom: 1px solid var(--border-color);
            height: 80px;
            margin-left: 260px;
            /* Match sidebar */
            padding: 0 2.5rem;
            z-index: 1020;
            width: calc(100% - 260px);
        }

        /* --- SIDEBAR STYLING (Centralized) --- */
        .sidebar-admin {
            width: 260px;
            height: 100vh;
            background-color: var(--primary);
            /* Uses Global Variable */
            border-right: 1px solid rgba(0, 0, 0, 0.05);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }

        .sidebar-admin .logo-area {
            padding: 25px 20px;
            font-weight: 800;
            font-size: 22px;
            color: white;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
            background-color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sidebar-admin .nav-link {
            color: rgba(255, 255, 255, 0.75);
            font-weight: 500;
            padding: 14px 20px;
            margin: 5px 15px;
            border-radius: 10px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            font-size: 15px;
            text-decoration: none;
        }

        .sidebar-admin .nav-link i {
            font-size: 18px;
            margin-right: 12px;
            width: 25px;
            text-align: center;
        }

        .sidebar-admin .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .sidebar-admin .nav-link.active {
            background-color: #FFFFFF !important;
            color: var(--primary) !important;
            font-weight: 700 !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1) !important;
        }

        .logout-area {
            margin-top: auto;
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logout-area .nav-link {
            color: white !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            background-color: transparent !important;
            justify-content: center;
        }

        .logout-area .nav-link:hover {
            background-color: white !important;
            color: var(--primary) !important;
        }


        /* --- COMPONENTS --- */

        /* Cards: Static, Clean, Premium */
        .card {
            background-color: var(--bg-surface);
            border: 1px solid rgba(0, 0, 0, 0.04);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-card);
            /* No hover transform for 'exclusive/solid' feel */
        }

        /* Buttons */
        .btn {
            padding: 0.6rem 1.25rem;
            font-weight: 500;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .btn-primary-custom {
            background-color: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        .btn-primary-custom:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            color: white;
        }

        /* Tables */
        .table-custom {
            width: 100%;
        }

        .table-custom thead th {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            background-color: #F9FAFB;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            border-top: none;
        }

        .table-custom tbody td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #F3F4F6;
            vertical-align: middle;
            font-size: 0.875rem;
        }

        .table-custom tbody tr:last-child td {
            border-bottom: none;
        }

        /* Mobile Responsive */
        @media (max-width: 991px) {

            .content-wrapper,
            .navbar-admin {
                margin-left: 0;
                width: 100%;
            }

            .sidebar {
                display: none;
                /* Or handle mobile menu toggle */
            }
        }
    </style>
</head>

<body>

    <?php
    // Include sidebar (Assumes sidebar.php exists in same directory)
    include __DIR__ . '/sidebar.php';
    ?>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-admin fixed-top d-flex justify-content-between align-items-center">
        <!-- Left: Breadcrumb/Title placeholder -->
        <div>
            <span class="text-muted small fw-medium">Admin Portal</span>
        </div>

        <!-- Right: User Profile -->
        <div class="d-flex align-items-center gap-3">
            <div class="text-end d-none d-md-block" style="line-height: 1.2;">
                <div class="fw-bold text-dark" style="font-size: 0.9rem;">Administrator</div>
                <div class="text-muted" style="font-size: 0.75rem;">Super User</div>
            </div>
            <div
                style="width: 40px; height: 40px; background: var(--bg-surface); border: 1px solid #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-sm);">
                <i class="bi bi-person-fill text-muted fs-5"></i>
            </div>
        </div>
    </nav>

    <!-- Main Content Wrapper Starts Here -->
    <div class="content-wrapper">