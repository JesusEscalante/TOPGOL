<?php
declare(strict_types=1);

/**
 * ====================================================================
 * TOP GOL - Datos de pago con Yape (solo lo que se muestra al cliente)
 * ====================================================================
 * Completa estos tres datos con los REALES del negocio. Si están vacíos, las
 * pantallas de pago muestran "Datos de Yape pendientes de configurar" (el campo
 * para subir el comprobante sigue visible).
 *
 * - numero : número de Yape, por ejemplo '999 999 999'
 * - titular: nombre del titular, por ejemplo 'Nombre del titular'
 * - qr     : ruta de la imagen del QR dentro de public/, por ejemplo
 *            'assets/img/pagos/yape-qr.png' (el archivo debe existir)
 */
return [
    'numero'  => '',
    'titular' => '',
    'qr'      => '',
];
