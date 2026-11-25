<?php

namespace App\Controllers;

use App\Core\Route;
use App\Models\BitacoraModel;
use App\Services\Reportes\ReporteEstadistico;
use Throwable;

$logger = new BitacoraModel();
$model  = new ReporteEstadistico();

/**
 * Extrae y sanea los parámetros de filtro enviados desde la petición (GET o POST).
 * Soporta tanto parámetros planos (?periodo=hoy&id_metodo=1) como agrupados (?filtros[periodo]=hoy).
 *
 * @return array Filtros limpios listos para pasar al modelo ReporteEstadistico
 */
function extraerFiltrosRequest(): array
{
    $origen = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;

    // Si el frontend envía los filtros dentro de un arreglo 'filtros', los combinamos
    if (isset($origen['filtros']) && is_array($origen['filtros'])) {
        $origen = array_merge($origen, $origen['filtros']);
    }

    // Lista blanca de claves de filtro permitidas en todos los módulos
    $clavesPermitidas = [
        'periodo',
        'fecha_inicio',
        'fecha_fin',
        'id_metodo',
        'estado',
        'concepto',
        'id_tipo',
        'id_estado',
        'activo',
        'rango_edad',
        'con_rutina',
        'tipo',
        'franja_horaria',
        'id_categoria',
        'estado_stock',
        'solo_activos',
        'cedula_trabajador',
        'tipo_mantenimiento',
        'id_dificultad',
        'id_rol'
    ];

    $filtrosLimpios = [];

    foreach ($clavesPermitidas as $clave) {
        if (array_key_exists($clave, $origen)) {
            $valor = $origen[$clave];
            if (is_string($valor)) {
                $filtrosLimpios[$clave] = strip_tags(trim($valor));
            } elseif (is_numeric($valor)) {
                $filtrosLimpios[$clave] = (string)$valor;
            }
        }
    }

    return $filtrosLimpios;
}

