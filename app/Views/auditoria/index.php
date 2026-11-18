<?php
declare(strict_types=1);

$modulosOpciones = [
    'reservas'   => 'Reservas y Eventos',
    'pagos'      => 'Pagos y Caja',
    'canchas'    => 'Canchas',
    'productos'  => 'Productos',
    'inventario' => 'Inventario / Kardex',
    'categorias' => 'Categorías',
    'ventas'     => 'Ventas POS',
    'espacios'   => 'Cumpleaños y Espacios',
    'auth'       => 'Seguridad / Acceso',
];

$accionesOpciones = [
    'crear'               => 'Crear registro',
    'crear_evento'        => 'Crear evento grupal',
    'actualizar'          => 'Actualizar datos',
    'eliminar'            => 'Eliminar registro',
    'cambiar_estado'      => 'Cambiar estado',
    'verificar_pago'      => 'Verificar comprobante',
    'rechazar_pago'       => 'Rechazar comprobante',
    'cobro_saldo'         => 'Cobro de saldo',
    'cambiar_estado_pago' => 'Cambiar estado de pago',
    'cancelar'            => 'Cancelar reserva',
    'reprogramar'         => 'Reprogramar horario',
    'asistencia'          => 'Registrar asistencia',
    'ajustar_stock'       => 'Ajuste manual de stock',
    'descontinuar'        => 'Descontinuar producto',
    'venta_pos'           => 'Venta en mostrador',
    'login'               => 'Inicio de sesión admin',
];

$queryExport = http_build_query(array_filter($filtros ?? [], static fn($v) => $v !== '' && $v !== 0));
$urlExportar = url('/admin/auditoria/exportar') . ($queryExport !== '' ? '?' . $queryExport : '');
?>
<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_top.php'; ?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="adm-h1"><i class="bi bi-shield-check text-success me-2"></i>Bitácora de Auditoría</h1>
        <p class="adm-sub">Historial inmutable de operaciones administrativas, seguridad y trazabilidad del sistema.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= htmlspecialchars($urlExportar) ?>" class="btn btn-sm btn-success fw-bold" style="border-radius:9px; padding:8px 16px;">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Exportar CSV
        </a>
    </div>
</div>

<!-- Indicadores rápidos -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="kpi">
            <div class="kpi-ico g"><i class="bi bi-journal-check"></i></div>
            <div>
                <small class="lbl">Total Registrado</small>
                <div class="val"><?= (int)($estadisticas['total'] ?? 0) ?></div>
            </div>
            <div class="foot">Eventos</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="kpi">
            <div class="kpi-ico b"><i class="bi bi-clock-history"></i></div>
            <div>
                <small class="lbl">Operaciones Hoy</small>
                <div class="val"><?= (int)($estadisticas['hoy'] ?? 0) ?></div>
            </div>
            <div class="foot up">Hoy</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="kpi">
            <div class="kpi-ico y"><i class="bi bi-calendar-range"></i></div>
            <div>
                <small class="lbl">Últimos 7 Días</small>
                <div class="val"><?= (int)($estadisticas['semana'] ?? 0) ?></div>
            </div>
            <div class="foot">Semana</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="kpi">
            <div class="kpi-ico g" style="background:#0f766e;"><i class="bi bi-layers"></i></div>
            <div>
                <small class="lbl">Módulos Auditados</small>
                <div class="val"><?= (int)($estadisticas['modulos'] ?? 0) ?></div>
            </div>
            <div class="foot">Activos</div>
        </div>
    </div>
</div>

