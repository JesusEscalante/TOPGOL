<?php
declare(strict_types=1);

/**
 * Aviso de tolerancia para el cliente (reservas de cancha y eventos de fútbol).
 * El valor sale de config/config.php (TOLERANCIA_MINUTOS). No se usa en cumpleaños y reuniones.
 * Es solo texto: no cancela ni marca inasistencia.
 */
?>
<div class="aviso-tolerancia" role="note" style="display:flex; gap:10px; align-items:flex-start; background:#fff8e1; border:1px solid #fde68a; border-left:4px solid #f59e0b; border-radius:10px; padding:10px 12px; margin:12px 0; color:#78350f; font-size:.88rem; line-height:1.4;">
    <i class="bi bi-clock-history" style="font-size:1.1rem; color:#d97706; margin-top:1px;"></i>
    <span><?= htmlspecialchars(textoToleranciaReserva()) ?></span>
</div>
