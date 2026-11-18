<?php
declare(strict_types=1);
$idSeleccionado = (int)($canchaSeleccionada['id'] ?? 0);

// Valores pre-seleccionados desde URL (GET)
$fechaPre = $_GET['fecha'] ?? date('Y-m-d');
$horaPre = $_GET['horario'] ?? '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaPre)) {
    $fechaPre = date('Y-m-d');
}

// Duración que llega por URL (paso 1 la manda en minutos). Se usa en horas en este formulario.
$durRawPre = (float)($_GET['duracion'] ?? 0);
$durMinPre = $durRawPre > 8 ? (int)round($durRawPre) : ($durRawPre > 0 ? (int)round($durRawPre * 60) : 60);
$durHorasPre = $durMinPre / 60;
$celularPre = (string)preg_replace('/^\+51\s*/', '', (string)($_GET['celular'] ?? ''));
$origenPre = (string)($_GET['origen'] ?? 'presencial');

// Desde 1 hora más de la actual (ni pasadas ni la actual) si la fecha es hoy (hora de Lima)
$hoyStr = date('Y-m-d');
$esHoy = ($fechaPre === $hoyStr);
// Hora mínima de inicio hoy (Lima): el administrador puede registrar el bloque de media hora en curso
$minHoyAdmin = horaMinimaReserva($hoyStr, true);

 // Cancha inicial para el resumen
$canchaIni = $canchaSeleccionada ?? ($canchas[0] ?? null);
$precioHoraIni = (float)($canchaIni['precio_hora'] ?? 60);
$duracionIni = $durHorasPre;
$totalIni = $precioHoraIni * $duracionIni;
$esAdminView = function_exists('isAdmin') && isAdmin();
$adelantoIni = $esAdminView ? $totalIni : 20;
$saldoIni = max(0, $totalIni - $adelantoIni);
$metodoIni = $esAdminView ? 'efectivo' : 'yape';

$tipoLabels = ['futbol_5' => 'Fútbol 5', 'futbol_7' => 'Fútbol 7', 'futbol_11' => 'Fútbol 11'];
$tipoIni = $tipoLabels[$canchaIni['tipo'] ?? ''] ?? 'Fútbol 5';
$capacidadIni = (int)($canchaIni['capacidad'] ?? 0); // de la base de datos (canchas.capacidad)

// Fecha formateada: Sáb, 26 de abril de 2025
$dias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
$meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$tsFecha = strtotime($fechaPre);
$fechaFormateada = $dias[(int)date('w', $tsFecha)] . ', ' . date('d', $tsFecha) . ' de ' . $meses[(int)date('n', $tsFecha)] . ' de ' . date('Y', $tsFecha);

// Horario inicial
$horaIniStr = $horaPre !== '' ? $horaPre : '10:00';
$horaFinStr = date('H:i', strtotime($horaIniStr . ' +1 hour'));
?>

