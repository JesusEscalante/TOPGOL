<?php
declare(strict_types=1);

/**
 * ====================================================================
 * TOP GOL - Textos de la página /eventos y reglas de eventos
 * ====================================================================
 * TODOS los textos de "Fútbol para grupos" y "Cumpleaños y reuniones" están aquí.
 * Para cambiar un texto, edita solo este archivo (no hace falta tocar las vistas).
 *
 * - Los precios del espacio salen de config/config.php (ESPACIO_PRECIO_HORA).
 * - Las imágenes se guardan en public/assets/img/eventos/ con los nombres de abajo.
 *   Si un archivo no existe, la página muestra un cuadro gris neutro.
 */
return [
    'futbol_grupos' => [
        'titulo'      => 'Fútbol para grupos',
        'subtitulo'   => 'Colegios y academias',
        'descripcion' => 'Para colegios y academias que reservan 3, 4 o más canchas para practicar el mismo día.',
        'puntos'      => [
            'Eliges la fecha, la hora y cuántas canchas necesitas.',
            'Todas las canchas del evento comparten el mismo horario.',
            'Subes tu comprobante y el encargado verifica el pago.',
        ],
        'adelanto'    => 'Adelanto de S/ 20 por cancha (por ejemplo, 3 canchas = S/ 60). El resto se paga en persona o por Yape, o puedes pagar el total.',
        'reglas_titulo' => 'Reglas para grupos',
        'reglas'      => [
            'Dejar la cancha limpia al terminar.',
            'Cumplir las normas del local.',
        ],
        'acepto'      => 'Acepto las reglas',
        'boton'       => 'Reservar canchas',
        'imagen'      => 'futbol-1.jpg',          // foto de las canchas
    ],

    'cumpleanos' => [
        'titulo'      => 'Cumpleaños y reuniones',
        'subtitulo'   => 'El espacio del medio del local',
        'descripcion' => 'Una zona del medio del local pensada para celebrar con calma.',
        'puntos'      => [
            'Zona con espacio para muchas mesas.',
            'Para cumpleaños, reuniones o parrillas con calma.',
            'TV grande para poner música y videos.',
            'Si quieres, puedes agregar una o más canchas para el mismo horario.',
        ],
        // {precio} se reemplaza con el valor de ESPACIO_PRECIO_HORA (config/config.php)
        'precio'      => 'El espacio cuesta S/ {precio} por hora (precio provisional, por confirmar con el encargado). Cada cancha que agregues suma S/ 60 por hora.',
        'adelanto'    => 'El adelanto y el precio final los confirma el encargado después de tu solicitud.',
        'regla_alcohol' => 'Las bebidas alcohólicas se compran dentro del local. No se permite traerlas de afuera.',
        'boton'       => 'Solicitar',
        // Fotos reales (public/assets/img/eventos/): mesas, parrillas o ambiente, TV grande
        'imagenes'    => ['cumple-1.jpg', 'cumple-2.jpg', 'cumple-3.jpg'],
        'imagenes_alt' => ['Zona de mesas del espacio de cumpleaños y reuniones', 'Parrillas y ambiente del espacio de cumpleaños y reuniones', 'TV grande del espacio de cumpleaños y reuniones'],
        'fotos_pendientes' => 'Fotos próximamente',
        'promociones' => [
            'titulo'     => 'Promociones',
            // Mientras sea true se muestra el aviso "texto de ejemplo". Cámbialo a false al poner promociones reales.
            'es_ejemplo' => true,
            'items'      => [
                'Texto de ejemplo: aquí irá la promoción vigente (por ejemplo, un descuento o un combo). Edítalo en config/eventos_textos.php.',
            ],
        ],
    ],
];
