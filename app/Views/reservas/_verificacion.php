<?php
declare(strict_types=1);

/**
 * Aviso de espera de verificación del pago (solo texto, no es automático ni garantizado).
 * El plazo sale de config/config.php (VERIFICACION_PAGO_PLAZO). No confundir con la tolerancia de la hora de juego.
 */
?>
<div class="aviso-verificacion" role="note" style="display:flex; gap:10px; align-items:flex-start; background:#eff6ff; border:1px solid #bfdbfe; border-left:4px solid #2563eb; border-radius:10px; padding:10px 12px; margin:12px 0; color:#1e3a8a; font-size:.88rem; line-height:1.4;">
    <i class="bi bi-hourglass-split" style="font-size:1.1rem; color:#2563eb; margin-top:1px;"></i>
    <span><?= htmlspecialchars(textoVerificacionPago()) ?></span>
</div>
