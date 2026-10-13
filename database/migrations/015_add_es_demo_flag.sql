-- ====================================================================
-- TOP GOL - MIGRACIÓN 015: AISLAMIENTO AUDITABLE DE DATOS DE DEMOSTRACIÓN (ES_DEMO)
-- ====================================================================
-- Agrega:
-- 1. Columna `es_demo` (TINYINT(1) DEFAULT 0) en la tabla `ventas` para marcar
--    transacciones de prueba o calibración sin eliminar historial ni romper
--    relaciones de venta_detalles o kardex de inventario.
-- 2. Columna `es_demo` en `pagos` y `reservas` para futuras pruebas sin contaminar
--    la contabilidad comercial.
-- 3. Marcado auditable de las dos ventas de calibración conocidas (VTA-20261012-0001 y VTA-20261012-0002).
-- ====================================================================

-- 1. Agregar columna es_demo en `ventas`
ALTER TABLE `ventas`
    ADD COLUMN `es_demo` TINYINT(1) NOT NULL DEFAULT 0 AFTER `estado`,
    ADD KEY `idx_ventas_es_demo` (`es_demo`);

-- 2. Agregar columna es_demo en `pagos`
ALTER TABLE `pagos`
    ADD COLUMN `es_demo` TINYINT(1) NOT NULL DEFAULT 0 AFTER `estado`,
    ADD KEY `idx_pagos_es_demo` (`es_demo`);

-- 3. Agregar columna es_demo en `reservas`
ALTER TABLE `reservas`
    ADD COLUMN `es_demo` TINYINT(1) NOT NULL DEFAULT 0 AFTER `estado`,
    ADD KEY `idx_reservas_es_demo` (`es_demo`);

-- 4. Marcar de forma segura las dos ventas de prueba realizadas durante la fase de verificación
UPDATE `ventas`
SET `es_demo` = 1
WHERE `codigo_operacion` IN ('VTA-20261012-0001', 'VTA-20261012-0002');
