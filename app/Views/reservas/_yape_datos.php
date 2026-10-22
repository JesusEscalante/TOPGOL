<?php
declare(strict_types=1);

/**
 * Datos de Yape (QR, número y titular) leídos de config/yape.php.
 * Se incluye dentro de <div class="row g-3"> del panel Yape.
 * Opcional: $yapeImportanteHtml = bloque "Importante" propio de cada pantalla.
 */
$yape = yapeConfig();
$hayQr = $yape['qr_url'] !== '';
$hayDatos = $yape['numero'] !== '' || $yape['titular'] !== '';
?>
<?php if ($hayQr): ?>
    <div class="col-md-5 text-center">
        <div class="qr-title">Escanea el código QR con Yape</div>
        <div class="qr-wrap">
            <img src="<?= htmlspecialchars($yape['qr_url']) ?>" alt="QR Yape Top Gol" width="180" height="180">
        </div>
    </div>
<?php endif; ?>
<div class="<?= $hayQr ? 'col-md-7' : 'col-12' ?>">
    <?php if ($hayDatos): ?>
        <div class="pago-numero-box">
            <?php if ($yape['numero'] !== ''): ?>
                <div class="fw-semibold small mb-2">También puedes yapear al número:</div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="tel-ico"><i class="bi bi-telephone-fill"></i></span>
                    <strong class="tel-num"><?= htmlspecialchars($yape['numero']) ?></strong>
                    <button type="button" class="btn-copiar" onclick="copiarNumero('<?= htmlspecialchars(preg_replace('/\D+/', '', $yape['numero'])) ?>', this)">
                        <i class="bi bi-copy"></i> Copiar
                    </button>
                </div>
            <?php endif; ?>
            <?php if ($yape['titular'] !== ''): ?>
                <div class="small mt-2">Titular: <strong><?= htmlspecialchars($yape['titular']) ?></strong></div>
            <?php endif; ?>
        </div>
    <?php elseif (!$hayQr): ?>
        <div class="alert alert-warning py-2 mb-3" role="alert" style="font-size:.9rem;">
            <i class="bi bi-gear me-1"></i> <strong>Datos de Yape pendientes de configurar.</strong>
            Mientras tanto puedes subir tu comprobante más abajo para probar el flujo.
        </div>
    <?php endif; ?>
    <?= $yapeImportanteHtml ?? '' ?>
</div>
