<?php
declare(strict_types=1);

/**
 * Página informativa /eventos: dos opciones. Todos los textos salen de config/eventos_textos.php
 * y las imágenes de public/assets/img/eventos/ (si falta un archivo se muestra un cuadro neutro).
 */
$tx = eventosTextos();
$fg = $tx['futbol_grupos'] ?? [];
$cu = $tx['cumpleanos'] ?? [];
$promo = $cu['promociones'] ?? [];
$precioEspacioTxt = number_format(espacioPrecioHora(), 0);

// Solo se muestran fotos reales que existan en public/assets/img/eventos/ (nunca fotos de internet)
$fotosTxt = (string)($cu['fotos_pendientes'] ?? 'Fotos próximamente');
$galeria = [];
foreach (($cu['imagenes'] ?? []) as $i => $archivo) {
    $src = eventoImagenUrl((string)$archivo);
    if ($src !== null) {
        $galeria[] = ['src' => $src, 'alt' => (string)(($cu['imagenes_alt'][$i] ?? '') ?: 'Espacio de cumpleaños y reuniones de TOP GOL')];
    }
}
$faltanFotosCumple = count($galeria) < count($cu['imagenes'] ?? []);
$fotoFutbol = eventoImagenUrl((string)($fg['imagen'] ?? ''));
?>

