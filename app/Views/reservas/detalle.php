<?php
declare(strict_types=1);

/** @var array<string, mixed> $reserva */
/** @var array<string, mixed>|null $cancha */
/** @var array<int, array<string, mixed>> $otras */

$id = (int)$reserva['id'];
$estado = (string)$reserva['estado'];
$total = (float)$reserva['total_pago'];
$adelanto = (float)($reserva['adelanto_monto'] ?? 20);
$saldo = max(0, $total - $adelanto);
$pagoEstado = (string)($reserva['pago_estado'] ?? 'pendiente');
$metodoPago = (string)($reserva['metodo_pago'] ?? '');
$comprobanteRuta = (string)($reserva['comprobante_ruta'] ?? '');
$email = (string)($reserva['usuario_email'] ?? '');

$tipoLabels = ['futbol_5' => 'Fútbol 5', 'futbol_7' => 'Fútbol 7', 'futbol_11' => 'Fútbol 11'];
$tipo = $tipoLabels[$reserva['cancha_tipo']] ?? (string)$reserva['cancha_tipo'];
$capacidad = (int)($cancha['capacidad'] ?? 8);
$canchaNombre = (string)$reserva['cancha_nombre'];

$imgPorTipo = [
    'futbol_5'  => 'https://images.unsplash.com/photo-1553778263-73a83bab9b0c?w=800&h=500&fit=crop&q=80',
    'futbol_7'  => 'https://images.unsplash.com/photo-1522778119026-d647f0596c20?w=800&h=500&fit=crop&q=80',
    'futbol_11' => 'https://images.unsplash.com/photo-1487466365202-1afdb86c764e?w=800&h=500&fit=crop&q=80',
];
$imgUrl = $imgPorTipo[$reserva['cancha_tipo']] ?? $imgPorTipo['futbol_7'];

// Fecha larga: Sábado, 26 de abril de 2025
$diasLargo = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$mesesLargo = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$tsFecha = strtotime($reserva['fecha']);
$fechaLarga = $diasLargo[(int)date('w', $tsFecha)] . ', ' . date('d', $tsFecha) . ' de ' . $mesesLargo[(int)date('n', $tsFecha)] . ' de ' . date('Y', $tsFecha);

$horaIni = date('H:i', strtotime($reserva['hora_inicio']));
$horaFin = date('H:i', strtotime($reserva['hora_fin']));
$dur = (int)$reserva['duracion_horas'];

$fmtCorta = static function (string $fecha): string {
    $m = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $ts = strtotime($fecha);
    return date('d', $ts) . ' ' . $m[(int)date('n', $ts)] . '. ' . date('Y', $ts);
};
$fmtFechaHora = static function (?string $dt): string {
    if (!$dt) return '—';
    $m = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $ts = strtotime($dt);
    return date('d', $ts) . ' ' . $m[(int)date('n', $ts)] . '. ' . date('Y', $ts) . ' - ' . date('H:i', $ts);
};

// Banner según estado
$banner = match ($estado) {
    'confirmada' => ['cls' => 'ok', 'titulo' => '¡Reserva confirmada!', 'texto' => 'Tu cancha ha sido reservada exitosamente.'],
    'pendiente'  => ['cls' => 'pend', 'titulo' => '¡Reserva pendiente!', 'texto' => 'Estamos verificando tu pago. Te avisaremos cuando se confirme.'],
    'cancelada'  => ['cls' => 'canc', 'titulo' => 'Reserva cancelada', 'texto' => 'Esta reserva fue cancelada. Puedes crear una nueva cuando quieras.'],
    'finalizada' => ['cls' => 'fin', 'titulo' => 'Reserva finalizada', 'texto' => 'Gracias por jugar en Top Gol. ¡Te esperamos de nuevo!'],
    default      => ['cls' => 'ok', 'titulo' => '¡Reserva registrada!', 'texto' => 'Tu cancha ha sido reservada exitosamente.'],
};

