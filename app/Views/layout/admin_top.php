<?php
declare(strict_types=1);

/**
 * ====================================================================
 * TOP GOL - Layout Administrador (apertura)
 * ====================================================================
 * Sidebar + barra superior del panel admin. Las vistas del panel solo
 * definen $adminMenu ('dashboard', 'reservas', ...) y el contenido.
 */

$usuarioAdm = currentUser();
$nombreAdmin = trim(explode(' ', (string)($usuarioAdm['nombre'] ?? ''))[0] ?? '');
if ($nombreAdmin === '') {
    $nombreAdmin = 'Administrador';
}
$inicial = mb_strtoupper(mb_substr($nombreAdmin, 0, 1));

$adminMenuActivo = $adminMenu ?? 'dashboard';

// Contador de la pestaña "Por verificar" (mismo criterio que la lista de Caja)
$conteoVerifAdm = 0;
try {
    $conteoVerifAdm = (new \App\Models\Reserva())->contarPagosPorVerificar();
} catch (\Throwable $e) {
    $conteoVerifAdm = 0;
}

// Ruta actual normalizada (sin query ni subcarpeta): el grupo y la pestaña activos se deciden por la URL,
// así cada pantalla resalta SU grupo y SU pestaña (por ejemplo, Venta rápida ya no marca Historial de ventas).
$reqPathAdm = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$basePathAdm = parse_url(URL_BASE, PHP_URL_PATH) ?? '';
if ($basePathAdm !== '' && $basePathAdm !== '/' && str_starts_with($reqPathAdm, $basePathAdm)) {
    $reqPathAdm = substr($reqPathAdm, strlen($basePathAdm));
}
$rutaAdm = '/' . trim($reqPathAdm, '/');

// Menú corto: las pantallas hermanas pasan a pestañas dentro de su grupo (las rutas son las mismas de siempre).
$nav = [
    ['key' => 'dashboard',  'label' => 'Inicio',     'ico' => 'bi-house',          'href' => url('/admin/dashboard'), 'tabs' => []],
    ['key' => 'calendario', 'label' => 'Calendario', 'ico' => 'bi-calendar3',      'href' => url('/admin/calendario'), 'tabs' => []],
    ['key' => 'reservas',   'label' => 'Reservas',   'ico' => 'bi-calendar-check', 'href' => url('/admin/reservas'), 'tabs' => [
        ['key' => 'reservas', 'label' => 'Reservas',               'href' => url('/admin/reservas')],
        ['key' => 'espacios', 'label' => 'Cumpleaños y reuniones', 'href' => url('/admin/espacios')],
    ]],
    ['key' => 'caja',       'label' => 'Caja',       'ico' => 'bi-cash-coin',      'href' => url('/admin/pagos?tab=revision'), 'tabs' => [
        ['key' => 'porverificar', 'label' => 'Por verificar',      'href' => url('/admin/pagos?tab=revision'), 'badge' => $conteoVerifAdm],
        ['key' => 'cobrar',       'label' => 'Cobrar saldos',      'href' => url('/admin/pagos?tab=pendientes')],
        ['key' => 'caja',         'label' => 'Caja del día',       'href' => url('/admin/caja')],
        ['key' => 'historial',    'label' => 'Historial de cobros', 'href' => url('/admin/pagos?tab=historial')],
    ]],
    ['key' => 'ventas',     'label' => 'Ventas',     'ico' => 'bi-cart-check',     'href' => url('/admin/ventas/pos'), 'tabs' => [
        ['key' => 'pos',    'label' => 'Venta rápida',        'href' => url('/admin/ventas/pos')],
        ['key' => 'ventas', 'label' => 'Historial de ventas', 'href' => url('/admin/ventas')],
    ]],
    ['key' => 'productos',  'label' => 'Productos',  'ico' => 'bi-box',            'href' => url('/admin/productos'), 'tabs' => [
        ['key' => 'productos',  'label' => 'Productos',          'href' => url('/admin/productos')],
        ['key' => 'categorias', 'label' => 'Categorías',         'href' => url('/admin/categorias')],
        ['key' => 'inventario', 'label' => 'Inventario y stock', 'href' => url('/admin/inventario')],
    ]],
    ['key' => 'reportes',   'label' => 'Reportes',   'ico' => 'bi-bar-chart-line', 'href' => url('/admin/reportes'), 'tabs' => []],
    ['key' => 'canchas',    'label' => 'Canchas',    'ico' => 'bi-grid',           'href' => url('/canchas'), 'tabs' => []],
];

