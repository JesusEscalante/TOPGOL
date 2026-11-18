<?php declare(strict_types=1);
$tituloPagina = $titulo ?? (APP_NAME . ' - Alquiler de Canchas de Futbol');
$usuarioActual = currentUser();
$esAdmin = isAdmin();
$loggedIn = isLoggedIn();

// Ruta actual normalizada (sin query string ni subcarpeta base) para marcar el menú activo
$reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$basePath = parse_url(URL_BASE, PHP_URL_PATH) ?? '';
if ($basePath !== '' && $basePath !== '/' && str_starts_with($reqPath, $basePath)) {
    $reqPath = substr($reqPath, strlen($basePath));
}
$rutaActual = '/' . trim($reqPath, '/');
if ($rutaActual === '//') {
    $rutaActual = '/';
}
$navActive = static function (string ...$rutas) use ($rutaActual): string {
    foreach ($rutas as $r) {
        $r = '/' . trim($r, '/');
        if ($r === '//') {
            $r = '/';
        }
        if ($r === '/') {
            if ($rutaActual === '/') {
                return 'active';
            }
        } elseif ($rutaActual === $r || str_starts_with($rutaActual, $r . '/')) {
            return 'active';
        }
    }
    return '';
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloPagina) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,600;0,700;0,800;0,900;1,700;1,800;1,900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || !form.getAttribute) return;
        var msg = form.getAttribute('data-swal-confirm');
        if (!msg || form.dataset.swalConfirmed === '1') return;
        e.preventDefault();
        Swal.fire({
            title: form.getAttribute('data-swal-title') || '¿Estás seguro?',
            text: msg,
            icon: form.getAttribute('data-swal-icon') || 'warning',
            showCancelButton: true,
            confirmButtonColor: form.getAttribute('data-swal-color') || '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: form.getAttribute('data-swal-btn') || 'Sí, continuar',
            cancelButtonText: 'Cancelar'
        }).then(function (res) {
            if (res.isConfirmed) {
                form.dataset.swalConfirmed = '1';
                form.submit();
            }
        });
    });
    </script>
    <style>
        /* Menú con más enlaces: que no se corten los nombres en pantallas medianas */
        .navbar-topgol .navbar-nav .nav-link { white-space: nowrap; }
        @media (min-width: 992px) and (max-width: 1399px) {
            .navbar-topgol .navbar-nav .nav-link { padding-left: .5rem; padding-right: .5rem; font-size: .88rem; }
        }
        /* Integración Tom Select - Tema TOP GOL */
        .ts-wrapper.form-select, .ts-wrapper.single { padding: 0 !important; border: none !important; background: transparent !important; box-shadow: none !important; }
        .ts-wrapper .ts-control { border-radius: 8px; border: 1px solid #cbd5e1; padding: 0.48rem 2rem 0.48rem 0.75rem; min-height: 38px; font-size: 0.9rem; color: #0f172a; background-color: #fff; box-shadow: none; transition: border-color .15s, box-shadow .15s; }
        .ts-wrapper.focus .ts-control { border-color: #1a7a3a !important; box-shadow: 0 0 0 3px rgba(26, 122, 58, 0.16) !important; }
        .ts-wrapper.ts-sm .ts-control { min-height: 31px; padding: 0.25rem 1.8rem 0.25rem 0.6rem; font-size: 0.82rem; border-radius: 7px; }
        .ts-wrapper.ts-search-card { width: 100%; }
        .ts-wrapper.ts-search-card .ts-control { border: none !important; background: transparent !important; padding: 0 18px 0 0 !important; min-height: 22px !important; font-size: 0.875rem !important; font-weight: 600 !important; color: #0f172a !important; box-shadow: none !important; }
        .input-group > .ts-wrapper { flex: 1 1 auto; width: 1%; min-width: 68px; }
        .ts-dropdown { z-index: 2050 !important; border-radius: 10px !important; border: 1px solid #e2e8f0 !important; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12) !important; font-size: 0.88rem; overflow: hidden; }
        .ts-dropdown .option { padding: 8px 12px; }
        .ts-dropdown .option.active, .ts-dropdown .option:hover { background-color: #eef7f0 !important; color: #166534 !important; font-weight: 600; }
        .ts-dropdown .option.selected { background-color: #1a7a3a !important; color: #fff !important; font-weight: 600; }
    </style>
</head>
<body>

<!-- ===== NAVBAR ===== -->
<nav class="navbar navbar-topgol navbar-expand-lg">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand" href="<?= url('/') ?>">
            <div class="brand-logo-wrap">
                <i class="bi bi-dribbble"></i>
            </div>
            <div class="brand-name">
                <span class="tg-main">TOP GOL</span>
                <span class="tg-sub">Mas que futbol</span>
            </div>
        </a>

        <!-- Toggle Mobile -->
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navTopgol" aria-controls="navTopgol" aria-expanded="false">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Links + Auth -->
        <div class="collapse navbar-collapse" id="navTopgol">
            <!-- Links Centrales -->
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $navActive('/') ?>" href="<?= url('/') ?>">Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $navActive('/canchas', '/cancha') ?>" href="<?= url('/canchas') ?>">Canchas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $navActive('/eventos', '/evento') ?>" href="<?= url('/eventos') ?>">Eventos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $navActive('/cumpleanos') ?>" href="<?= url('/cumpleanos/solicitar') ?>">Cumpleaños y reuniones</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $navActive('/contacto') ?>" href="<?= url('/contacto') ?>">Contacto</a>
                </li>
                <?php if ($loggedIn && !$esAdmin): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $navActive('/mis-reservas') ?>" href="<?= url('/mis-reservas') ?>">Mis Reservas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $navActive('/mis-solicitudes') ?>" href="<?= url('/mis-solicitudes') ?>">Mis solicitudes</a>
                    </li>
                <?php endif; ?>
            </ul>

            <!-- Auth Buttons -->
            <div class="d-flex align-items-center gap-2">
                <?php if ($loggedIn): ?>
                    <div class="dropdown">
                        <button class="btn-nav-user nav-link dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                            <?php if (!empty($usuarioActual['avatar'])): ?>
                                <img src="<?= htmlspecialchars($usuarioActual['avatar']) ?>" alt="Avatar" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;">
                            <?php else: ?>
                                <i class="bi bi-person-circle fs-4"></i>
                            <?php endif; ?>
                            <span><?= htmlspecialchars($usuarioActual['nombre'] ?? 'Usuario') ?></span>
                            <?php if ($esAdmin): ?><span class="badge-admin-nav">Admin</span><?php endif; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" style="border-radius:12px; min-width:220px;">
                            <li class="px-3 py-2 border-bottom d-flex align-items-center gap-3">
                                <?php if (!empty($usuarioActual['avatar'])): ?>
                                    <img src="<?= htmlspecialchars($usuarioActual['avatar']) ?>" alt="Avatar" class="rounded-circle" style="width:48px;height:48px;object-fit:cover;">
                                <?php else: ?>
                                    <i class="bi bi-person-circle fs-1 text-secondary"></i>
                                <?php endif; ?>
                                <div>
                                    <div class="fw-semibold small text-dark"><?= htmlspecialchars($usuarioActual['nombre'] ?? '') ?></div>
                                    <div class="text-muted" style="font-size:0.72rem;"><?= htmlspecialchars($usuarioActual['email'] ?? '') ?></div>
                                </div>
                            </li>
                            <?php if ($esAdmin): ?>
                                <li><a class="dropdown-item py-2" href="<?= url('/admin/dashboard') ?>"><i class="bi bi-speedometer2 me-2 text-success"></i>Panel Admin</a></li>
                                <li><a class="dropdown-item py-2" href="<?= url('/cancha/crear') ?>"><i class="bi bi-plus-square me-2 text-success"></i>Nueva Cancha</a></li>
                                <li><a class="dropdown-item py-2" href="<?= url('/admin/usuarios') ?>"><i class="bi bi-people me-2 text-success"></i>Usuarios</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item py-2" href="<?= url('/mis-reservas') ?>"><i class="bi bi-calendar-event me-2 text-success"></i>Mis Reservas</a></li>
                                <li><a class="dropdown-item py-2" href="<?= url('/reserva/crear') ?>"><i class="bi bi-plus-circle me-2 text-success"></i>Nueva Reserva</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item py-2" href="<?= url('/perfil') ?>"><i class="bi bi-person-gear me-2 text-success"></i>Mi perfil</a></li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li><a class="dropdown-item py-2 text-danger" href="<?= url('/logout') ?>"><i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesion</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= url('/login') ?>" class="btn-nav-login">
                        <i class="bi bi-person"></i> Iniciar sesion
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Flash Messages (SweetAlert2) -->
<?= renderFlashMessages() ?>

<!-- Main -->
<main class="main-content">