<!-- Barra de Filtros -->
<div class="panel-2 mb-4">
    <form method="GET" action="<?= url('/admin/auditoria') ?>" class="row g-2 align-items-end">
        <div class="col-md-2">
            <label class="form-label small fw-bold text-secondary mb-1">Módulo</label>
            <select name="modulo" class="form-select form-select-sm">
                <option value="">Todos los módulos</option>
                <?php foreach ($modulosOpciones as $k => $lbl): ?>
                    <option value="<?= htmlspecialchars($k) ?>" <?= ($filtros['modulo'] ?? '') === $k ? 'selected' : '' ?>>
                        <?= htmlspecialchars($lbl) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-bold text-secondary mb-1">Acción</label>
            <select name="accion" class="form-select form-select-sm">
                <option value="">Todas las acciones</option>
                <?php foreach ($accionesOpciones as $k => $lbl): ?>
                    <option value="<?= htmlspecialchars($k) ?>" <?= ($filtros['accion'] ?? '') === $k ? 'selected' : '' ?>>
                        <?= htmlspecialchars($lbl) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-bold text-secondary mb-1">Administrador</label>
            <select name="usuario_id" class="form-select form-select-sm">
                <option value="0">Todos</option>
                <?php foreach (($usuariosActivos ?? []) as $ua): ?>
                    <option value="<?= (int)$ua['usuario_id'] ?>" <?= (int)($filtros['usuario_id'] ?? 0) === (int)$ua['usuario_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)$ua['usuario_nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-bold text-secondary mb-1">Desde</label>
            <input type="date" name="desde" class="form-control form-control-sm" value="<?= htmlspecialchars((string)($filtros['desde'] ?? '')) ?>">
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-bold text-secondary mb-1">Hasta</label>
            <input type="date" name="hasta" class="form-control form-control-sm" value="<?= htmlspecialchars((string)($filtros['hasta'] ?? '')) ?>">
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-bold text-secondary mb-1">Buscar</label>
            <input type="text" name="q" class="form-control form-control-sm" placeholder="Detalle, ID o IP..." value="<?= htmlspecialchars((string)($filtros['q'] ?? '')) ?>">
        </div>

        <div class="col-12 d-flex justify-content-end gap-2 mt-2">
            <a href="<?= url('/admin/auditoria') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-x-circle me-1"></i> Limpiar
            </a>
            <button type="submit" class="btn btn-sm btn-success fw-bold px-3">
                <i class="bi bi-funnel-fill me-1"></i> Filtrar Bitácora
            </button>
        </div>
    </form>
</div>

<!-- Tabla de Registros -->
<div class="panel-2">
    <div class="table-responsive">
        <table class="tbl">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha y Hora</th>
                    <th>Administrador</th>
                    <th>Módulo</th>
                    <th>Acción</th>
                    <th>Entidad</th>
                    <th>Descripción de la Operación</th>
                    <th>IP</th>
                    <th class="text-end">Contexto</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-shield-lock fs-2 d-block mb-2 opacity-50"></i>
                            No se encontraron registros de auditoría con los filtros seleccionados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                        <?php
                        $accion = (string)$r['accion'];
                        $pillCls = match (true) {
                            in_array($accion, ['eliminar', 'rechazar_pago', 'cancelar', 'descontinuar'], true) => 'bad',
                            in_array($accion, ['crear', 'crear_evento', 'verificar_pago', 'cobro_saldo', 'venta_pos'], true) => 'ok',
                            in_array($accion, ['reprogramar', 'ajustar_stock', 'cambiar_estado', 'cambiar_estado_pago'], true) => 'warn',
                            default => 'info',
                        };
                        ?>
                        <tr>
                            <td class="fw-bold text-muted">#<?= (int)$r['id'] ?></td>
                            <td class="fh">
                                <strong><?= date('d/m/Y', strtotime((string)$r['created_at'])) ?></strong>
                                <small><?= date('H:i:s', strtotime((string)$r['created_at'])) ?></small>
                            </td>
                            <td>
                                <strong class="d-block text-dark"><?= htmlspecialchars((string)$r['usuario_nombre']) ?></strong>
                                <?php if (!empty($r['usuario_email'])): ?>
                                    <small class="text-muted"><?= htmlspecialchars((string)$r['usuario_email']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="pill fin text-uppercase"><?= htmlspecialchars((string)$r['modulo']) ?></span>
                            </td>
                            <td>
                                <span class="pill <?= $pillCls ?>">
                                    <?= htmlspecialchars($accionesOpciones[$accion] ?? ucfirst(str_replace('_', ' ', $accion))) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($r['entidad'])): ?>
                                    <span class="text-secondary small fw-semibold">
                                        <?= htmlspecialchars((string)$r['entidad']) ?>
                                        <?= !empty($r['entidad_id']) ? ' #' . htmlspecialchars((string)$r['entidad_id']) : '' ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="max-width:340px;">
                                <?= htmlspecialchars((string)$r['descripcion']) ?>
                            </td>
                            <td>
                                <code class="small text-secondary"><?= htmlspecialchars((string)($r['ip_address'] ?: '127.0.0.1')) ?></code>
                            </td>
                            <td class="text-end">
                                <?php if (!empty($r['datos_json'])): ?>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-secondary py-0 px-2"
                                            onclick='verDetalleAuditoria(<?= (int)$r["id"] ?>, <?= json_encode((string)$r["descripcion"], JSON_HEX_APOS | JSON_HEX_QUOT) ?>, <?= json_encode((string)$r["datos_json"], JSON_HEX_APOS | JSON_HEX_QUOT) ?>, <?= json_encode((string)($r["user_agent"] ?? ""), JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                            title="Ver datos JSON y navegador">
                                        <i class="bi bi-code-square"></i> Detalle
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function verDetalleAuditoria(id, descripcion, datosJsonRaw, userAgent) {
    var pretty = datosJsonRaw;
    try {
        pretty = JSON.stringify(JSON.parse(datosJsonRaw), null, 2);
    } catch (e) {}

    var div = document.createElement('div');
    div.textContent = pretty;
    var safeJson = div.innerHTML;

    var divUa = document.createElement('div');
    divUa.textContent = userAgent || 'No disponible';
    var safeUa = divUa.innerHTML;

    var divDesc = document.createElement('div');
    divDesc.textContent = descripcion;
    var safeDesc = divDesc.innerHTML;

    Swal.fire({
        title: 'Registro de Auditoría #' + id,
        html: '<div class="text-start small">'
            + '<p class="mb-2"><strong>Operación:</strong> ' + safeDesc + '</p>'
            + '<label class="fw-bold text-secondary mb-1">Datos registrados (JSON):</label>'
            + '<pre class="bg-light p-2 rounded border text-dark mb-2" style="max-height:220px;overflow:auto;font-size:12px;">' + safeJson + '</pre>'
            + '<div class="text-muted" style="font-size:11px;"><strong>Navegador / Agente:</strong> ' + safeUa + '</div>'
            + '</div>',
        icon: 'info',
        confirmButtonColor: '#1a7a3a',
        confirmButtonText: 'Cerrar'
    });
}
</script>

<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_bottom.php'; ?>
