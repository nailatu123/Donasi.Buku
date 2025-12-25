<div class="sidebar-admin">
    <div class="logo-area">
        <i class="bi bi-journal-bookmark-fill"></i> Admin
    </div>

    <nav class="nav flex-column mt-4">

        <a class="nav-link <?= (!isset($_GET['page']) || $_GET['page'] == 'dashboard') ? 'active' : '' ?>"
            href="index.php?page=dashboard">
            <i class="bi bi-grid-fill"></i> Dashboard
        </a>

        <a class="nav-link <?= (isset($_GET['page']) && $_GET['page'] == 'donatur') ? 'active' : '' ?>"
            href="index.php?page=donatur">
            <i class="bi bi-people-fill"></i> Data Donatur
        </a>

        <a class="nav-link <?= (isset($_GET['page']) && $_GET['page'] == 'buku') ? 'active' : '' ?>"
            href="index.php?page=buku">
            <i class="bi bi-book-half"></i> Data Buku
        </a>

        <a class="nav-link <?= (isset($_GET['page']) && $_GET['page'] == 'penerima') ? 'active' : '' ?>"
            href="index.php?page=penerima">
            <i class="bi bi-person-badge-fill"></i> Data Penerima
        </a>

    </nav>

    <div class="logout-area">
        <a class="nav-link fw-bold border bg-white justify-content-center" href="#" onclick="confirmLogout(event)">
            <i class="bi bi-box-arrow-right"></i> Keluar
        </a>
    </div>
</div>

<script>
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
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'index.php?logout=true';
            }
        })
    }
</script>
</div>
</div>