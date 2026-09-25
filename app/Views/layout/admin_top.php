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

$nav = [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'ico' => 'bi-house', 'href' => url('/admin/dashboard')],
    ['key' => 'reservas', 'label' => 'Reservas', 'ico' => 'bi-calendar', 'href' => url('/admin/reservas')],
    ['key' => 'pagos', 'label' => 'Pagos', 'ico' => 'bi-credit-card', 'href' => url('/admin/reservas?pago=en_revision')],
    ['key' => 'canchas', 'label' => 'Canchas', 'ico' => 'bi-grid', 'href' => url('/canchas')],
    ['key' => 'ventas', 'label' => 'Ventas', 'ico' => 'bi-bar-chart', 'href' => '#'],
    ['key' => 'productos', 'label' => 'Productos', 'ico' => 'bi-box', 'href' => url('/admin/productos')],
    ['key' => 'inventario', 'label' => 'Inventario', 'ico' => 'bi-boxes', 'href' => url('/admin/productos?filtro=stock_bajo')],
];
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
    body.adm-standalone { background: #e8edf3; margin: 0; font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; }
    .adm-page { min-height: 100vh; }
    .adm-wrap { display: flex; gap: 0; align-items: stretch; min-height: 100vh; }
    .adm-side { width: 220px; flex-shrink: 0; background: linear-gradient(180deg, #0b1e33 0%, #081424 100%); color: #fff; display: flex; flex-direction: column; position: sticky; top: 0; height: 100vh; overflow-y: auto; }
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
    .adm-main { flex: 1; min-width: 0; background: #f4f7fb; padding: 22px 26px 30px; color: #101c33; }
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
    .panel .psub { font-size: .78rem; color: #7c8aa0; }
    .link-more { font-size: .78rem; font-weight: 700; color: #2563eb; text-decoration: none; white-space: nowrap; }
    .legend { display: flex; gap: 8px 14px; font-size: .72rem; color: #7c8aa0; margin: 12px 0 14px; flex-wrap: wrap; }
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
    .tbl { width: 100%; font-size: .78rem; margin-bottom: 0; }
    .tbl thead th { background: #f4f7fb; color: #7c8aa0; font-size: .68rem; border: none; padding: 9px 10px; white-space: nowrap; }
    .tbl td { border-color: #eef2f7; vertical-align: middle; padding: 10px; }
    .tbl .fh small { display: block; color: #7c8aa0; }
    .pill { display: inline-block; font-size: .66rem; font-weight: 700; border-radius: 8px; padding: 3px 10px; white-space: nowrap; }
    .pill.ok { background: #d9efe0; color: #166534; }
    .pill.warn { background: #fbeecb; color: #92400e; }
    .pill.bad { background: #fbdcdc; color: #991b1b; }
    .pill.fin { background: #e2e8f0; color: #475569; }
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
    @media (max-width: 991px) {
        .adm-side { position: fixed; left: 0; top: 0; z-index: 1050; height: 100vh; transform: translateX(-105%); transition: transform .2s ease; box-shadow: 8px 0 24px rgba(0,0,0,.35); }
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
            <?php foreach ($nav as $item): ?>
                <a href="<?= $item['href'] ?>" class="<?= $adminMenuActivo === $item['key'] ? 'on' : '' ?>"><i class="bi <?= $item['ico'] ?>"></i> <?= $item['label'] ?></a>
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
        <div id="admToastWrap" class="adm-toast"></div>
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

            function fetchList(){
                fetch(apiList, {credentials:'same-origin'})
                    .then(function(r){ return r.json(); })
                    .then(function(d){ renderCount(d.count); renderList(d.items); })
                    .catch(function(){});
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

            // Tiempo real: SSE (push sin polling) - efecto WebSocket sin necesidad de daemon.
            // Para WebSocket puro ws://localhost:8080 habilita extension=sockets y ejecuta: php websocket_server.php
            var es = null;
            var lastId = 0;
            function connectSSE(){
                try{
                    var url = apiStream + (lastId ? '?last='+lastId : '');
                    es = new EventSource(url);
                    es.addEventListener('count', function(e){
                        try{ var d=JSON.parse(e.data); renderCount(d.count); }catch(err){}
                    });
                    es.addEventListener('notificacion', function(e){
                        try{
                            var n=JSON.parse(e.data);
                            lastId = Math.max(lastId, parseInt(n.id)||0);
                            showToast(n);
                            fetchList();
                        }catch(err){}
                    });
                    es.onerror = function(){
                        try{ es.close(); }catch(err){}
                        setTimeout(connectSSE, 3000);
                    };
                }catch(e){
                    setInterval(fetchList, 10000);
                }
            }
            fetchList();
            connectSSE();
            // Intento opcional WebSocket nativo en paralelo (si corre websocket_server.php)
            if('WebSocket' in window){
                try{
                    var ws=new WebSocket((location.protocol==='https:'?'wss://':'ws://')+location.hostname+':8080');
                    ws.onopen=function(){ if(es){ try{es.close();}catch(e){} } };
                    ws.onmessage=function(ev){ try{ var d=JSON.parse(ev.data); if(d.type==='notificacion'){ showToast(d); fetchList(); }}catch(err){} };
                }catch(e){}
            }
        })();
        </script>
