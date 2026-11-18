<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Auditoria;

/**
 * ====================================================================
 * TOP GOL - Controlador de Auditoría y Bitácora Administrativa (RF32)
 * ====================================================================
 * Permite consultar, filtrar y exportar a CSV el historial inmutable
 * de operaciones sensibles ejecutadas por los administradores.
 */
class AuditoriaController extends Controller
{
    /**
     * Vista principal de la bitácora de auditoría con filtros y KPIs (Solo Admin)
     */
    public function index(): void
    {
        $this->requireAdmin();

        $filtros = [
            'modulo'     => trim((string)($_GET['modulo'] ?? '')),
            'accion'     => trim((string)($_GET['accion'] ?? '')),
            'usuario_id' => (int)($_GET['usuario_id'] ?? 0),
            'desde'      => trim((string)($_GET['desde'] ?? '')),
            'hasta'      => trim((string)($_GET['hasta'] ?? '')),
            'q'          => trim((string)($_GET['q'] ?? '')),
        ];

        $modelo = new Auditoria();
        $registros = $modelo->obtenerListado($filtros, 300);
        $estadisticas = $modelo->obtenerEstadisticas();
        $usuariosActivos = $modelo->obtenerUsuariosConActividad();

        $this->view('auditoria/index', [
            'titulo'          => 'Bitácora de Auditoría - Panel Administrador',
            'adminMenu'       => 'auditoria',
            'registros'       => $registros,
            'estadisticas'    => $estadisticas,
            'usuariosActivos' => $usuariosActivos,
            'filtros'         => $filtros,
        ], false);
    }

    /**
     * Exporta los registros filtrados de la bitácora a un archivo CSV compatible con Excel (Solo Admin)
     */
    public function exportar(): void
    {
        $this->requireAdmin();

        $filtros = [
            'modulo'     => trim((string)($_GET['modulo'] ?? '')),
            'accion'     => trim((string)($_GET['accion'] ?? '')),
            'usuario_id' => (int)($_GET['usuario_id'] ?? 0),
            'desde'      => trim((string)($_GET['desde'] ?? '')),
            'hasta'      => trim((string)($_GET['hasta'] ?? '')),
            'q'          => trim((string)($_GET['q'] ?? '')),
        ];

        $modelo = new Auditoria();
        $registros = $modelo->obtenerListado($filtros, 1000);

        $filename = 'auditoria_topgol_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }

        // BOM UTF-8 para correcta lectura de tildes en Microsoft Excel
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, [
            'ID',
            'Fecha y Hora',
            'Usuario ID',
            'Administrador',
            'Correo',
            'Módulo',
            'Acción',
            'Entidad',
            'Entidad ID',
            'Descripción',
            'IP',
            'Datos Adicionales (JSON)',
        ]);

        foreach ($registros as $r) {
            fputcsv($out, [
                $r['id'],
                $r['created_at'],
                $r['usuario_id'] ?? '',
                $r['usuario_nombre'],
                $r['usuario_email'] ?? '',
                strtoupper((string)$r['modulo']),
                $r['accion'],
                $r['entidad'] ?? '',
                $r['entidad_id'] ?? '',
                $r['descripcion'],
                $r['ip_address'] ?? '',
                $r['datos_json'] ?? '',
            ]);
        }

        fclose($out);
        exit;
    }
}