<div class="container py-3 pago-page">
    <!-- Stepper -->
    <div class="stepper" aria-label="Progreso de reserva">
        <div class="step done">
            <span class="dot"><i class="bi bi-check-lg"></i></span>
            <span class="lbl"><span class="lbl-full">1. Selección de cancha</span><span class="lbl-short">Selección</span></span>
        </div>
        <span class="line done" aria-hidden="true"></span>
        <div class="step active" aria-current="step">
            <span class="dot">2</span>
            <span class="lbl"><span class="lbl-full">2. Pago y comprobante</span><span class="lbl-short">Pago</span></span>
        </div>
        <span class="line" aria-hidden="true"></span>
        <div class="step">
            <span class="dot">3</span>
            <span class="lbl"><span class="lbl-full">3. Confirmación</span><span class="lbl-short">Confirmación</span></span>
        </div>
    </div>

    <div class="row g-4">
        <!-- Columna izquierda -->
        <div class="col-lg-7">

            <form action="<?= url('/reserva/guardar') ?>" method="POST" enctype="multipart/form-data" class="needs-validation" id="formPago" novalidate>
                <?= csrf_field() ?>

                <div class="alert alert-danger d-none" id="formAlert" role="alert">
                    <strong><i class="bi bi-exclamation-triangle-fill me-1"></i> Revisa lo siguiente antes de realizar tu reserva:</strong>
                    <ul class="mb-0 mt-1" id="formAlertList"></ul>
                </div>

                <?php if (!$esAdminView): ?>
                <!-- Datos vienen del formulario previo (paso 1) - ocultos para cliente -->
                <input type="hidden" name="cancha_id" id="cancha_id" value="<?= htmlspecialchars($_GET['cancha_id'] ?? $idSeleccionado) ?>">
                <input type="hidden" name="fecha" id="fecha" value="<?= htmlspecialchars($_GET['fecha'] ?? $fechaPre) ?>">
                <input type="hidden" name="hora_inicio" id="hora_inicio" value="<?= htmlspecialchars($_GET['horario'] ?? $horaPre) ?>">
                <input type="hidden" name="duracion_horas" id="duracion_horas" value="<?= htmlspecialchars((string)$durHorasPre) ?>">
                <input type="hidden" name="contacto_telefono" id="contacto_telefono" value="<?= htmlspecialchars($_GET['celular'] ?? '') ?>">
                <input type="hidden" name="observaciones" value="<?= htmlspecialchars($_GET['observaciones'] ?? '') ?>">
                <input type="hidden" name="cliente_nombre" value="<?= htmlspecialchars($_GET['nombre_cliente'] ?? '') ?>">
                <?php endif; ?>

                <?php if ($esAdminView): ?>
                <!-- Datos de la reserva (editables, compactos) - solo admin en una sola página -->
                <div class="pago-card mb-3">
                    <div class="pago-card-title">Datos de tu reserva</div>
                    <?php if ($esAdminView): ?>
                    <div class="row g-2 mb-2">
                        <div class="col-md-7">
                            <label for="cliente_nombre" class="form-label fw-semibold small">Nombre del cliente <span class="text-danger">*</span></label>
                            <div class="cs-wrap" style="position:relative;">
                                <input type="text" class="form-control" id="cliente_nombre" name="cliente_nombre" placeholder="Ej: Juan Pérez" autocomplete="off" value="<?= htmlspecialchars((string)($_GET['nombre_cliente'] ?? '')) ?>" required>
                                <div id="clienteDropdown" class="cs-dropdown d-none"></div>
                            </div>
                            <input type="hidden" id="cliente_id" name="cliente_id" value="0">
                        </div>
                        <div class="col-md-5">
                            <label for="origen" class="form-label fw-semibold small">Canal / Origen <span class="text-danger">*</span></label>
                            <select class="form-select" id="origen" name="origen" required>
                                <option value="presencial" <?= $origenPre === 'presencial' || !in_array($origenPre, ['llamada', 'whatsapp'], true) ? 'selected' : '' ?>>Presencial (Mostrador)</option>
                                <option value="llamada" <?= $origenPre === 'llamada' ? 'selected' : '' ?>>Llamada telefónica</option>
                                <option value="whatsapp" <?= $origenPre === 'whatsapp' ? 'selected' : '' ?>>WhatsApp</option>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label for="cancha_id" class="form-label fw-semibold small">Cancha <span class="text-danger">*</span></label>
                            <select class="form-select" id="cancha_id" name="cancha_id" required>
                                <option value="" disabled <?= $idSeleccionado === 0 && empty($canchas) ? 'selected' : '' ?>>-- Elige tu cancha --</option>
                                <?php foreach ($canchas as $c): ?>
                                    <option value="<?= $c['id'] ?>"
                                        data-precio="<?= $c['precio_hora'] ?>"
                                        data-nombre="<?= htmlspecialchars($c['nombre']) ?>"
                                        data-tipo="<?= htmlspecialchars($tipoLabels[$c['tipo']] ?? $c['tipo']) ?>"
                                        data-capacidad="<?= (int)$c['capacidad'] ?>"
                                        <?= (int)$c['id'] === $idSeleccionado ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['nombre']) ?> (<?= htmlspecialchars($tipoLabels[$c['tipo']] ?? $c['tipo']) ?>) - <?= formatPrice($c['precio_hora']) ?>/h
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="fecha" class="form-label fw-semibold small">Fecha <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="fecha" name="fecha" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($fechaPre) ?>" required>
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="hora_inicio" class="form-label fw-semibold small">Hora <span class="text-danger">*</span></label>
                            <select class="form-select" id="hora_inicio" name="hora_inicio" required>
                                <option value="" disabled <?= $horaPre === '' ? 'selected' : '' ?>>--:--</option>
                                <?php
                                for ($h = 7; $h <= 23; $h++):
                                    foreach (reservaMinutosInicio() as $mm):
                                        $horaStr = sprintf('%02d:%s', $h, $mm);
                                        $pasada = ($esHoy && $horaStr < $minHoyAdmin);
                                ?>
                                    <option value="<?= $horaStr ?>" <?= $pasada ? 'disabled hidden' : '' ?> <?= $horaPre === $horaStr && !$pasada ? 'selected' : '' ?>><?= date('g:i A', strtotime($horaStr)) ?></option>
                                <?php endforeach; endfor; ?>
                            </select>
                            <small class="text-muted <?= ($esHoy && $minHoyAdmin === '24:00') ? '' : 'd-none' ?>" id="sinHorariosHoy">Por hoy ya no quedan horarios, elige otra fecha.</small>
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="duracion_horas" class="form-label fw-semibold small">Duración <span class="text-danger">*</span></label>
                            <select class="form-select" id="duracion_horas" name="duracion_horas" required>
                                <?php
                                $durOpciones = [0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 5, 6];
                                foreach ($durOpciones as $d):
                                    $lbl = formatoDuracionHoras((float)$d);
                                ?>
                                    <option value="<?= $d ?>" <?= $d == $duracionIni ? 'selected' : '' ?>><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="contacto_telefono" class="form-label fw-semibold small">Número de contacto (WhatsApp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:#f8fafc; font-weight:700; color:#334155; border-color:#e2e8f0;">+51</span>
                                <input type="tel" class="form-control" id="contacto_telefono" name="contacto_telefono" placeholder="999 999 999" value="<?= $esAdminView ? htmlspecialchars($celularPre) : htmlspecialchars(preg_replace('/^\\+51\\s*/', '', $_SESSION['usuario_telefono'] ?? '')) ?>" required>
                            </div>
                            <div class="invalid-feedback">Ingresa tu número de celular (WhatsApp).</div>
                        </div>
                        <div class="col-md-12">
                            <label for="observaciones" class="form-label fw-semibold small">Observaciones (opcional)</label>
                            <input type="text" class="form-control" id="observaciones" name="observaciones" placeholder="Ej: Necesitamos chalecos..." value="<?= htmlspecialchars((string)($_GET['observaciones'] ?? '')) ?>">
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Sección 1 -->
                <div class="pago-card mb-3" id="seccionPago">
                    <?php if ($esAdminView): ?>
                    <h2 class="pago-h2"><span class="pago-num">1.</span> Cobro y método de pago</h2>
                    <p class="pago-text" id="pagoTextoAdmin">Reserva presencial / mostrador: selecciona el tipo de cobro y el medio de pago recibido.</p>

                    <div class="p-3 mb-3" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px;">
                        <label class="form-label fw-bold small text-dark mb-2"><i class="bi bi-cash-coin me-1 text-success"></i> Modalidad de cobro presencial:</label>
                        <div class="d-flex gap-3 flex-wrap">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo_cobro" id="tipoCobroCompleto" value="completo" checked onchange="cambiarTipoCobroAdmin('completo')">
                                <label class="form-check-label fw-semibold" for="tipoCobroCompleto">
                                    Pago completo en mostrador <span class="badge bg-success-subtle text-success ms-1">Saldo S/ 0</span>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo_cobro" id="tipoCobroAdelanto" value="adelanto" onchange="cambiarTipoCobroAdmin('adelanto')">
                                <label class="form-check-label fw-semibold" for="tipoCobroAdelanto">
                                    Solo adelanto de S/ 20 <span class="badge bg-warning-subtle text-warning-emphasis ms-1">Deja saldo pendiente</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <h2 class="pago-h2"><span class="pago-num">1.</span> Realiza el pago del adelanto</h2>
                    <p class="pago-text">Realiza el adelanto habitual de S/ 20 mediante Yape. Luego sube tu comprobante de pago.</p>
                    <?php endif; ?>

                    <input type="hidden" name="metodo_pago" id="metodo_pago" value="<?= htmlspecialchars($metodoIni) ?>">
                    <div class="row g-2 mb-3" role="group" aria-label="Método de pago">
                        <?php if ($esAdminView): ?>
                        <div class="col-lg-4 col-md-4 col-sm-12">
                            <button type="button" class="metodo-btn active" id="btnEfectivo" onclick="elegirMetodo('efectivo')">
                                <span class="logo-metodo" style="background:#1a7a3a;">S/</span>
                                <span>Efectivo</span>
                            </button>
                        </div>
                        <div class="col-lg-4 col-md-4 col-sm-12">
                            <button type="button" class="metodo-btn" id="btnNiubiz" onclick="elegirMetodo('niubiz')">
                                <span class="logo-metodo" style="background:#005bb5;">POS</span>
                                <span>Niubiz (POS)</span>
                            </button>
                        </div>
                        <div class="col-lg-4 col-md-4 col-sm-12">
                            <button type="button" class="metodo-btn" id="btnYape" onclick="elegirMetodo('yape')">
                                <span class="yape-logo">yape</span>
                                <span>Yape</span>
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="col-12">
                            <button type="button" class="metodo-btn active" id="btnYape" onclick="elegirMetodo('yape')" style="cursor:default;">
                                <span class="yape-logo">yape</span>
                                <span>Yape — Adelanto S/ 20</span>
                                <span class="badge bg-success ms-auto"><i class="bi bi-shield-check me-1"></i> Oficial</span>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($esAdminView): ?>
                    <!-- Panel Efectivo (presencial) -->
                    <div class="pago-panel" id="panelEfectivo">
                        <div class="pago-importante" style="background:#eef7f0;">
                            <div class="fw-bold mb-1"><span class="info-ico"><i class="bi bi-cash-stack"></i></span> Cobro presencial en efectivo</div>
                            <ul class="mb-0">
                                <li>El encargado recibe el dinero en efectivo en el mostrador.</li>
                                <li>Se registra directamente a la caja del día sin comprobante obligatorio.</li>
                                <li>La reserva queda <strong>confirmada y verificada</strong> en el sistema.</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Panel Niubiz POS (presencial) -->
                    <div class="pago-panel d-none" id="panelNiubiz">
                        <div class="pago-importante" style="background:#f0f7ff; color:#1e3a8a;">
                            <div class="fw-bold mb-1"><span class="info-ico" style="background:#005bb5;"><i class="bi bi-credit-card-2-front"></i></span> Cobro presencial con Niubiz POS</div>
                            <ul class="mb-0">
                                <li>El cliente paga mediante tarjeta de débito o crédito en el equipo POS físico de Top Gol.</li>
                                <li>Ingreso registrado con método Niubiz para el cuadre de caja de POS.</li>
                                <li>La reserva queda <strong>confirmada y verificada</strong> al registrarse.</li>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Panel Yape -->
                    <div class="pago-panel <?= $esAdminView ? 'd-none' : '' ?>" id="panelYape">
                        <div class="row g-3">
                            <?php ob_start(); ?>
                                <div class="pago-importante">
                                    <div class="fw-bold mb-1"><span class="info-ico"><i class="bi bi-info-lg"></i></span> Importante</div>
                                    <ul class="mb-0">
                                        <li>Monto del adelanto: <strong>S/ 20</strong> por cancha reservada.</li>
                                        <li>Luego de pagar, sube la captura de pantalla de tu constancia de Yape.</li>
                                        <li>El encargado verificará el comprobante para confirmar tu reserva.</li>
                                    </ul>
                                </div>
                            <?php $yapeImportanteHtml = ob_get_clean(); ?>
                            <?php include __DIR__ . DIRECTORY_SEPARATOR . '_yape_datos.php'; ?>
                        </div>
                    </div>
                </div>

                <?php if (!$esAdminView): ?>
                <?php require __DIR__ . '/_tolerancia.php'; ?>
                <?php endif; ?>

                <!-- Sección 2 -->
                <div class="pago-card mb-3 <?= $esAdminView ? 'd-none' : '' ?>" id="seccionComprobante" style="<?= $esAdminView ? 'display:none;' : '' ?>">
                    <h2 class="pago-h2"><span class="pago-num">2.</span> Sube tu comprobante de pago <?= $esAdminView ? '<small class="text-muted" id="compOpcional">(no requerido para usuario administrador)</small>' : '<span class="text-danger">*</span>' ?></h2>
                    <p class="pago-text" id="compTexto">Adjunta una imagen o captura de tu comprobante de pago <strong><?= $esAdminView ? '(obligatorio solo para Yape)' : '(obligatorio)' ?></strong> (formato JPG, PNG o PDF. Máx. 5MB).</p>

                    <label class="dropzone" id="dropzone" for="comprobante">
                        <input type="file" id="comprobante" name="comprobante" accept=".jpg,.jpeg,.png,.pdf" hidden>
                        <span class="dz-ico"><i class="bi bi-cloud-upload-fill"></i></span>
                        <strong>Haz clic para subir tu comprobante</strong>
                        <span class="dz-sub">o arrastra y suelta tu archivo aquí</span>
                        <span class="dz-file d-none" id="fileInfo"></span>
                    </label>
                    <div class="dz-formats">Formatos soportados: JPG, PNG, PDF (Máx. 5MB)</div>
                    <div class="invalid-feedback d-none" id="fileError">Archivo no válido. Usa JPG, PNG o PDF de máximo 5MB.</div>
                </div>

                <div class="d-flex gap-2 mt-2">
                    <?php if (!$esAdminView): ?>
                    <a href="<?= url('/reserva/formulario/' . $idSeleccionado) ?>?fecha=<?= urlencode($fechaPre) ?>&horario=<?= urlencode($horaPre) ?>&duracion=<?= urlencode((string)$durMinPre) ?>&celular=<?= urlencode($_GET['celular'] ?? '') ?>&observaciones=<?= urlencode($_GET['observaciones'] ?? '') ?>&nombre_cliente=<?= urlencode($_GET['nombre_cliente'] ?? '') ?>" class="btn btn-outline-secondary p-3 fw-bold flex-grow-1" style="border-radius:10px; width:50%;">
                        <i class="bi bi-arrow-left me-2"></i> Volver a editar reserva
                    </a>
                    <?php endif; ?>
                    <button type="submit" class="btn-enviar flex-grow-1" style="<?= !$esAdminView ? 'width:50%; margin:0;' : 'margin-top:0;' ?>">
                        <i class="bi bi-send me-2"></i> Realizar Reserva
                    </button>
                </div>
            </form>
        </div>

        <!-- Columna derecha: resumen -->
        <div class="col-lg-5">
            <h2 class="resumen-h2">Resumen de tu reserva</h2>
            <div class="resumen-card">
                <div class="resumen-img">
                    <img src="https://images.unsplash.com/photo-1522778119026-d647f0596c20?w=800&q=80&fit=crop" alt="Cancha Top Gol de noche">
                    <span class="resumen-badge"><i class="bi bi-shield-fill-check me-1"></i> Cancha reservada</span>
                    <div class="resumen-img-txt">
                        <strong id="resCanchaNombre"><?= htmlspecialchars(mb_strtoupper($canchaIni['nombre'] ?? 'CANCHA 1')) ?></strong>
                        <span id="resCanchaTipo"><?= htmlspecialchars($tipoIni) ?> - Césped sintético</span>
                    </div>
                </div>

                <ul class="resumen-list">
                    <li>
                        <span class="rl-ico"><i class="bi bi-calendar3"></i></span>
                        <span><small>Fecha</small><strong id="resFecha"><?= htmlspecialchars($fechaFormateada) ?></strong></span>
                    </li>
                    <li>
                        <span class="rl-ico"><i class="bi bi-clock"></i></span>
                        <span><small>Horario</small><strong id="resHorario"><?= htmlspecialchars($horaIniStr) ?> - <?= htmlspecialchars($horaFinStr) ?> (<?= htmlspecialchars(formatoDuracionHoras((float)$duracionIni)) ?>)</strong></span>
                    </li>
                    <li>
                        <span class="rl-ico"><i class="bi bi-people-fill"></i></span>
                        <span><small>Número de personas</small><strong id="resPersonas"><?= $capacidadIni > 0 ? 'Hasta ' . $capacidadIni . ' personas' : 'Por confirmar' ?></strong></span>
                    </li>
                    <li>
                        <span class="rl-ico"><i class="bi bi-geo-alt-fill"></i></span>
                        <span><small>Ubicación</small><strong>Top Gol Tacna</strong></span>
                    </li>
                </ul>

                <div class="resumen-pago">
                    <div class="rp-title">Detalle de pago</div>
                    <div class="rp-row"><span>Precio por hora</span><strong id="resPrecioHora">S/ <?= number_format($precioHoraIni, 0) ?></strong></div>
                    <div class="rp-row"><span>Total de la reserva</span><strong id="resTotal">S/ <?= number_format($totalIni, 0) ?></strong></div>
                    <div class="rp-row verde"><span>Adelanto requerido (hoy)</span><strong>S/ 20</strong></div>
                    <div class="rp-row saldo"><span>Saldo pendiente</span><strong id="resSaldo">S/ <?= number_format($saldoIni, 0) ?></strong></div>
                </div>

                <div class="resumen-alerta">
                    <span class="ra-ico"><i class="bi bi-clock-history"></i></span>
                    <span>
                        <strong>Revisión de Pago</strong>
                        <small><?= isAdmin() ? 'Una vez verificado el comprobante, la reserva queda confirmada.' : htmlspecialchars(textoVerificacionPago()) ?></small>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.pago-page { background: #f6f9fb; border-radius: 12px; }
.pago-breadcrumb { font-size: .8rem; color: #64748b; margin-bottom: 2px; }
.pago-breadcrumb a { color: #64748b; }
.pago-breadcrumb .sep { margin: 0 6px; }
.pago-breadcrumb .current { color: #1a7a3a; font-weight: 600; }
.pago-h1 { font-size: 2rem; font-weight: 800; color: #101c33; margin: 0; }
.pago-sub { color: #7c8aa0; margin: 2px 0 12px; }
.pago-seguro { align-items: center; gap: 8px; color: #101c33; }
.pago-seguro-ico { color: #1a7a3a; font-size: 1.6rem; }
.pago-seguro strong { display: block; font-size: .85rem; }
.pago-seguro small { color: #7c8aa0; font-size: .72rem; }
/* Stepper */
.stepper { display: flex; align-items: flex-start; gap: 8px; margin: 6px 0 18px; }
.step { display: flex; align-items: center; gap: 8px; }
.step .dot { width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; background: #e5e9ef; color: #8a97a8; flex-shrink: 0; }
.step .lbl { font-size: .75rem; color: #8a97a8; line-height: 1.2; }
.step.done .dot { background: #1a7a3a; color: #fff; }
.step.done .lbl { color: #101c33; font-weight: 600; }
.step.active .dot { background: #22c55e; color: #fff; box-shadow: 0 0 0 5px rgba(34,197,94,.18); }
.step.active .lbl { color: #101c33; font-weight: 600; }
.stepper .line { flex: 1; height: 3px; border-radius: 3px; background: #e5e9ef; margin-top: 16px; min-width: 30px; }
.stepper .line.done { background: #22c55e; }
.step .lbl-short { display: none; }
@media (max-width: 991px) { .pago-h1 { font-size: 1.6rem; } }
@media (max-width: 576px) {
    .stepper { gap: 6px; margin: 2px 0 14px; }
    .step { gap: 6px; min-width: 0; }
    .step .dot { width: 28px; height: 28px; font-size: .8rem; }
    .step.active .dot { box-shadow: 0 0 0 4px rgba(34,197,94,.18); }
    .step .lbl { font-size: .68rem; line-height: 1.15; }
    .step .lbl-full { display: none; }
    .step .lbl-short { display: inline; }
    .stepper .line { min-width: 12px; margin-top: 13px; }
}
@media (max-width: 360px) {
    .step .lbl { font-size: .62rem; }
    .stepper { gap: 4px; }
}
/* Cliente searchable select (Google Drive style) */
.cs-dropdown { position:absolute; top:calc(100% + 6px); left:0; right:0; background:#fff; border:1px solid #e2e8f0; border-radius:12px; box-shadow:0 12px 28px rgba(16,28,51,.14); max-height:260px; overflow-y:auto; z-index:1050; padding:4px; }
.cs-item { display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:8px; cursor:pointer; }
.cs-item:hover, .cs-item.active { background:#f1f5f9; }
.cs-avatar { width:32px; height:32px; border-radius:50%; background:#eef7f0; color:#1a7a3a; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:.85rem; }
.cs-info { min-width:0; flex:1; }
.cs-name { font-size:.82rem; font-weight:600; color:#101c33; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.cs-mail { font-size:.7rem; color:#7c8aa0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.cs-empty { padding:12px; text-align:center; font-size:.8rem; color:#7c8aa0; }
.cs-mark { background:#fef08a; padding:0 2px; border-radius:3px; }
/* Cards */
.pago-card { background: #fff; border: 1px solid #e8eef4; border-radius: 12px; padding: 18px; box-shadow: 0 1px 2px rgba(16,28,51,.04); }
.pago-card-title { font-weight: 800; color: #101c33; margin-bottom: 10px; }
.pago-h2 { font-size: 1.05rem; font-weight: 800; color: #101c33; margin-bottom: 2px; }
.pago-num { font-weight: 900; }
.pago-text { color: #7c8aa0; font-size: .85rem; margin-bottom: 12px; }
/* Métodos */
.metodo-btn { width: 100%; display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 10px; border: 1.5px solid #e2e8f0; background: #fff; font-weight: 700; color: #101c33; transition: all .15s; }
.metodo-btn.active { background: #effaf1; border-color: #22c55e; }
.yape-logo { background: #742284; color: #fff; font-style: italic; font-weight: 800; border-radius: 8px; padding: 6px 10px; font-size: .85rem; }
.logo-metodo { background: #002a8d; color: #fff; font-weight: 800; border-radius: 8px; padding: 6px 10px; font-size: .8rem; letter-spacing: .5px; }
/* Panel */
.pago-panel { border: 1px solid #e8eef4; border-radius: 10px; padding: 14px; }
.qr-title { font-size: .8rem; font-weight: 600; margin-bottom: 8px; }
.qr-wrap { position: relative; display: inline-block; }
.qr-wrap img { border-radius: 8px; display: block; }
.qr-logo { position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); background: #742284; color: #fff; font-style: italic; font-weight: 800; border-radius: 12px; padding: 10px 16px; border: 3px solid #fff; }
.pago-numero-box { background: #f4f7fb; border-radius: 10px; padding: 12px; margin-bottom: 12px; color: #101c33; }
.tel-ico { width: 32px; height: 32px; border-radius: 50%; background: #fff; display: inline-flex; align-items: center; justify-content: center; color: #101c33; }
.tel-num { font-size: 1.15rem; letter-spacing: .5px; }
.btn-copiar { border: none; background: transparent; color: #1a7a3a; font-weight: 700; font-size: .85rem; }
.pago-importante { background: #e9f7ee; border-radius: 10px; padding: 12px 14px; font-size: .8rem; color: #23402e; }
.pago-importante ul { margin: 6px 0 0; padding-left: 18px; }
.info-ico { display: inline-flex; width: 22px; height: 22px; border-radius: 50%; background: #1a7a3a; color: #fff; align-items: center; justify-content: center; font-size: .8rem; margin-right: 4px; }
/* Dropzone */
.dropzone { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; border: 1.5px dashed #c4cfdb; background: #f7fafc; border-radius: 10px; padding: 28px 16px; cursor: pointer; text-align: center; transition: all .15s; }
.dropzone:hover, .dropzone.over { border-color: #22c55e; background: #effaf1; }
.dz-ico { font-size: 2rem; color: #42506b; }
.dz-sub { color: #7c8aa0; font-size: .85rem; }
.dz-formats { text-align: center; color: #7c8aa0; font-size: .72rem; margin-top: 6px; }
.dz-file { margin-top: 8px; font-size: .8rem; background: #e9f7ee; color: #166534; border-radius: 8px; padding: 6px 10px; }
.btn-enviar { width: 100%; background: #0d7a33; color: #fff; border: none; border-radius: 10px; padding: 13px; font-weight: 800; font-size: 1rem; transition: all .15s; }
.btn-enviar:hover { background: #0a6129; color: #fff; }
/* Resumen */
.resumen-h2 { font-size: 1.2rem; font-weight: 800; color: #101c33; margin-bottom: 10px; }
.resumen-card { background: #fff; border: 1px solid #e8eef4; border-radius: 14px; overflow: hidden; box-shadow: 0 6px 18px rgba(16,28,51,.06); }
.resumen-img { position: relative; height: 190px; }
.resumen-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
.resumen-badge { position: absolute; top: 12px; left: 12px; background: #b8e6c3; color: #14532d; font-size: .72rem; font-weight: 700; border-radius: 8px; padding: 5px 10px; }
.resumen-img-txt { position: absolute; left: 14px; bottom: 12px; color: #fff; text-shadow: 0 1px 4px rgba(0,0,0,.6); }
.resumen-img-txt strong { display: block; font-size: 1rem; letter-spacing: .5px; }
.resumen-img-txt span { font-size: .8rem; opacity: .9; }
.resumen-list { list-style: none; margin: 0; padding: 12px 16px 4px; }
.resumen-list li { display: flex; gap: 10px; padding: 7px 0; color: #101c33; }
.rl-ico { color: #101c33; font-size: 1.2rem; width: 26px; text-align: center; }
.resumen-list small { display: block; color: #7c8aa0; font-size: .72rem; }
.resumen-list strong { font-size: .85rem; }
.resumen-pago { background: #f4f7fb; border-radius: 10px; margin: 8px 14px; padding: 12px 14px; font-size: .85rem; }
.rp-title { font-weight: 800; margin-bottom: 6px; color: #101c33; }
.rp-row { display: flex; justify-content: space-between; padding: 3px 0; color: #33415c; }
.rp-row.verde { color: #1a7a3a; font-weight: 700; }
.rp-row.saldo { background: #e9f7ee; color: #166534; font-weight: 800; border-radius: 8px; padding: 8px 10px; margin-top: 6px; }
.resumen-alerta { display: flex; gap: 10px; background: #fef6e0; border-radius: 10px; margin: 12px 14px 16px; padding: 12px 14px; }
.ra-ico { color: #d97706; font-size: 1.4rem; }
.resumen-alerta strong { display: block; color: #b45309; font-size: .9rem; }
.resumen-alerta small { color: #7c6a3a; font-size: .78rem; }
</style>

<script>
var ES_ADMIN = <?= $esAdminView ? 'true' : 'false' ?>;
var actualizarResumenGlobal = null;
var tipoCobroAdminActual = 'completo';

function cambiarTipoCobroAdmin(tipo) {
    tipoCobroAdminActual = tipo;
    if (typeof actualizarResumenGlobal === 'function') actualizarResumenGlobal();
}

function elegirMetodo(m) {
    var esEfectivo = m === 'efectivo';
    var esNiubiz = m === 'niubiz';
    var esYape = m === 'yape';
    var btnEfe = document.getElementById('btnEfectivo');
    var btnNiu = document.getElementById('btnNiubiz');
    var btnYape = document.getElementById('btnYape');
    var pEfe = document.getElementById('panelEfectivo');
    var pNiu = document.getElementById('panelNiubiz');
    var pYape = document.getElementById('panelYape');
    var secComp = document.getElementById('seccionComprobante');

    if(btnEfe) btnEfe.classList.toggle('active', esEfectivo);
    if(btnNiu) btnNiu.classList.toggle('active', esNiubiz);
    if(btnYape) btnYape.classList.toggle('active', esYape);

    if(pEfe) pEfe.classList.toggle('d-none', !esEfectivo);
    if(pNiu) pNiu.classList.toggle('d-none', !esNiubiz);
    if(pYape) pYape.classList.toggle('d-none', !esYape);

    document.getElementById('metodo_pago').value = m;

    if(ES_ADMIN && secComp){
        var ocultar = esEfectivo || esNiubiz;
        secComp.classList.toggle('d-none', ocultar);
        secComp.style.display = ocultar ? 'none' : '';
    }
    if(typeof actualizarResumenGlobal === 'function') actualizarResumenGlobal();
}

function copiarNumero(num, btn) {
    const done = () => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Copiado';
        setTimeout(() => { btn.innerHTML = orig; }, 1500);
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(num).then(done).catch(done);
    } else {
        const t = document.createElement('textarea');
        t.value = num;
        document.body.appendChild(t);
        t.select();
        try { document.execCommand('copy'); } catch (e) {}
        document.body.removeChild(t);
        done();
    }
}

(function () {
    const dz = document.getElementById('dropzone');
    const input = document.getElementById('comprobante');
    const info = document.getElementById('fileInfo');
    const err = document.getElementById('fileError');
    const validTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    const maxSize = 5 * 1024 * 1024;

    function validar(file) {
        if (!file) return true;
        const okType = validTypes.includes(file.type) || /\.(jpe?g|png|pdf)$/i.test(file.name);
        const okSize = file.size <= maxSize;
        err.classList.toggle('d-none', okType && okSize);
        return okType && okSize;
    }

    function mostrar(file) {
        if (!file) return;
        const kb = (file.size / 1024).toFixed(0);
        info.textContent = '📎 ' + file.name + ' (' + kb + ' KB)';
        info.classList.remove('d-none');
    }

    input.addEventListener('change', () => {
        const f = input.files[0];
        if (f && validar(f)) mostrar(f);
        else if (f) { input.value = ''; info.classList.add('d-none'); }
    });

    ['dragenter', 'dragover'].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); dz.classList.add('over'); }));
    ['dragleave', 'drop'].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); dz.classList.remove('over'); }));
    dz.addEventListener('drop', e => {
        const f = e.dataTransfer.files && e.dataTransfer.files[0];
        if (!f) return;
        if (!validar(f)) { input.value = ''; info.classList.add('d-none'); return; }
        const dt = new DataTransfer();
        dt.items.add(f);
        input.files = dt.files;
        mostrar(f);
    });

    // Resumen dinámico (soporta admin con selects y cliente con hidden)
    const selCancha = document.getElementById('cancha_id');
    const inpFecha = document.getElementById('fecha');
    const selHora = document.getElementById('hora_inicio');
    const selDur = document.getElementById('duracion_horas');
    // Mismo formato que formatoDuracion() de PHP: 30 min, 1 h, 1 h 30 min
    function fmtDuracion(min) {
        if (min < 60) return min + ' min';
        return (min % 60 === 0) ? (min / 60) + ' h' : Math.floor(min / 60) + ' h ' + (min % 60) + ' min';
    }
    const dias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    const meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    const canchasData = <?= json_encode(array_map(function($c) use ($tipoLabels){ return ['id'=> (int)$c['id'], 'nombre'=>$c['nombre'], 'tipo'=>$tipoLabels[$c['tipo']] ?? $c['tipo'], 'precio'=>$c['precio_hora'], 'capacidad'=>$c['capacidad']]; }, $canchas), JSON_UNESCAPED_UNICODE) ?>;

    function getCanchaData(){
        if(!selCancha) return null;
        if(selCancha.tagName === 'SELECT'){
            const opt = selCancha.options[selCancha.selectedIndex];
            if(!opt || !opt.value) return null;
            return {
                precio: parseFloat(opt.dataset.precio || '0'),
                nombre: opt.dataset.nombre || '',
                tipo: opt.dataset.tipo || '',
                capacidad: opt.dataset.capacidad || ''
            };
        } else {
            const id = selCancha.value;
            const found = canchasData.find(function(c){ return String(c.id) === String(id); });
            if(found) return { precio: parseFloat(found.precio), nombre: found.nombre, tipo: found.tipo, capacidad: found.capacidad };
            return null;
        }
    }

    function actualizarResumen() {
        const data = getCanchaData();
        const precio = data ? parseFloat(data.precio || '0') : 0;
        const nombre = data ? data.nombre : '';
        const tipo = data ? data.tipo : '';
        const cap = data ? data.capacidad : '';
        const dur = parseFloat((selDur && selDur.value) ? selDur.value : '1');
        const total = precio * dur;
        var esCompletoAdmin = ES_ADMIN && tipoCobroAdminActual === 'completo';
        const saldo = esCompletoAdmin ? 0 : Math.max(0, total - 20);
        const adelanto = esCompletoAdmin ? total : Math.min(total, 20);

        if (nombre) document.getElementById('resCanchaNombre').textContent = nombre.toUpperCase();
        if (tipo) document.getElementById('resCanchaTipo').textContent = tipo + ' - Césped sintético';
        if (cap) document.getElementById('resPersonas').textContent = 'Hasta ' + cap + ' personas';

        const f = new Date(inpFecha.value + 'T12:00:00');
        if (!isNaN(f)) {
            document.getElementById('resFecha').textContent =
                dias[f.getDay()] + ', ' + String(f.getDate()).padStart(2, '0') + ' de ' + meses[f.getMonth() + 1] + ' de ' + f.getFullYear();
        }

        if (selHora.value) {
            const ini = selHora.value;
            const finDate = new Date('2000-01-01T' + ini + ':00');
            finDate.setTime(finDate.getTime() + Math.round(dur * 3600 * 1000));
            const fin = String(finDate.getHours()).padStart(2, '0') + ':' + String(finDate.getMinutes()).padStart(2, '0');
            document.getElementById('resHorario').textContent = ini + ' - ' + fin + ' (' + fmtDuracion(Math.round(dur * 60)) + ')';
        }

        document.getElementById('resPrecioHora').textContent = 'S/ ' + precio.toFixed(0);
        document.getElementById('resTotal').textContent = 'S/ ' + total.toFixed(0);
        document.getElementById('resSaldo').textContent = 'S/ ' + saldo.toFixed(0);
        var adelantoRow = document.querySelector('.resumen-pago .rp-row.verde strong');
        if(adelantoRow) adelantoRow.textContent = 'S/ ' + adelanto.toFixed(0);
        var adelantoLbl = document.querySelector('.resumen-pago .rp-row.verde span');
        if(adelantoLbl) adelantoLbl.textContent = esCompletoAdmin ? 'Cobro completo en mostrador' : 'Adelanto requerido (hoy)';
    }
    actualizarResumenGlobal = actualizarResumen;

    // Oculta las horas que ya pasaron cuando la fecha elegida es hoy (hora de Lima) - solo si es select
    const HOY_STR = <?= json_encode($hoyStr) ?>;
    const MIN_HOY = <?= json_encode($minHoyAdmin) ?>; // 'HH:MM' (hora de Lima calculada en el servidor)
    function filtrarHoras() {
        if(!selHora || selHora.tagName !== 'SELECT' || !inpFecha) return;
        const esHoySel = (inpFecha.value === HOY_STR);
        let seleccionValida = true;
        let visibles = 0;
        Array.from(selHora.options).forEach(o => {
            if (!o.value) return;
            const pasada = esHoySel && o.value < MIN_HOY;
            o.hidden = pasada;
            o.disabled = pasada;
            if (!pasada) visibles++;
            if (pasada && o.selected) seleccionValida = false;
        });
        if (!seleccionValida) selHora.value = '';
        const aviso = document.getElementById('sinHorariosHoy');
        if (aviso) aviso.classList.toggle('d-none', !(esHoySel && visibles === 0));
    }

    [selCancha, inpFecha, selHora, selDur].forEach(el => el && el.addEventListener('change', actualizarResumen));
    inpFecha.addEventListener('change', filtrarHoras);
    filtrarHoras();
    actualizarResumen();

    // Admin: sincronizar cliente_nombre con datalist -> cliente_id oculto
    // --- Cliente searchable select (Google Drive style) ---
    var clienteNombreInput = document.getElementById('cliente_nombre');
    var clienteIdInput = document.getElementById('cliente_id');
    var clienteDropdown = document.getElementById('clienteDropdown');
    var clientesData = <?= json_encode(array_map(function($c){ return ['id'=> (int)$c['id'], 'nombre'=> $c['nombre'], 'email'=> $c['email'], 'telefono'=> $c['telefono'] ?? '']; }, $clientes ?? []), JSON_UNESCAPED_UNICODE) ?>;

    function escHtml(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
    function highlight(text, q){
        if(!q) return escHtml(text);
        var idx = text.toLowerCase().indexOf(q.toLowerCase());
        if(idx === -1) return escHtml(text);
        return escHtml(text.slice(0,idx)) + '<span class="cs-mark">' + escHtml(text.slice(idx, idx+q.length)) + '</span>' + escHtml(text.slice(idx+q.length));
    }
    function filtrarClientes(q){
        q = (q||'').trim().toLowerCase();
        if(q.length < 1) return clientesData.slice(0,8);
        return clientesData.filter(function(c){
            return c.nombre.toLowerCase().includes(q) || c.email.toLowerCase().includes(q);
        });
    }
    function renderDropdown(){
        if(!clienteDropdown || !clienteNombreInput) return;
        var q = clienteNombreInput.value;
        var lista = filtrarClientes(q);
        if(q.trim().length < 1){
            // al hacer foco sin escribir, muestra hasta 8 recientes
            lista = clientesData.slice(0,8);
        }
        if(lista.length === 0){
            clienteDropdown.innerHTML = '<div class="cs-empty"><i class="bi bi-search me-1"></i> No se encontraron resultados</div>';
            clienteDropdown.classList.remove('d-none');
            return;
        }
        var html = '';
        lista.forEach(function(c){
            var ini = c.nombre.trim().charAt(0).toUpperCase() || '?';
            html += '<div class="cs-item" data-id="'+c.id+'" data-nombre="'+escHtml(c.nombre)+'">'
                + '<span class="cs-avatar">'+escHtml(ini)+'</span>'
                + '<span class="cs-info"><span class="cs-name">'+highlight(c.nombre, q)+'</span><span class="cs-mail">'+highlight(c.email, q)+'</span></span>'
                + '</div>';
        });
        clienteDropdown.innerHTML = html;
        clienteDropdown.classList.remove('d-none');
        // bind click
        clienteDropdown.querySelectorAll('.cs-item').forEach(function(el){
            el.addEventListener('click', function(){
                var id = this.getAttribute('data-id');
                var nombre = this.getAttribute('data-nombre');
                clienteNombreInput.value = nombre;
                clienteIdInput.value = id;
                var found = clientesData.find(function(cc){ return String(cc.id) === String(id); });
                var contactoEl = document.getElementById('contacto_telefono');
                if(found && found.telefono && contactoEl && !contactoEl.value.trim()){
                    contactoEl.value = found.telefono;
                }
                cerrarDropdown();
                clienteNombreInput.focus();
            });
        });
    }
    function abrirDropdown(){ renderDropdown(); }
    function cerrarDropdown(){ if(clienteDropdown) clienteDropdown.classList.add('d-none'); }
    if(clienteNombreInput && clienteDropdown && clienteIdInput){
        clienteNombreInput.addEventListener('focus', abrirDropdown);
        clienteNombreInput.addEventListener('input', function(){
            // si escribe manual, resetea id hasta seleccionar
            var val = this.value.trim().toLowerCase();
            var exact = clientesData.find(function(c){ return c.nombre.toLowerCase() === val || c.email.toLowerCase() === val; });
            clienteIdInput.value = exact ? String(exact.id) : '0';
            var contactoEl2 = document.getElementById('contacto_telefono');
            if(exact && exact.telefono && contactoEl2 && !contactoEl2.value.trim()){
                contactoEl2.value = exact.telefono;
            }
            renderDropdown();
        });
        clienteNombreInput.addEventListener('keydown', function(e){
            if(e.key === 'Escape'){ cerrarDropdown(); this.blur(); }
            if(e.key === 'Enter'){
                var first = clienteDropdown.querySelector('.cs-item');
                if(first && !clienteDropdown.classList.contains('d-none')){
                    e.preventDefault();
                    first.click();
                }
            }
        });
        // cerrar al hacer click fuera
        document.addEventListener('click', function(e){
            var wrap = clienteNombreInput.closest('.cs-wrap');
            if(!wrap || !wrap.contains(e.target)) cerrarDropdown();
        });
        // cerrar al perder foco (con delay para permitir click en item)
        clienteNombreInput.addEventListener('blur', function(){
            setTimeout(function(){
                if(!clienteDropdown.matches(':hover')) cerrarDropdown();
            }, 150);
        });
    }

    // Validación previa: avisa qué falta antes de reservar (comprobante obligatorio excepto efectivo admin)
    const form = document.getElementById('formPago');
    const formAlert = document.getElementById('formAlert');
    const formAlertList = document.getElementById('formAlertList');

    form.addEventListener('submit', e => {
        const faltantes = [];
        if (ES_ADMIN && clienteNombreInput && !clienteNombreInput.value.trim()) faltantes.push('Ingresa el nombre del cliente.');
        var contactoInput = document.getElementById('contacto_telefono');
        if (!contactoInput || !contactoInput.value.trim()) faltantes.push('Ingresa un número de contacto (WhatsApp).');
        if (!selCancha.value) faltantes.push('Selecciona una cancha.');
        if (!inpFecha.value) faltantes.push('Elige la fecha del partido.');
        if (!selHora.value) faltantes.push('Elige la hora de inicio.');
        if (!selDur.value) faltantes.push('Elige la duración del partido.');
        var metodoAhora = document.getElementById('metodo_pago').value;
        var requiereComp = !ES_ADMIN;
        const f = input.files[0];
        if (requiereComp && !f) {
            faltantes.push('Sube tu comprobante de pago (JPG, PNG o PDF, máx. 5MB).');
            err.textContent = 'Falta tu comprobante de pago. Súbelo para continuar.';
            err.classList.remove('d-none');
            dz.style.borderColor = '#dc2626';
        } else if (f && !validar(f)) {
            faltantes.push('El comprobante no es válido. Usa JPG, PNG o PDF de máximo 5MB.');
        }

        if (faltantes.length > 0 || !form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
            formAlertList.innerHTML = faltantes.map(t => '<li>' + t + '</li>').join('');
            formAlert.classList.remove('d-none');
            formAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            formAlert.classList.add('d-none');
        }
        form.classList.add('was-validated');
    });

    // Oculta el aviso y errores al corregir
    [selCancha, inpFecha, selHora, selDur].forEach(el => el && el.addEventListener('change', () => {
        formAlert.classList.add('d-none');
    }));
    var contactoForAlert = document.getElementById('contacto_telefono');
    if(contactoForAlert) contactoForAlert.addEventListener('input', () => formAlert.classList.add('d-none'));
    input.addEventListener('change', () => {
        dz.style.borderColor = '';
    });
})();
</script>
