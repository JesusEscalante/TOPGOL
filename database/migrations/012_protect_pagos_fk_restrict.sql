-- ====================================================================
-- MIGRACIÓN 012: Blindaje de Historial Financiero (ON DELETE RESTRICT)
-- ====================================================================
-- Objetivo: Proteger la auditoría contable impidiendo que reservas con
-- pagos registrados puedan ser eliminadas físicamente de la base de datos.
-- Si se intenta un borrado físico, MySQL rechazará la operación (Error 1451).
-- Las cancelaciones operativas del sistema continúan funcionando mediante
-- borrado lógico (UPDATE reservas SET estado = 'cancelada').
-- ====================================================================

USE `topgol_db`;

-- 1. Eliminar la restricción previa con borrado en cascada
ALTER TABLE `pagos`
    DROP FOREIGN KEY `fk_pagos_reserva`;

-- 2. Recrear la restricción con ON DELETE RESTRICT
ALTER TABLE `pagos`
    ADD CONSTRAINT `fk_pagos_reserva`
    FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`id`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT;