// Pago según pago_estado
$pago = match ($pagoEstado) {
    'verificado'  => ['badge' => 'ok', 'txt' => 'Pagado', 'sub' => 'Completado'],
    'en_revision' => ['badge' => 'warn', 'txt' => 'Por verificar', 'sub' => 'Pendiente'],
    'rechazado'   => ['badge' => 'bad', 'txt' => 'Rechazado', 'sub' => 'Rechazado'],
    default       => ['badge' => 'warn', 'txt' => 'Pendiente', 'sub' => 'Pendiente'],
};
$adelantoSub = !empty($reserva['comprobante_subido_at'])
    ? 'Pagado el ' . $fmtFechaHora((string)$reserva['comprobante_subido_at'])
    : 'Se paga con Yape o BCP';

$waTexto = urlencode("Hola Top Gol, tengo una consulta sobre mi reserva #{$id}");
$waUrl = "https://wa.me/51987654321?text={$waTexto}";

$puedeCancelar = in_array($estado, ['pendiente', 'confirmada'], true) && ($reserva['fecha'] >= date('Y-m-d'));
?>

<div class="container py-4 det-page">
    <!-- Stepper del flujo de reserva -->
    <div class="stepper" aria-label="Progreso de reserva">
        <div class="step done">
            <span class="dot"><i class="bi bi-check-lg"></i></span>
            <span class="lbl"><span class="lbl-full">1. Selección de cancha</span><span class="lbl-short">Selección</span></span>
        </div>
        <span class="line done" aria-hidden="true"></span>
        <div class="step done">
            <span class="dot"><i class="bi bi-check-lg"></i></span>
            <span class="lbl"><span class="lbl-full">2. Pago y comprobante</span><span class="lbl-short">Pago</span></span>
        </div>
        <span class="line done" aria-hidden="true"></span>
        <div class="step active" aria-current="step">
            <span class="dot">3</span>
            <span class="lbl"><span class="lbl-full">3. Confirmación</span><span class="lbl-short">Confirmación</span></span>
        </div>
    </div>
    
    <!-- Encabezado -->
    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
        <div>
            <h1 class="det-h1">Mis reservas</h1>
            <p class="det-sub">Consulta el estado de tus reservas y administra tus próximas canchas.</p>
        </div>
        <div class="text-md-end">
            <a href="<?= url('/reserva/crear') ?>" class="btn-nueva"><i class="bi bi-plus-lg me-1"></i> Nueva reserva</a>
        </div>
    </div>

    <!-- Tarjeta principal -->
    <div class="det-main">
        <div class="row g-0">
            <!-- Banner estado -->
            <div class="col-lg-3">
                <div class="det-banner <?= $banner['cls'] ?>">
                    <span class="det-check"><i class="bi <?= $estado === 'cancelada' ? 'bi-x-lg' : 'bi-check-lg' ?>"></i></span>
                    <h2><?= $banner['titulo'] ?></h2>
                    <p><?= $banner['texto'] ?></p>
                    <div class="det-codigo-lbl">Código de reserva</div>
                    <div class="det-codigo">
                        #<?= $id ?>
                        <button type="button" class="det-copy" onclick="copiarCodigo('<?= $id ?>', this)" title="Copiar código">
                            <i class="bi bi-copy"></i>
                        </button>
                    </div>
                    <p class="det-mail">Hemos enviado los detalles a tu correo<br><strong><?= htmlspecialchars($email) ?></strong></p>
                </div>
            </div>
            <!-- Imagen cancha -->
            <div class="col-lg-4">
                <div class="det-img">
                    <img src="<?= $imgUrl ?>" alt="<?= htmlspecialchars($canchaNombre) ?>">
                    <span class="det-img-badge"><i class="bi bi-shield-fill-check me-1"></i> Cancha <?= $estado === 'confirmada' ? 'confirmada' : htmlspecialchars($estado) ?></span>
                    <div class="det-img-txt">
                        <strong><?= htmlspecialchars($canchaNombre) ?></strong>
                        <span><?= htmlspecialchars($tipo) ?> - Césped sintético</span>
                    </div>
                </div>
            </div>
            <!-- Info + pago -->
            <div class="col-lg-5">
                <div class="det-info">
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <div class="det-box">
                                <i class="bi bi-calendar3"></i>
                                <span><small>Fecha</small><strong><?= htmlspecialchars($fechaLarga) ?></strong></span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="det-box">
                                <i class="bi bi-clock"></i>
                                <span><small>Hora</small><strong><?= $horaIni ?> - <?= $horaFin ?></strong><small><?= $dur ?> hora<?= $dur > 1 ? 's' : '' ?></small></span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="det-box">
                                <i class="bi bi-people-fill"></i>
                                <span><small>N° de personas</small><strong>Hasta <?= $capacidad ?> personas</strong></span>
                            </div>
                        </div>
                    </div>

                    <div class="det-pago-titulo">Resumen de pago</div>
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <div class="det-pay">
                                <span class="dp-ico ok"><i class="bi bi-check-circle-fill"></i></span>
                                <small>Adelanto pagado</small>
                                <strong><?= formatPrice($adelanto) ?></strong>
                                <span class="dp-sub"><?= htmlspecialchars($adelantoSub) ?></span>
                                <span class="dp-badge ok">Completado</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="det-pay">
                                <span class="dp-ico warn"><i class="bi bi-clock-history"></i></span>
                                <small>Saldo pendiente</small>
                                <strong><?= formatPrice($saldo) ?></strong>
                                <span class="dp-sub">Se paga en cancha</span>
                                <span class="dp-badge warn"><?= $pago['sub'] ?></span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="det-pay">
                                <span class="dp-ico info"><i class="bi bi-wallet2"></i></span>
                                <small>Monto total</small>
                                <strong><?= formatPrice($total) ?></strong>
                                <span class="dp-sub"><?= $dur ?> hora<?= $dur > 1 ? 's' : '' ?> de alquiler</span>
                            </div>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-6">
                            <?php if ($comprobanteRuta): ?>
                                <a href="<?= url('/' . ltrim($comprobanteRuta, '/')) ?>" target="_blank" rel="noopener" class="btn-comp">
                                    <i class="bi bi-file-earmark-text me-2"></i> Ver comprobante
                                </a>
                            <?php else: ?>
                                <span class="btn-comp disabled"><i class="bi bi-file-earmark-text me-2"></i> Sin comprobante</span>
                            <?php endif; ?>
                        </div>
                        <div class="col-6">
                            <a href="<?= $waUrl ?>" target="_blank" rel="noopener" class="btn-wa">
                                <i class="bi bi-whatsapp me-2"></i>
                                <span>Contactar por WhatsApp<small>¿Tienes alguna consulta?</small></span>
                            </a>
                        </div>
                    </div>
                    <?php if ($metodoPago): ?>
                        <div class="det-metodo">Método de pago: <strong><?= $metodoPago === 'yape' ? 'Yape' : 'Transferencia BCP' ?></strong> · Estado del pago: <strong><?= $pago['txt'] ?></strong></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Historial -->
    <div class="det-card mt-3">
        <h3 class="det-h3">Historial de la reserva</h3>
        <div class="det-timeline">
            <div class="dt-item done">
                <span class="dt-dot"></span>
                <div><strong>Reserva <?= $estado === 'cancelada' ? 'cancelada' : 'confirmada' ?></strong> <span class="dt-fecha"><?= $fmtFechaHora((string)$reserva['created_at']) ?></span>
                <p>Tu reserva ha sido <?= $estado === 'cancelada' ? 'cancelada' : 'confirmada' ?>. Código: #<?= $id ?></p></div>
            </div>
            <div class="dt-item <?= $comprobanteRuta ? 'done' : '' ?>">
                <span class="dt-dot"></span>
                <div><strong>Pago del adelanto</strong> <span class="dt-fecha"><?= $comprobanteRuta ? $fmtFechaHora((string)$reserva['comprobante_subido_at']) : 'Pendiente' ?></span>
                <p><?= $comprobanteRuta ? 'Se ha registrado el pago de ' . formatPrice($adelanto) . ' (' . ($metodoPago === 'yape' ? 'Yape' : 'Transferencia BCP') . '). Estado: ' . $pago['txt'] . '.' : 'Aún no se ha registrado el pago de ' . formatPrice($adelanto) . '.' ?></p></div>
            </div>
            <div class="dt-item done">
                <span class="dt-dot"></span>
                <div><strong>Recordatorio</strong> <span class="dt-fecha"><?= $fmtCorta(date('Y-m-d', strtotime($reserva['fecha'] . ' -1 day'))) ?> - 10:00</span>
                <p>Te enviaremos un recordatorio el día de tu reserva</p></div>
            </div>
            <div class="dt-item">
                <span class="dt-dot"></span>
                <div><strong>Por jugar</strong> <span class="dt-fecha"><?= $fmtCorta((string)$reserva['fecha']) ?> - <?= $horaIni ?></span></div>
            </div>
        </div>
    </div>

    <!-- Otras reservas -->
    <div class="det-card mt-3">
        <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap mb-1">
            <div>
                <h3 class="det-h3 mb-0">Otras reservas</h3>
                <p class="det-sub small">Aquí puedes ver tu historial y próximas reservas.</p>
            </div>
            <a href="<?= url('/mis-reservas') ?>" class="det-link">Ver todas mis reservas <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
        <?php if (empty($otras)): ?>
            <p class="text-muted small mb-0">No tienes otras reservas registradas.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table det-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th><th>Fecha</th><th>Hora</th><th>Cancha</th><th>Tipo</th><th>Estado</th><th>Pago</th><th>Monto</th><th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($otras as $o): ?>
                        <?php
                            $estLbl = match ($o['estado']) {
                                'confirmada' => 'Confirmada', 'pendiente' => 'Pendiente',
                                'cancelada' => 'Cancelada', 'finalizada' => 'Completada', default => ucfirst($o['estado']),
                            };
                            $estCls = match ($o['estado']) {
                                'confirmada' => 'ok', 'finalizada' => 'fin',
                                'pendiente' => 'warn', 'cancelada' => 'bad', default => 'mut',
                            };
                            $pe = (string)($o['pago_estado'] ?? 'pendiente');
                            $pagoLbl = match ($pe) {
                                'verificado' => 'Pagado', 'en_revision' => 'Por verificar',
                                'rechazado' => 'Rechazado', default => 'Pendiente',
                            };
                            $pagoCls = match ($pe) {
                                'verificado' => 'ok', 'en_revision' => 'warn',
                                'rechazado' => 'bad', default => 'warn',
                            };
                            $oPuedeCancelar = in_array($o['estado'], ['pendiente', 'confirmada'], true) && ($o['fecha'] >= date('Y-m-d'));
                        ?>
                        <tr>
                            <td class="fw-semibold">#<?= $o['id'] ?></td>
                            <td><?= $fmtCorta((string)$o['fecha']) ?></td>
                            <td><?= date('H:i', strtotime($o['hora_inicio'])) ?> - <?= date('H:i', strtotime($o['hora_fin'])) ?></td>
                            <td><?= htmlspecialchars($o['cancha_nombre']) ?></td>
                            <td><?= htmlspecialchars($tipoLabels[$o['cancha_tipo']] ?? $o['cancha_tipo']) ?></td>
                            <td><span class="pill <?= $estCls ?>"><?= $estLbl ?></span></td>
                            <td><span class="pill <?= $pagoCls ?>"><?= $pagoLbl ?></span></td>
                            <td class="fw-semibold"><?= formatPrice($o['total_pago']) ?></td>
                            <td class="text-end">
                                <a href="<?= url('/mis-reservas/' . $o['id']) ?>" class="btn-ver">Ver detalle</a>
                                <?php if ($oPuedeCancelar): ?>
                                    <a href="<?= url('/reserva/cancelar/' . $o['id']) ?>" class="btn-ver danger btn-confirmar-eliminar" data-confirm="¿Deseas cancelar la reserva #<?= $o['id'] ?>?">Cancelar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.det-page { background: #f6f9fb; border-radius: 12px; }
.det-h1 { font-size: 1.9rem; font-weight: 800; color: #101c33; margin: 0; }
.det-sub { color: #7c8aa0; margin: 2px 0 0; }
.det-crumb { font-size: .75rem; color: #7c8aa0; margin-bottom: 6px; }
.det-crumb a { color: #101c33; font-weight: 600; }
.det-crumb .current { color: #7c8aa0; }
.btn-nueva { display: inline-flex; align-items: center; background: #1a7a3a; color: #fff; font-weight: 700; font-size: .85rem; border-radius: 9px; padding: 9px 16px; }
.btn-nueva:hover { background: #135c2b; color: #fff; }
.det-main { background: #fff; border: 1px solid #e8eef4; border-radius: 14px; overflow: hidden; box-shadow: 0 6px 18px rgba(16,28,51,.06); }
.det-banner { background: #eef7f0; height: 100%; padding: 22px 20px; }
.det-banner.pend { background: #fef6e0; }
.det-banner.canc { background: #fdecec; }
.det-banner.fin { background: #eef1f6; }
.det-check { width: 44px; height: 44px; border-radius: 50%; background: #1a7a3a; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 1.4rem; margin-bottom: 10px; }
.det-banner.pend .det-check { background: #d97706; }
.det-banner.canc .det-check { background: #dc2626; }
.det-banner.fin .det-check { background: #475569; }
.det-banner h2 { font-size: 1.2rem; font-weight: 800; color: #101c33; margin: 0 0 2px; }
.det-banner p { color: #5b6b82; font-size: .82rem; }
.det-codigo-lbl { font-size: .78rem; color: #101c33; font-weight: 600; margin-top: 14px; }
.det-codigo { background: #d9efe0; color: #166534; font-size: 1.7rem; font-weight: 800; border-radius: 10px; padding: 8px 12px; display: flex; align-items: center; justify-content: space-between; margin-top: 4px; }
.det-banner.pend .det-codigo { background: #fbeecb; color: #92400e; }
.det-banner.canc .det-codigo { background: #fbdcdc; color: #991b1b; }
.det-copy { border: none; background: transparent; color: inherit; font-size: 1rem; }
.det-mail { font-size: .75rem; margin-top: 12px; }
.det-img { position: relative; height: 100%; min-height: 280px; }
.det-img img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.det-img-badge { position: absolute; top: 12px; left: 12px; background: #b8e6c3; color: #14532d; font-size: .72rem; font-weight: 700; border-radius: 8px; padding: 5px 10px; }
.det-img-txt { position: absolute; left: 14px; bottom: 12px; color: #fff; text-shadow: 0 1px 4px rgba(0,0,0,.65); }
.det-img-txt strong { display: block; font-size: 1.15rem; }
.det-img-txt span { font-size: .82rem; opacity: .92; }
.det-info { padding: 18px; }
.det-box { border: 1px solid #e8eef4; border-radius: 10px; padding: 10px; display: flex; gap: 8px; align-items: flex-start; height: 100%; }
.det-box i { font-size: 1.2rem; color: #101c33; }
.det-box small { display: block; color: #7c8aa0; font-size: .68rem; }
.det-box strong { font-size: .74rem; color: #101c33; line-height: 1.25; }
.det-pago-titulo { font-weight: 800; color: #101c33; margin-bottom: 8px; }
.det-pay { border-left: 1px solid #e8eef4; padding-left: 10px; }
.det-pay:first-child { border-left: none; padding-left: 0; }
.dp-ico { font-size: 1.2rem; }
.dp-ico.ok { color: #1a7a3a; } .dp-ico.warn { color: #d97706; } .dp-ico.info { color: #1d4ed8; }
.det-pay small { display: block; color: #7c8aa0; font-size: .68rem; }
.det-pay strong { font-size: 1.05rem; color: #101c33; }
.dp-sub { display: block; font-size: .68rem; color: #7c8aa0; }
.dp-badge { display: inline-block; font-size: .65rem; font-weight: 700; border-radius: 7px; padding: 2px 8px; margin-top: 4px; }
.dp-badge.ok { background: #d9efe0; color: #166534; }
.dp-badge.warn { background: #fbeecb; color: #92400e; }
.btn-comp { display: flex; align-items: center; justify-content: center; border: 1.5px solid #101c33; color: #101c33; font-weight: 700; border-radius: 10px; padding: 11px; width: 100%; font-size: .88rem; }
.btn-comp:hover { background: #101c33; color: #fff; }
.btn-comp.disabled { opacity: .5; pointer-events: none; }
.btn-wa { display: flex; align-items: center; justify-content: center; background: #0d7a33; color: #fff; font-weight: 700; border-radius: 10px; padding: 9px; width: 100%; font-size: .85rem; line-height: 1.2; }
.btn-wa:hover { background: #0a6129; color: #fff; }
.btn-wa i { font-size: 1.5rem; }
.btn-wa small { display: block; font-weight: 400; font-size: .68rem; opacity: .85; }
.det-metodo { font-size: .75rem; color: #5b6b82; margin-top: 10px; }
.det-card { background: #fff; border: 1px solid #e8eef4; border-radius: 14px; padding: 18px; box-shadow: 0 6px 18px rgba(16,28,51,.06); }
.det-h3 { font-size: 1.05rem; font-weight: 800; color: #101c33; margin-bottom: 12px; }
.det-link { color: #1d4ed8; font-weight: 600; font-size: .82rem; }
.det-timeline { display: flex; gap: 12px; }
.dt-item { flex: 1; display: flex; gap: 10px; position: relative; padding-left: 2px; }
.dt-item::before { content: ''; position: absolute; left: 8px; top: 20px; bottom: -6px; width: 2px; background: #e5e9ef; }
.dt-item:last-child::before { display: none; }
.dt-dot { width: 16px; height: 16px; border-radius: 50%; background: #cbd5e1; border: 3px solid #e2e8f0; flex-shrink: 0; margin-top: 2px; }
.dt-item.done .dt-dot { background: #22c55e; border-color: #bbf7d0; }
.dt-item strong { font-size: .82rem; color: #101c33; }
.dt-fecha { font-size: .72rem; color: #7c8aa0; margin-left: 6px; }
.dt-item p { font-size: .75rem; color: #5b6b82; margin: 2px 0 0; }
.det-table thead th { font-size: .72rem; text-transform: uppercase; letter-spacing: .4px; background: #f4f7fb; color: #5b6b82; border: none; white-space: nowrap; }
.det-table td { font-size: .8rem; border-color: #eef2f7; vertical-align: middle; white-space: nowrap; }
.pill { display: inline-block; font-size: .68rem; font-weight: 700; border-radius: 8px; padding: 3px 10px; }
.pill.ok { background: #d9efe0; color: #166534; }
.pill.warn { background: #fbeecb; color: #92400e; }
.pill.bad { background: #fbdcdc; color: #991b1b; }
.pill.fin { background: #e2e8f0; color: #475569; }
.pill.mut { background: #f1f5f9; color: #64748b; }
.btn-ver { display: inline-block; border: 1px solid #cbd5e1; border-radius: 8px; padding: 5px 12px; font-size: .75rem; font-weight: 600; color: #101c33; margin-right: 4px; }
.btn-ver:hover { border-color: #1a7a3a; color: #1a7a3a; }
.btn-ver.danger { color: #dc2626; border-color: #f3c2c2; }
@media (max-width: 991px) {
    .det-h1 { font-size: 1.5rem; }
    .det-img { min-height: 220px; }
    .det-timeline { flex-direction: column; }
    .det-pay { border-left: none; padding-left: 0; border-top: 1px solid #eef2f7; padding-top: 8px; }
}
</style>

<script>
function copiarCodigo(id, btn) {
    const done = () => {
        btn.innerHTML = '<i class="bi bi-check-lg"></i>';
        setTimeout(() => { btn.innerHTML = '<i class="bi bi-copy"></i>'; }, 1500);
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText('#' + id).then(done).catch(done);
    } else { done(); }
}
</script>