switch (Route::action()) {
    /**
     * Muestra la vista principal del módulo de Reportes Estadísticos.
     * Carga la definición de todos los reportes con sus filtros predeterminados
     * y ejecuta un reporte inicial para que la pantalla abra con datos cargados.
     */
    case "index":
    default:
        // Ajusta el slug del permiso si en tu BD de seguridad tiene otro nombre (ej. "estadisticas:ver")
        Route::protect("reportes:ver");

        try {
            // 1. Obtener el catálogo de reportes y las opciones reales de cada filtro
            $definicionReportes = $model->obtenerDefinicionReportes();

            // 2. Determinar qué reporte cargar inicialmente (por defecto 'resumen_general')
            $moduloActual = isset($_GET['modulo']) && array_key_exists($_GET['modulo'], $definicionReportes)
                ? $_GET['modulo']
                : 'resumen_general';

            $filtrosIniciales = extraerFiltrosRequest();

            // 3. Generar la data inicial (KPIs, series para gráficos, columnas y registros)
            $reporteInicial = $model->generarReporte($moduloActual, $filtrosIniciales);

            // 4. Mensajes temporales de sesión (Toasts/Alertas) igual que en productos.php
            $mensaje     = $_SESSION['mensaje'] ?? '';
            $tipoMensaje = $_SESSION['tipo_mensaje'] ?? '';
            unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);

            // 5. Renderizar la vista pasando toda la configuración inicial
            // Nota: Si tu vista tiene otra ruta (ej. 'reporteEstadistico'), cámbiala aquí
            echo Route::render('reportes/estadisticos', [
                'definicionReportes' => $definicionReportes,
                'moduloActual'       => $moduloActual,
                'reporteInicial'     => $reporteInicial,
                'mensaje'            => $mensaje,
                'tipoMensaje'        => $tipoMensaje
            ]);
        } catch (Throwable $e) {
            error_log("Error en reporteEstadistico::index -> " . $e->getMessage());
            http_response_code(500);
            echo "Ocurrió un error al cargar el módulo de reportes estadísticos.";
        }
        exit;

    /**
     * Endpoint API AJAX: Genera y devuelve en JSON cualquier reporte estadístico
     * aplicando el módulo y los filtros predeterminados seleccionados por el usuario.
     */
    case "consultar":
        Route::protect("reportes:ver");

        header('Content-Type: application/json; charset=utf-8');

        try {
            $origen = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
            $modulo = isset($origen['modulo']) ? strip_tags(trim($origen['modulo'])) : 'resumen_general';

            if (empty($modulo)) {
                http_response_code(400);
                echo json_encode([
                    'exito'   => false,
                    'mensaje' => 'Debe especificar el módulo de reporte a consultar.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $filtros   = extraerFiltrosRequest();
            $resultado = $model->generarReporte($modulo, $filtros);

            if (!$resultado['exito']) {
                http_response_code(400);
            }

            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log("Error en reporteEstadistico::consultar -> " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'exito'   => false,
                'mensaje' => 'Error interno al procesar el reporte estadístico.'
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;

    /**
     * Endpoint API AJAX: Devuelve únicamente la estructura de reportes y sus filtros
     * predeterminados (útil si el JS necesita refrescar los selectores dinámicamente).
     */
    case "definicion":
        Route::protect("reportes:ver");

        header('Content-Type: application/json; charset=utf-8');

        try {
            $definicion = $model->obtenerDefinicionReportes();
            echo json_encode([
                'exito'    => true,
                'reportes' => $definicion
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log("Error en reporteEstadistico::definicion -> " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'exito'   => false,
                'mensaje' => 'No se pudieron cargar las definiciones de filtros.'
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;

    /**
     * Endpoint de Exportación CSV / Excel: Descarga el reporte actualmente filtrado
     * en formato CSV compatible con Excel (con BOM UTF-8 para tildes y ñ) y lo registra en bitácora.
     */
    case "exportarCsv":
        Route::protect("reportes:ver");

        try {
            $modulo    = isset($_GET['modulo']) ? strip_tags(trim($_GET['modulo'])) : 'resumen_general';
            $filtros   = extraerFiltrosRequest();
            $resultado = $model->generarReporte($modulo, $filtros);

            if (!$resultado['exito']) {
                $_SESSION['mensaje']     = $resultado['mensaje'] ?? 'No se pudo exportar el reporte.';
                $_SESSION['tipo_mensaje'] = 'error';
                header('Location: ?action=index');
                exit;
            }

            // Registrar la exportación en la bitácora del sistema
            $logger->log("Exportación CSV del reporte estadístico '{modulo}'", [
                "modulo"  => "reportes",
                "accion"  => "exportar_csv",
                "reporte" => $modulo
            ]);

            $nombreArchivo = 'reporte_' . $modulo . '_' . date('Ymd_His') . '.csv';

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $salida = fopen('php://output', 'w');

            // BOM UTF-8 para que Microsoft Excel reconozca acentos correctamente
            fwrite($salida, "\xEF\xBB\xBF");

            // Título y fecha de generación
            fputcsv($salida, [$resultado['titulo'], 'Generado el: ' . date('d/m/Y H:i')], ';');
            fputcsv($salida, [], ';');

            // Encabezados de columnas
            if (!empty($resultado['columnas'])) {
                fputcsv($salida, $resultado['columnas'], ';');
            }

            // Filas de datos
            foreach ($resultado['registros'] as $fila) {
                // Evitar exportar columnas auxiliares internas como 'hora_num' en asistencias
                if ($modulo === 'asistencias' && isset($fila['hora_num'])) {
                    unset($fila['hora_num']);
                }
                fputcsv($salida, array_values($fila), ';');
            }

            fclose($salida);
        } catch (Throwable $e) {
            error_log("Error en reporteEstadistico::exportarCsv -> " . $e->getMessage());
            http_response_code(500);
            echo "Error al exportar el archivo CSV.";
        }
        exit;
}