// Pantalla (pestaña) activa según la ruta; el valor que manda la vista ($adminMenu) es el respaldo.
$tabPorRutaAdm = [
    '/admin/dashboard' => 'dashboard', '/admin/calendario' => 'calendario',
    '/admin/espacios' => 'espacios', '/admin/reservas' => 'reservas', '/admin/reserva' => 'reservas', '/reservas' => 'reservas',
    '/admin/caja' => 'caja', '/admin/pagos' => 'porverificar', '/admin/pago' => 'porverificar',
    '/admin/ventas/pos' => 'pos', '/admin/ventas' => 'ventas',
    '/admin/productos' => 'productos', '/admin/categorias' => 'categorias', '/admin/inventario' => 'inventario',
    '/admin/reportes' => 'reportes', '/canchas' => 'canchas',
];
$tabActivaAdm = $adminMenuActivo;
$mejorLargoAdm = 0;
foreach ($tabPorRutaAdm as $prefijoNav => $claveNav) {
    if (($rutaAdm === $prefijoNav || str_starts_with($rutaAdm, $prefijoNav . '/')) && strlen($prefijoNav) > $mejorLargoAdm) {
        $tabActivaAdm = $claveNav;
        $mejorLargoAdm = strlen($prefijoNav);
    }
}
// Caja: las pestañas de /admin/pagos se distinguen por ?tab= (revision | pendientes | historial)
if ($tabActivaAdm === 'pagos') { $tabActivaAdm = 'porverificar'; }
if ($tabActivaAdm === 'porverificar' && str_starts_with($rutaAdm, '/admin/pagos')) {
    $tabPagosAdm = (string)($_GET['tab'] ?? 'revision');
    $tabActivaAdm = match ($tabPagosAdm) { 'pendientes' => 'cobrar', 'historial', 'pagadas' => 'historial', default => 'porverificar' };
}
// Grupo activo = el grupo que contiene esa pestaña
$grupoActivoAdm = $tabActivaAdm;
$tabsGrupoAdm = [];
foreach ($nav as $itemNav) {
    foreach ($itemNav['tabs'] as $tabNav) {
        if ($tabNav['key'] === $tabActivaAdm) {
            $grupoActivoAdm = $itemNav['key'];
            $tabsGrupoAdm = $itemNav['tabs'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo ?? 'Panel Administrador - TOP GOL') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
</head>
<body class="adm-standalone">

<style>
    html { background: #f4f7fb; }
    body.adm-standalone { background: #f4f7fb; margin: 0; font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; }
    .adm-page { min-height: 100vh; }
    .adm-wrap { display: flex; gap: 0; align-items: stretch; min-height: 100vh; padding-left: 220px; }
    .adm-side { width: 220px; flex-shrink: 0; background: linear-gradient(180deg, #0b1e33 0%, #081424 100%); color: #fff; display: flex; flex-direction: column; position: fixed; top: 0; left: 0; bottom: 0; z-index: 1030; overflow-y: auto; overscroll-behavior: contain; }
    .adm-burger { display: none; border: none; background: none; font-size: 1.4rem; color: #101c33; margin-right: auto; padding: 4px 8px; }
    .adm-overlay { display: none; }
    .adm-logo { display: flex; align-items: center; gap: 10px; padding: 20px 18px 16px; }
    .adm-logo .ball { font-size: 2rem; filter: drop-shadow(0 2px 4px rgba(0,0,0,.5)); }
    .adm-logo strong { display: block; font-size: 1.2rem; font-weight: 900; letter-spacing: .5px; line-height: 1; }
    .adm-logo small { display: block; font-size: .55rem; letter-spacing: 2px; color: #8ea3b8; margin-top: 3px; }
    .adm-nav { display: flex; flex-direction: column; gap: 4px; padding: 4px 12px; }
    .adm-nav a { display: flex; align-items: center; gap: 12px; color: #a9bccd; font-size: .86rem; font-weight: 500; padding: 10px 14px; border-radius: 10px; text-decoration: none; }
    .adm-nav a i { font-size: 1.15rem; }
    .adm-nav a:hover { background: rgba(255,255,255,.06); color: #fff; }
    .adm-nav a.on { background: #1a7a3a; color: #fff; font-weight: 700; }
    .adm-tabs { display: flex; flex-wrap: wrap; gap: 6px; margin: 0 0 16px; }
    .adm-tabs a { display: inline-flex; align-items: center; padding: 8px 16px; border-radius: 999px; background: #fff; border: 1px solid #dbe3ec; color: #334155; font-size: .88rem; font-weight: 700; text-decoration: none; white-space: nowrap; }
    .adm-tabs a:hover { border-color: #1a7a3a; color: #1a7a3a; }
    .adm-tab-badge { display: inline-block; min-width: 20px; padding: 1px 6px; margin-left: 6px; border-radius: 10px; background: #dc2626; color: #fff; font-size: .7rem; font-weight: 800; text-align: center; }
    .adm-tabs a.on { background: #1a7a3a; border-color: #1a7a3a; color: #fff; }
    .adm-grp { font-size: .66rem; font-weight: 800; letter-spacing: 1.2px; text-transform: uppercase; color: #6f87a0; padding: 14px 14px 4px; }
    .adm-grp:first-child { padding-top: 4px; }
    @media (max-width: 991px) { .adm-nav a { padding: 12px 14px; font-size: .95rem; } }
    .adm-side-foot { margin-top: auto; padding: 14px; }
    .adm-slogan { position: relative; border-radius: 12px; overflow: hidden; padding: 40px 14px 16px; text-align: left; background: linear-gradient(to top, rgba(4,10,18,.92) 20%, rgba(4,10,18,.45) 60%, rgba(4,10,18,.25) 100%), url('https://images.unsplash.com/photo-1522778119026-d647f0596c20?w=400&q=60&fit=crop') center/cover; }
    .adm-slogan .b { font-size: 2.2rem; }
    .adm-slogan strong { display: block; font-style: italic; font-weight: 900; font-size: 1.05rem; line-height: 1.15; margin-top: 4px; }
    .adm-slogan .u { display: block; width: 64px; height: 4px; border-radius: 4px; background: #22c55e; margin-top: 8px; transform: skewX(-18deg); }
    .adm-ver { font-size: .7rem; color: #8ea3b8; margin-top: 12px; line-height: 1.55; }
    .adm-ver strong { color: #fff; font-size: .75rem; }
    .adm-toast { position: fixed; top: 18px; right: 18px; z-index: 1080; min-width: 320px; }
    .adm-top { background: #fff; display: flex; align-items: center; justify-content: flex-end; gap: 14px; padding: 10px 18px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.07); margin-bottom: 16px; }
    .adm-bell { position: relative; font-size: 1.3rem; color: #101c33; border: none; background: none; cursor: pointer; padding: 4px 8px; }
    .adm-bell-dot { position: absolute; top: 0; right: 2px; min-width: 18px; height: 18px; border-radius: 10px; background: #dc2626; color: #fff; font-size: .62rem; font-weight: 800; display: flex; align-items: center; justify-content: center; padding: 0 5px; border: 2px solid #fff; }
    .adm-notif-dd { width: 360px; max-height: 420px; overflow-y: auto; border-radius: 12px; padding: 0; }
    .adm-notif-item { display: flex; gap: 10px; padding: 12px 14px; border-bottom: 1px solid #eef2f7; text-decoration: none; color: #101c33; }
    .adm-notif-item:hover { background: #f8fafc; }
    .adm-notif-item.unread { background: #eef7f0; }
    .adm-notif-ico { width: 32px; height: 32px; border-radius: 8px; background: #1a7a3a; color: #fff; display: flex; align-items: center; justify-content: center; font-size: .9rem; flex-shrink: 0; }
    .adm-notif-ico.evt { background: #2563eb; }
    .adm-toast { position: fixed; top: 18px; right: 18px; z-index: 1080; min-width: 300px; }
    .adm-user { display: flex; align-items: center; gap: 10px; }
    .adm-avatar { width: 42px; height: 42px; border-radius: 50%; background: #1a7a3a; color: #fff; font-weight: 800; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
    .adm-user strong { display: block; font-size: .86rem; color: #101c33; }
    .adm-user small { display: block; font-size: .7rem; color: #7c8aa0; }
    .adm-main { flex: 1; min-width: 0; min-height: 100vh; background: #f4f7fb; padding: 22px 26px 30px; color: #101c33; }
    .adm-h1 { font-size: 1.8rem; font-weight: 800; margin: 0; }
    .adm-sub { color: #7c8aa0; margin: 2px 0 0; font-size: .9rem; }
    .adm-date { display: inline-flex; align-items: center; gap: 10px; background: #fff; border: 1px solid #e8eef4; border-radius: 12px; padding: 6px 6px 6px 10px; font-size: .82rem; font-weight: 600; box-shadow: 0 1px 4px rgba(16,28,51,.06); transition: all .15s; position: relative; }
    .adm-date:hover { border-color: #cbd5e1; box-shadow: 0 4px 12px rgba(16,28,51,.08); }
    .adm-date.open { border-color: #1a7a3a; box-shadow: 0 4px 16px rgba(26,122,58,.12); }
    .adm-date .cal-ico { width: 34px; height: 34px; border-radius: 8px; background: #eef7f0; color: #1a7a3a; display: flex; align-items: center; justify-content: center; font-size: 1.05rem; flex-shrink: 0; }
    .adm-date-btn { display: inline-flex; align-items: center; gap: 8px; border: none; background: transparent; font-weight: 700; font-size: .85rem; color: #101c33; cursor: pointer; padding: 6px 4px; }
    .adm-date-btn .chev { color: #94a3b8; font-size: .7rem; transition: transform .15s; }
    .adm-date.open .chev { transform: rotate(180deg); }
    .adm-date-dropdown { position: absolute; top: calc(100% + 8px); right: 0; min-width: 240px; background: #fff; border: 1px solid #e8eef4; border-radius: 12px; box-shadow: 0 12px 28px rgba(16,28,51,.14); padding: 6px; z-index: 1050; }
    .adm-opt { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 12px; border-radius: 8px; cursor: pointer; font-size: .82rem; transition: all .12s; }
    .adm-opt:hover { background: #f1f5f9; }
    .adm-opt.active { background: #1a7a3a; color: #fff; }
    .adm-opt.active .mut { color: rgba(255,255,255,.75) !important; }
    .adm-opt.today:not(.active) { background: #eef7f0; border: 1px solid #bbf7d0; }
    .adm-opt .mut { color: #7c8aa0; font-size: .72rem; }
    .adm-opt .check { color: #1a7a3a; font-size: .9rem; }
    .adm-opt.active .check { color: #fff; }
    .kpi { background: #fff; border-radius: 14px; padding: 18px; display: flex; align-items: center; gap: 13px; box-shadow: 0 1px 3px rgba(16,28,51,.07); height: 100%; }
    .kpi-ico { width: 52px; height: 52px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #fff; flex-shrink: 0; }
    .kpi-ico.g { background: #1a7a3a; } .kpi-ico.y { background: #f5b301; } .kpi-ico.b { background: #2563eb; }
    .kpi > div { min-width: 0; }
    .kpi small.lbl { color: #101c33; font-size: .78rem; display: block; }
    .kpi .val { font-size: 1.65rem; font-weight: 800; line-height: 1.2; margin-top: 2px; }
    .kpi .foot { font-size: .72rem; color: #7c8aa0; margin-left: auto; padding-left: 8px; text-align: right; align-self: flex-end; flex-shrink: 0; }
    .kpi .foot.up { color: #1a7a3a; font-weight: 700; }
    .kpi .foot a { color: #101c33; font-weight: 600; text-decoration: none; }
    .panel { background: #fff; border-radius: 14px; padding: 20px; box-shadow: 0 1px 3px rgba(16,28,51,.07); height: 100%; display: flex; flex-direction: column; }
    .panel-2 { background: #fff; border-radius: 14px; padding: 20px; box-shadow: 0 1px 3px rgba(16,28,51,.07); }
    .panel h3 { font-size: 1.02rem; font-weight: 800; margin: 0 0 2px; }
    .panel .psub { font-size: .85rem; color: #6b7a90; }
    .link-more { font-size: .78rem; font-weight: 700; color: #2563eb; text-decoration: none; white-space: nowrap; }
    .legend { display: flex; gap: 8px 14px; font-size: .82rem; color: #6b7a90; margin: 12px 0 14px; flex-wrap: wrap; }
    .legend i.dot { display: inline-block; width: 11px; height: 11px; border-radius: 50%; margin-right: 5px; }
    .occ-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .72rem; }
    .occ-table th, .occ-table td { padding: 3px 4px; text-align: center; }
    .occ-table thead th { font-size: .7rem; }
    .occ-table thead small { display: block; font-weight: 400; color: #7c8aa0; }
    .occ-table tbody th { text-align: left; color: #7c8aa0; font-weight: 600; white-space: nowrap; padding-right: 10px; }
    .cell { display: block; height: 15px; border-radius: 4px; background: #e2e8f0; }
    .cell.ocup { background: #22c55e; }
    .cell.mant { background: #ef4444; }
    .btn-cal { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; background: #1a7a3a; color: #fff; font-weight: 700; border-radius: 10px; padding: 12px; margin-top: 14px; text-decoration: none; font-size: .85rem; }
    .btn-cal:hover { background: #135c2b; color: #fff; }
    .tbl { width: 100%; font-size: .86rem; margin-bottom: 0; }
    .tbl thead th { background: #f4f7fb; color: #64748b; font-size: .74rem; border: none; padding: 10px 10px; white-space: nowrap; }
    .tbl td { border-color: #eef2f7; vertical-align: middle; padding: 10px; }
    .tbl .fh small { display: block; color: #7c8aa0; }
    .pill { display: inline-block; font-size: .76rem; font-weight: 700; border-radius: 8px; padding: 4px 10px; white-space: nowrap; }
    .pill.ok { background: #d9efe0; color: #166534; }
    .pill.warn { background: #fbeecb; color: #92400e; }
    .pill.bad { background: #fbdcdc; color: #991b1b; }
    .pill.fin { background: #e2e8f0; color: #475569; }
    .pill.info { background: #e0f2fe; color: #0369a1; }
    .pill.mut { background: #f1f5f9; color: #64748b; }
    /* Amarillo de Bootstrap casi no se lee sobre blanco: se usa ámbar oscuro en el panel */
    .adm-main .text-warning { color: #b45309 !important; }
    .dots-btn { border: none; background: none; color: #7c8aa0; font-size: 1rem; }
    .donut { width: 168px; height: 168px; border-radius: 50%; margin: 14px auto 8px; display: flex; align-items: center; justify-content: center; }
    .donut-in { width: 112px; height: 112px; border-radius: 50%; background: #fff; display: flex; flex-direction: column; align-items: center; justify-content: center; }
    .donut-in strong { font-size: 1rem; }
    .donut-in small { font-size: .65rem; color: #7c8aa0; }
    .dleg { font-size: .75rem; margin-top: 8px; }
    .dleg i.dot { display: inline-block; width: 11px; height: 11px; border-radius: 50%; margin-right: 6px; }
    .dleg small { display: block; color: #7c8aa0; margin-left: 17px; }
    .crec-box { background: #eef7f0; border-radius: 10px; padding: 10px 12px; display: flex; align-items: center; gap: 8px; margin-top: 12px; font-size: .78rem; }
    .crec-box strong { color: #1a7a3a; }
    .crec-box small { color: #7c8aa0; display: block; }
    /* Tablas como tarjetas en celular (Reservas y Pagos) */
    .btn-mini .btn-txt { display: none; }
    @media (max-width: 767px) {
        /* La tabla se muestra como tarjetas: una por reserva, mismos formularios y botones */
        .tbl-cards, .tbl-cards tbody { display: block; width: 100%; }
        .tbl-cards thead { display: none; }
        .tbl-cards tr { display: block; background: #fff; border: 1px solid #dbe3ec; border-left: 4px solid #1a7a3a; border-radius: 12px; margin-bottom: 14px; padding: 8px 0; box-shadow: 0 1px 3px rgba(16,28,51,.07); }
        .tbl-cards td { display: block; border: 0; padding: 7px 14px; font-size: .9rem; }
        .tbl-cards td[data-label]::before { content: attr(data-label); display: block; font-size: .7rem; font-weight: 700; color: #7c8aa0; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 2px; }
        .tbl-cards .pill { font-size: .78rem; padding: 4px 12px; }
        .tbl-cards .est-select { font-size: .9rem; max-width: 100%; width: 100%; padding: 8px 10px; margin-top: 8px; }
        .tbl-cards td.td-acc { white-space: normal !important; display: flex; flex-wrap: wrap; gap: 8px; border-top: 1px solid #eef2f7; margin-top: 6px; padding-top: 12px; }
        .tbl-cards td.td-acc::before { display: none; }
        .tbl-cards td.td-acc form { display: inline-flex !important; margin: 0; }
        .tbl-cards .btn-mini { width: auto; min-height: 42px; padding: 0 14px; margin: 0; gap: 6px; font-size: .88rem; font-weight: 600; }
        .tbl-cards .btn-mini .btn-txt { display: inline; }
        .tbl-cards .fh small { display: inline; margin-left: 8px; }
    }
    @media (max-width: 991px) {
        .adm-wrap { padding-left: 0; }
        .adm-side { position: fixed; left: 0; top: 0; bottom: 0; z-index: 1050; transform: translateX(-105%); transition: transform .2s ease; box-shadow: 8px 0 24px rgba(0,0,0,.35); }
        .adm-side.open { transform: none; }
        .adm-burger { display: block; }
        .adm-overlay.show { display: block; position: fixed; inset: 0; background: rgba(4,10,18,.5); z-index: 1040; }
        .adm-main { padding: 16px 14px 24px; }
        .adm-h1 { font-size: 1.4rem; }
        .adm-date { width: 100%; justify-content: center; }
        .kpi { padding: 14px; gap: 10px; }
    }
</style>

<div class="adm-page">
<div class="adm-wrap">
<div class="adm-overlay" id="admOverlay" onclick="toggleAdmSide(false)"></div>
    <!-- Sidebar -->
    <aside class="adm-side" id="admSide">
        <div class="adm-logo">
            <span class="ball">⚽</span>
            <span><strong>TOP GOL</strong><small>MÁS QUE FÚTBOL</small></span>
        </div>
        <nav class="adm-nav">
            <?php foreach ($nav as $itemNav): ?>
                <a href="<?= $itemNav['href'] ?>" class="<?= $grupoActivoAdm === $itemNav['key'] ? 'on' : '' ?>"><i class="bi <?= $itemNav['ico'] ?>"></i> <?= $itemNav['label'] ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="adm-side-foot">
            <div class="adm-slogan">
                <span class="b">⚽</span>
                <strong>EL FÚTBOL<br>NOS UNE</strong>
                <span class="u"></span>
            </div>
            <div class="adm-ver"><strong>Top Gol</strong><br>Panel Administrador<br>v1.0.0</div>
        </div>
    </aside>

    <!-- Main -->
    <div class="adm-main">
        <header class="adm-top">
            <button class="adm-burger" onclick="toggleAdmSide()" aria-label="Menú"><i class="bi bi-list"></i></button>
            <div class="dropdown">
                <button class="adm-bell" id="admBellBtn" data-bs-toggle="dropdown" aria-expanded="false" title="Notificaciones">
                    <i class="bi bi-bell"></i>
                    <span class="adm-bell-dot d-none" id="admBellCount">0</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 adm-notif-dd" id="admNotifDropdown">
                    <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
                        <strong style="font-size:.85rem;">Notificaciones</strong>
                        <button class="btn btn-sm btn-link p-0" style="font-size:.72rem; text-decoration:none;" onclick="marcarTodasLeidas()">Marcar todas leídas</button>
                    </div>
                    <div id="admNotifList"><div class="p-4 text-center text-muted" style="font-size:.82rem;">Cargando...</div></div>
                    <a href="<?= url('/admin/reservas') ?>" class="d-block text-center py-2 border-top" style="font-size:.78rem; font-weight:700; color:#1a7a3a; text-decoration:none;">Ver todas las reservas</a>
                </div>
            </div>
            <div class="dropdown">
                <a href="#" class="adm-user text-decoration-none" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="adm-avatar"><?= htmlspecialchars($inicial) ?></span>
                    <span><strong><?= htmlspecialchars($nombreAdmin) ?></strong><small>Top Gol Tacna</small></span>
                    <i class="bi bi-chevron-down" style="font-size:.75rem;color:#7c8aa0;"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                    <li><a class="dropdown-item" href="<?= url('/perfil') ?>"><i class="bi bi-person-gear me-2"></i>Mi perfil</a></li>
                    <li><a class="dropdown-item" href="<?= url('/') ?>"><i class="bi bi-house me-2"></i>Ver sitio</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?= url('/logout') ?>"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a></li>
                </ul>
            </div>
        </header>
        <?php if (count($tabsGrupoAdm) > 1): ?>
            <nav class="adm-tabs" aria-label="Pantallas del grupo">
                <?php foreach ($tabsGrupoAdm as $tabNav): ?>
                    <a href="<?= $tabNav['href'] ?>" class="<?= $tabActivaAdm === $tabNav['key'] ? 'on' : '' ?>"<?= $tabActivaAdm === $tabNav['key'] ? ' aria-current="page"' : '' ?>><?= htmlspecialchars($tabNav['label']) ?><?php if (!empty($tabNav['badge'])): ?> <span class="adm-tab-badge"><?= (int)$tabNav['badge'] ?></span><?php endif; ?></a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
        <div id="admToastWrap" class="adm-toast"></div>
        <?= renderFlashMessages() /* avisos de éxito/error de las acciones del panel */ ?>
        <script>
        (function(){
            var countEl=document.getElementById('admBellCount');
            var listEl=document.getElementById('admNotifList');
            var csrf='<?= htmlspecialchars(csrf_token()) ?>';
            var apiList='<?= url('/api/notificaciones') ?>';
            var apiStream='<?= url('/api/notificaciones/stream') ?>';
            var apiLeerTodas='<?= url('/api/notificaciones/leer-todas') ?>';

            function esc(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }

            function renderCount(c){
                if(!countEl) return;
                if(c>0){ countEl.textContent=c>99?'99+':String(c); countEl.classList.remove('d-none'); }
                else { countEl.classList.add('d-none'); }
            }

            function renderList(items){
                if(!listEl) return;
                if(!items || items.length===0){
                    listEl.innerHTML='<div class="p-4 text-center text-muted" style="font-size:.82rem;">Sin notificaciones</div>';
                    return;
                }
                var html='';
                items.forEach(function(n){
                    var ico = n.tipo==='evento' ? 'evt' : '';
                    var unread = n.leida==0 ? ' unread' : '';
                    var href = n.link ? n.link : '#';
                    // Corrige links antiguos relativos sin base (/admin/* -> /topgol/admin/*)
                    if(href.charAt(0)==='/' && href.indexOf('http')!==0){
                        var basePath = new URL('<?= URL_BASE ?>').pathname.replace(/\/$/, '');
                        if(basePath && href.indexOf(basePath)!==0) href = basePath + href;
                    }
                    html += '<a href="'+esc(href)+'" class="adm-notif-item'+unread+'" onclick="marcarLeida(event,'+n.id+')">'
                        + '<span class="adm-notif-ico '+ico+'"><i class="bi '+(n.tipo==='evento'?'bi-calendar-event':'bi-bell-fill')+'"></i></span>'
                        + '<span style="flex:1; min-width:0;"><strong style="font-size:.82rem; display:block;">'+esc(n.titulo)+'</strong><span style="font-size:.75rem; color:#5b6b82;">'+esc(n.mensaje)+'</span><small style="display:block; color:#94a3b8; font-size:.68rem;">'+esc(n.created_at)+'</small></span>'
                        + '</a>';
                });
                listEl.innerHTML=html;
            }

            window.marcarLeida=function(e,id){
                // deja navegar, marca en segundo plano
                fetch('<?= url('/api/notificaciones/') ?>'+id+'/leer', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'csrf_token='+encodeURIComponent(csrf), credentials:'same-origin'});
            };
            window.marcarTodasLeidas=function(){
                fetch(apiLeerTodas, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'csrf_token='+encodeURIComponent(csrf), credentials:'same-origin'})
                    .then(function(){ fetchList(); });
            };

            function showToast(n){
                var wrap=document.getElementById('admToastWrap');
                if(!wrap) return;
                var el=document.createElement('div');
                el.className='toast show align-items-center text-bg-success border-0 mb-2';
                el.setAttribute('role','alert');
                el.innerHTML='<div class="d-flex"><div class="toast-body" style="font-size:.82rem;"><strong>'+esc(n.titulo)+'</strong><br>'+esc(n.mensaje)+'</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
                wrap.appendChild(el);
                setTimeout(function(){ el.remove(); }, 5000);
                try{ var a=new Audio('data:audio/wav;base64,UklGRigAAABXQVZFZm10IBAAAAABAAEARKwAAIhYAQACABAAZGF0YQQAAAAAAA=='); a.play().catch(function(){}); }catch(e){}
            }

            // Avisos: se consulta /api/notificaciones cada 20 s (solo con la pestaña visible).
            // Ya no se deja una conexión abierta por página: con el servidor de desarrollo (php -S)
            // una sola conexión larga detenía todas las demás páginas del panel.
            var lastId = null;
            var POLL_MS = 20000;
            function fetchList(){
                fetch(apiList, {credentials:'same-origin'})
                    .then(function(r){ return r.json(); })
                    .then(function(d){
                        renderCount(d.count); renderList(d.items);
                        var items = d.items || [];
                        var maxId = items.reduce(function(m,n){ return Math.max(m, parseInt(n.id)||0); }, 0);
                        if(lastId !== null){
                            // avisos nuevos desde la última consulta (de más antiguo a más reciente)
                            items.filter(function(n){ return (parseInt(n.id)||0) > lastId; })
                                 .sort(function(x,y){ return x.id - y.id; })
                                 .forEach(showToast);
                        }
                        lastId = Math.max(lastId || 0, maxId);
                    })
                    .catch(function(){});
            }
            fetchList();
            setInterval(function(){ if(!document.hidden) fetchList(); }, POLL_MS);
            document.addEventListener('visibilitychange', function(){ if(!document.hidden) fetchList(); });
            // Intento opcional WebSocket nativo en paralelo (si corre websocket_server.php): igual que antes
            if('WebSocket' in window){
                try{
                    var ws=new WebSocket((location.protocol==='https:'?'wss://':'ws://')+location.hostname+':8080');
                    ws.onmessage=function(ev){ try{ var d=JSON.parse(ev.data); if(d.type==='notificacion'){ lastId = Math.max(lastId || 0, parseInt(d.id)||0); showToast(d); fetchList(); }}catch(err){} };
                }catch(e){}
            }
        })();
        </script>