<style>
    .ev-hero { background: linear-gradient(100deg, #07140d 0%, #0b2a18 60%, #0f3a20 100%); color: #fff; padding: 56px 0 60px; }
    .ev-hero h1 { font-size: 2.6rem; font-weight: 900; line-height: 1.1; margin: 10px 0; }
    .ev-hero p { color: rgba(255,255,255,.85); font-size: 1rem; max-width: 560px; }
    .ev-btn { display: inline-flex; align-items: center; gap: 8px; border-radius: 12px; padding: 13px 22px; font-weight: 800; text-decoration: none; font-size: .95rem; }
    .ev-btn.principal { background: #22c55e; color: #052e16; }
    .ev-btn.principal:hover { background: #4ade80; color: #052e16; }
    .ev-btn.claro { background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.35); }
    .ev-btn.claro:hover { background: rgba(255,255,255,.22); color: #fff; }
    .ev-sec { background: #fff; border-radius: 18px; padding: 28px; box-shadow: 0 2px 8px rgba(16,28,51,.07); margin-bottom: 28px; scroll-margin-top: 90px; }
    .ev-sec h2 { font-size: 1.7rem; font-weight: 900; color: #101c33; margin: 0; }
    .ev-sub { color: #1a7a3a; font-weight: 700; font-size: .85rem; letter-spacing: .5px; text-transform: uppercase; }
    .ev-list { list-style: none; padding: 0; margin: 14px 0; }
    .ev-list li { display: flex; gap: 10px; padding: 6px 0; font-size: 1rem; color: #33415c; }
    .ev-list li i { color: #1a7a3a; margin-top: 3px; }
    .ev-regla { background: #fef3c7; border: 1px solid #fde68a; border-left: 5px solid #d97706; border-radius: 12px; padding: 14px 16px; color: #78350f; font-size: 1rem; font-weight: 600; margin: 16px 0; }
    .ev-nota { background: #eef7f0; border: 1px solid #bbf7d0; border-radius: 12px; padding: 12px 16px; color: #166534; font-size: .95rem; margin: 12px 0; }
    .ev-promo { background: #f5f3ff; border: 1px solid #ddd6fe; border-radius: 14px; padding: 18px; margin-top: 20px; }
    .ev-promo h3 { font-size: 1.15rem; font-weight: 800; color: #4c1d95; margin: 0 0 8px; }
    .ev-galeria { display: grid; gap: 12px; margin-top: 16px; grid-template-columns: 1fr; }
    .ev-galeria a { display: block; overflow: hidden; border-radius: 14px; background: #e8edf3; }
    .ev-galeria img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .25s; }
    .ev-galeria a:hover img { transform: scale(1.03); }
    .ev-galeria.g1 a { height: 420px; }
    .ev-galeria.g2 { grid-template-columns: 1fr 1fr; }
    .ev-galeria.g2 a { height: 340px; }
    .ev-galeria.g3 { grid-template-columns: 2fr 1fr; grid-template-rows: 214px 214px; }
    .ev-galeria.g3 a:first-child { grid-row: span 2; }
    .ev-prox { display: inline-flex; align-items: center; gap: 6px; color: #94a3b8; font-size: .85rem; margin-top: 10px; }
    @media (max-width: 767px) {
        .ev-galeria.g2, .ev-galeria.g3 { grid-template-columns: 1fr; grid-template-rows: none; }
        .ev-galeria.g1 a, .ev-galeria.g2 a, .ev-galeria.g3 a, .ev-galeria.g3 a:first-child { height: 240px; grid-row: auto; }
    }
    @media (max-width: 767px) {
        .ev-hero h1 { font-size: 2rem; }
        .ev-sec { padding: 20px 16px; }
        .ev-sec h2 { font-size: 1.4rem; }
        .ev-btn { width: 100%; justify-content: center; }
    }
</style>

<!-- Encabezado: las dos opciones -->
<section class="ev-hero">
    <div class="container">
        <span class="badge" style="background:#facc15; color:#0f172a; font-weight:800; letter-spacing:.5px; padding:6px 12px; border-radius:20px; font-size:.72rem;">EVENTOS</span>
        <h1>Eventos en <span style="color:#4ade80; font-style:italic;">TOP GOL</span></h1>
        <p>Elige lo que necesitas: canchas para tu grupo de fútbol, o el espacio del medio del local para celebrar.</p>
        <div class="d-flex flex-wrap gap-2 mt-3">
            <a href="#futbol-grupos" class="ev-btn principal"><i class="bi bi-dribbble"></i> <?= htmlspecialchars((string)($fg['titulo'] ?? 'Fútbol para grupos')) ?></a>
            <a href="#cumpleanos" class="ev-btn claro"><i class="bi bi-balloon"></i> <?= htmlspecialchars((string)($cu['titulo'] ?? 'Cumpleaños y reuniones')) ?></a>
        </div>
    </div>
</section>

<div class="container" style="margin-top:32px; margin-bottom:56px;">

    <!-- 1. Fútbol para grupos -->
    <section class="ev-sec" id="futbol-grupos">
        <div class="row g-4 align-items-center">
            <div class="<?= $fotoFutbol !== null ? 'col-lg-7' : 'col-12' ?>">
                <div class="ev-sub"><?= htmlspecialchars((string)($fg['subtitulo'] ?? '')) ?></div>
                <h2><?= htmlspecialchars((string)($fg['titulo'] ?? '')) ?></h2>
                <p class="mt-2 mb-0" style="font-size:1.05rem; color:#33415c;"><?= htmlspecialchars((string)($fg['descripcion'] ?? '')) ?></p>
                <ul class="ev-list">
                    <?php foreach (($fg['puntos'] ?? []) as $punto): ?>
                        <li><i class="bi bi-check-circle-fill"></i><span><?= htmlspecialchars((string)$punto) ?></span></li>
                    <?php endforeach; ?>
                </ul>
                <div class="ev-nota"><i class="bi bi-cash-coin me-1"></i> <?= htmlspecialchars((string)($fg['adelanto'] ?? '')) ?></div>
                <div class="fw-bold mt-3" style="color:#101c33;"><?= htmlspecialchars((string)($fg['reglas_titulo'] ?? 'Reglas')) ?></div>
                <ul class="ev-list" style="margin-top:6px;">
                    <?php foreach (($fg['reglas'] ?? []) as $regla): ?>
                        <li><i class="bi bi-exclamation-circle-fill" style="color:#d97706;"></i><span><?= htmlspecialchars((string)$regla) ?></span></li>
                    <?php endforeach; ?>
                </ul>
                <p class="text-muted" style="font-size:.88rem;">Al reservar tendrás que marcar “<?= htmlspecialchars((string)($fg['acepto'] ?? 'Acepto las reglas')) ?>”.</p>
                <a href="<?= url('/evento') ?>" class="ev-btn principal mt-2"><i class="bi bi-calendar-plus"></i> <?= htmlspecialchars((string)($fg['boton'] ?? 'Reservar canchas')) ?></a>
                <?php if ($fotoFutbol === null): ?><div class="ev-prox"><i class="bi bi-camera"></i> <?= htmlspecialchars($fotosTxt) ?></div><?php endif; ?>
            </div>
            <?php if ($fotoFutbol !== null): ?>
            <div class="col-lg-5">
                <a href="<?= htmlspecialchars($fotoFutbol) ?>" target="_blank" rel="noopener" style="display:block; border-radius:14px; overflow:hidden;">
                    <img src="<?= htmlspecialchars($fotoFutbol) ?>" alt="Canchas de TOP GOL" loading="lazy" style="width:100%; height:340px; object-fit:cover; display:block;">
                </a>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- 2. Cumpleaños y reuniones -->
    <section class="ev-sec" id="cumpleanos">
        <div class="ev-sub"><?= htmlspecialchars((string)($cu['subtitulo'] ?? '')) ?></div>
        <h2><?= htmlspecialchars((string)($cu['titulo'] ?? '')) ?></h2>
        <p class="mt-2 mb-0" style="font-size:1.05rem; color:#33415c;"><?= htmlspecialchars((string)($cu['descripcion'] ?? '')) ?></p>

        <?php if (!empty($galeria)): ?>
            <div class="ev-galeria g<?= min(3, count($galeria)) ?>">
                <?php foreach (array_slice($galeria, 0, 3) as $foto): ?>
                    <a href="<?= htmlspecialchars($foto['src']) ?>" target="_blank" rel="noopener" title="Ver foto grande">
                        <img src="<?= htmlspecialchars($foto['src']) ?>" alt="<?= htmlspecialchars($foto['alt']) ?>" loading="lazy">
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($faltanFotosCumple): ?><div class="ev-prox"><i class="bi bi-camera"></i> <?= htmlspecialchars($fotosTxt) ?></div><?php endif; ?>

        <ul class="ev-list">
            <?php foreach (($cu['puntos'] ?? []) as $punto): ?>
                <li><i class="bi bi-check-circle-fill"></i><span><?= htmlspecialchars((string)$punto) ?></span></li>
            <?php endforeach; ?>
        </ul>

        <div class="ev-regla"><i class="bi bi-ban me-1"></i> <?= htmlspecialchars((string)($cu['regla_alcohol'] ?? '')) ?></div>

        <div class="ev-nota"><i class="bi bi-cash-coin me-1"></i> <?= htmlspecialchars(str_replace('{precio}', $precioEspacioTxt, (string)($cu['precio'] ?? ''))) ?></div>
        <p class="text-muted mb-3" style="font-size:.92rem;"><?= htmlspecialchars((string)($cu['adelanto'] ?? '')) ?></p>

        <div class="ev-promo">
            <h3><i class="bi bi-gift me-1"></i> <?= htmlspecialchars((string)($promo['titulo'] ?? 'Promociones')) ?>
                <?php if (!empty($promo['es_ejemplo'])): ?><span class="badge bg-secondary ms-1" style="font-size:.7rem;">Texto de ejemplo</span><?php endif; ?>
            </h3>
            <?php foreach (($promo['items'] ?? []) as $item): ?>
                <p class="mb-1" style="color:#4c1d95;"><?= htmlspecialchars((string)$item) ?></p>
            <?php endforeach; ?>
        </div>

        <a href="<?= url('/cumpleanos/solicitar') ?>" class="ev-btn principal mt-4"><i class="bi bi-send"></i> <?= htmlspecialchars((string)($cu['boton'] ?? 'Solicitar')) ?></a>
    </section>
</div>
