<?php
declare(strict_types=1);

/** @var array<string, mixed> $venta */
?>
<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_top.php'; ?>

<style>
    @media print {
        .no-print, .adm-topbar, .adm-side, .breadcrumb, footer {
            display: none !important;
        }
        .adm-main {
            margin: 0 !important;
            padding: 0 !important;
        }
        .voucher-box {
            box-shadow: none !important;
            border: 1px dashed #94a3b8 !important;
            margin: 0 auto !important;
            max-width: 100% !important;
        }
    }
    .voucher-box {
        max-width: 680px;
        margin: 0 auto;
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 16px rgba(0,0,0,0.05);
        padding: 32px;
    }
</style>

<div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-4 no-print">
    <div>
        <a href="<?= url('/admin/ventas') ?>" class="text-decoration-none text-muted small fw-bold">
            <i class="bi bi-arrow-left me-1"></i> Volver al historial de ventas
        </a>
        <h1 class="adm-h1 mt-1">Comprobante de Venta</h1>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-sm btn-outline-dark fw-bold" style="border-radius: 9px; padding: 8px 16px;">
            <i class="bi bi-printer me-1"></i> Imprimir Comprobante
        </button>
        <a href="<?= url('/admin/ventas/pos') ?>" class="btn btn-sm fw-bold" style="background:#15803d; color:#fff; border-radius: 9px; padding: 8px 16px;">
            <i class="bi bi-plus-lg me-1"></i> Nueva Venta
        </a>
    </div>
</div>

<div class="voucher-box">
    <!-- Encabezado del comprobante interno -->
    <div class="text-center pb-3 border-bottom mb-4">
        <div class="d-inline-flex align-items-center justify-content-center bg-dark text-white rounded-3 px-3 py-2 mb-2">
            <i class="bi bi-trophy-fill text-warning me-2 fs-5"></i>
            <span class="fw-bold tracking-wide fs-6">TOP GOL</span>
        </div>
        <h2 class="h5 fw-bold mb-1 text-dark">Comprobante Interno de Venta</h2>
        <p class="text-muted small mb-1">Control Administrativo de Bar, Kiosco y Cafetería</p>
        <div class="badge bg-light text-dark border px-3 py-1 font-monospace fs-6 mt-1">
            <?= htmlspecialchars((string)$venta['codigo_operacion'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php if (($venta['estado'] ?? '') === 'anulada'): ?>
            <div class="alert alert-danger fw-bold py-2 mt-2 mb-0" style="font-size: .85rem;">
                <i class="bi bi-x-octagon-fill me-1"></i> OPERACIÓN ANULADA — Comprobante sin valor comercial
            </div>
        <?php endif; ?>
    </div>

    <!-- Metadatos de la venta -->
    <div class="row g-3 mb-4 small text-secondary">
        <div class="col-6">
            <div><strong class="text-dark">Fecha:</strong> <?= htmlspecialchars(date('d/m/Y H:i:s', strtotime((string)$venta['created_at'])), ENT_QUOTES, 'UTF-8') ?></div>
            <div><strong class="text-dark">Cliente:</strong> <?= htmlspecialchars((string)($venta['cliente_nombre'] ?? 'Cliente Mostrador'), ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <div class="col-6 text-end">
            <div>
                <strong class="text-dark">Atendido por:</strong> <?= htmlspecialchars((string)($venta['admin_nombre'] ?? $venta['registrado_por_nombre'] ?? 'Administrador'), ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div>
                <strong class="text-dark">Método de pago:</strong>
                <?php
                $metodo = (string)$venta['metodo_pago'];
                $metodoNombre = match ($metodo) {
                    'efectivo' => 'Efectivo',
                    'niubiz'   => 'Niubiz POS',
                    default    => ucfirst($metodo),
                };
                ?>
                <span class="badge bg-dark text-white ms-1"><?= $metodoNombre ?></span>
            </div>
            <?php if (!empty($venta['idempotency_token'])): ?>
                <div class="mt-1" style="font-size: .7rem;">
                    <span class="text-muted font-monospace">OP-ID: <?= htmlspecialchars(substr((string)$venta['idempotency_token'], 0, 12), ENT_QUOTES, 'UTF-8') ?>...</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabla de ítems vendidos -->
    <div class="table-responsive mb-3">
        <table class="table align-middle" style="font-size: .85rem;">
            <thead class="table-light">
                <tr style="text-transform: uppercase; font-size: .72rem; letter-spacing: .5px;">
                    <th>Producto</th>
                    <th class="text-center">Cant.</th>
                    <th class="text-end">P. Unit.</th>
                    <th class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($venta['detalles'] ?? []) as $item): ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars((string)$item['producto_nombre'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php if (!empty($item['categoria_nombre'])): ?>
                                <small class="text-muted"><?= htmlspecialchars((string)$item['categoria_nombre'], ENT_QUOTES, 'UTF-8') ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center fw-semibold">
                            <?= (int)$item['cantidad'] ?>
                        </td>
                        <td class="text-end font-monospace">
                            S/ <?= number_format((float)$item['precio_unitario'], 2) ?>
                        </td>
                        <td class="text-end font-monospace fw-bold text-dark">
                            S/ <?= number_format((float)$item['subtotal'], 2) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="border-top-2 border-dark">
                    <td colspan="3" class="text-end fw-bold fs-6 pt-3">TOTAL COBRADO:</td>
                    <td class="text-end font-monospace fw-bold fs-5 text-success pt-3">
                        S/ <?= number_format((float)$venta['total'], 2) ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php if (!empty($venta['notas'])): ?>
        <div class="p-3 bg-light rounded-3 small mb-3 border">
            <strong class="text-dark d-block mb-1">Notas de la operación:</strong>
            <span class="text-muted"><?= nl2br(htmlspecialchars((string)$venta['notas'], ENT_QUOTES, 'UTF-8')) ?></span>
        </div>
    <?php endif; ?>

    <div class="text-center text-muted small pt-3 border-top" style="font-size: .75rem;">
        <div>Este documento es un comprobante de control interno emitido por el sistema Top Gol.</div>
        <div>No válido como comprobante fiscal o tributario ante SUNAT.</div>
    </div>
</div>

<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_bottom.php'; ?>
