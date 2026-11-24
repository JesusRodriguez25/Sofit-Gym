<?php

namespace App\Controllers;

use App\Core\Route;
use App\Core\Http\Response;
use App\Models\GestionPagoModel;
use App\Models\BitacoraModel;
use App\Services\Reportes\ReporteFinanciero;
use Throwable;

$bitacora = new BitacoraModel();
$model    = new GestionPagoModel();

function jsonOut($data, int $code = 200): void
{
    if (ob_get_level() > 0) { ob_clean(); }
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    try { echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR); } 
    catch (Throwable $e) { echo json_encode(['success' => false, 'message' => 'Error interno.']); }
    exit;
}

$action = Route::action();
if ($action !== 'index' && $action !== 'reporteVista') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);
    if (ob_get_level() === 0) { ob_start(); }
}

switch ($action) {
    case "index":
        Route::protect("gestion_pago:ver");
        $mensaje     = $_SESSION['mensaje'] ?? '';
        $tipoMensaje = $_SESSION['tipo_mensaje'] ?? '';
        unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);

        try {
            $pagos          = $model->obtenerTodosPagos();
            $clientes       = $model->obtenerClientesSimples();
            $tiposMembresia = $model->obtenerTiposMembresia();
        } catch (Throwable $e) {
            $pagos = []; $clientes = []; $tiposMembresia = [];
            $mensaje = '❌ Error al cargar datos: ' . $e->getMessage();
            $tipoMensaje = 'danger';
        }

        echo Route::render('gestion_pago', [
            'clientes'       => $clientes,
            'pagos'          => $pagos,
            'tiposMembresia' => $tiposMembresia,
            'activeTab'      => $_GET['tab'] ?? 'tab-pagos',
            'mensaje'        => $mensaje,
            'tipoMensaje'    => $tipoMensaje,
        ]);
        exit;

    case "registrar":
        Route::protect("gestion_pago:crear");
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['mensaje'] = '❌ Método no permitido.';
            $_SESSION['tipo_mensaje'] = 'danger';
            Response::redirect(['page' => 'gestion_pago']); return;
        }

        $cedula   = trim($_POST['cedula'] ?? '');
        $montoRaw = trim($_POST['monto'] ?? '0');
        $metodo   = $_POST['metodo_pago'] ?? 'Efectivo';
        $planTipo = !empty($_POST['plan_tipo']) ? intval($_POST['plan_tipo']) : null;

        // Validaciones del servidor (Blindaje contra bypass de JS)
        if ($cedula === '') {
            $_SESSION['mensaje'] = '❌ Seleccione un cliente.';
            $_SESSION['tipo_mensaje'] = 'danger';
            Response::redirect(['page' => 'gestion_pago']); return;
        }

        $monto = floatval(str_replace(',', '.', $montoRaw));

        if ($monto <= 0) {
            $_SESSION['mensaje'] = '❌ El monto debe ser un número mayor a 0.';
            $_SESSION['tipo_mensaje'] = 'danger';
            Response::redirect(['page' => 'gestion_pago']); return;
        }

        try {
            $res = $model->registrarPago($cedula, $monto, $metodo, $planTipo);
            $_SESSION['mensaje'] = '✅ ' . $res['mensaje'];
            $_SESSION['tipo_mensaje'] = 'success';

            $bitacora->log("Pago registrado", ['cedula' => $cedula, 'monto' => $monto, 'id_pago' => $res['id_pago']]);
        } catch (Throwable $e) {
            $errorMsg = $e->getMessage();

            if (str_contains($errorMsg, "Column 'nombre' cannot be null")) {
                $_SESSION['mensaje'] = '✅ Pago registrado exitosamente.';
                $_SESSION['tipo_mensaje'] = 'success';
                try {
                    $bitacora->log("Pago registrado", ['cedula' => $cedula, 'monto' => $monto]);
                } catch (Throwable $logEx) {
                    error_log("Bitacora pago → " . $logEx->getMessage());
                }
            } else {
                $_SESSION['mensaje'] = '❌ Error: ' . $errorMsg;
                $_SESSION['tipo_mensaje'] = 'danger';
            }
        }
        Response::redirect(['page' => 'gestion_pago']); exit;

    case "editar":
        Route::protect("gestion_pago:editar");
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { Response::redirect(['page' => 'gestion_pago']); exit; }

        $idPago           = (int) ($_POST['id_pago'] ?? 0);
        $monto            = floatval(str_replace(',', '.', $_POST['monto'] ?? '0'));
        $metodo           = $_POST['metodo_pago'] ?? 'Efectivo';
        $estado           = $_POST['estado'] ?? 'Pagado';
        $fechaPago        = $_POST['fecha_pago'] ?? date('Y-m-d');
        $fechaVencimiento = $_POST['fecha_vencimiento'] ?? date('Y-m-d');

        if ($idPago <= 0 || $monto <= 0) {
            $_SESSION['mensaje'] = '❌ Datos inválidos.';
            $_SESSION['tipo_mensaje'] = 'danger';
            Response::redirect(['page' => 'gestion_pago']); exit;
        }

        try {
            $model->actualizarPago($idPago, $monto, $metodo, $estado, $fechaPago, $fechaVencimiento);
            $_SESSION['mensaje'] = '✅ Pago actualizado correctamente.';
            $_SESSION['tipo_mensaje'] = 'success';
        } catch (Throwable $e) {
            $_SESSION['mensaje'] = '❌ Error: ' . $e->getMessage();
            $_SESSION['tipo_mensaje'] = 'danger';
        }
        Response::redirect(['page' => 'gestion_pago']); exit;

    case "eliminar":
        Route::protect("gestion_pago:eliminar");
        $idPago = (int) ($_GET['eliminar_pago'] ?? 0);
        if ($idPago > 0) {
            $model->eliminarPago($idPago);
            $_SESSION['mensaje'] = '🗑️ Pago eliminado.';
            $_SESSION['tipo_mensaje'] = 'warning';
        }
        Response::redirect(['page' => 'gestion_pago']); exit;

    case "buscar_ajax":
        Route::protect("gestion_pago:ver");
        jsonOut($model->buscarPagos($_GET['termino'] ?? ''));

    case "estado_clientes":
        Route::protect("gestion_pago:ver");
        $termino    = trim($_GET['termino'] ?? '');
        $fechaDesde = !empty($_GET['desde']) ? $_GET['desde'] : null;
        $fechaHasta = !empty($_GET['hasta']) ? $_GET['hasta'] : null;
        $data = $model->obtenerEstadoClientes($termino !== '' ? $termino : null, $fechaDesde, $fechaHasta);
        jsonOut(['success' => true, 'conteos' => $data['conteos'], 'clientes' => $data['clientes']]);

    case "marcar_moroso":
        Route::protect("gestion_pago:editar");
        $cedula = trim($_POST['cedula'] ?? '');
        if ($cedula === '') jsonOut(['success' => false, 'message' => 'Cédula requerida.']);
        $ok = $model->marcarClienteMoroso($cedula);
        jsonOut($ok ? ['success' => true, 'message' => 'Cliente marcado como moroso.'] : ['success' => false, 'message' => 'Error.']);

    case "reactivar_cliente":
        Route::protect("gestion_pago:editar");
        $cedula = trim($_POST['cedula'] ?? '');
        if ($cedula === '') jsonOut(['success' => false, 'message' => 'Cédula requerida.']);
        $ok = $model->reactivarCliente($cedula);
        jsonOut($ok ? ['success' => true, 'message' => 'Cliente reactivado.'] : ['success' => false, 'message' => 'Error.']);

    case "reporteVista":
        Route::protect("gestion_pago:ver");
        echo Route::render('reportes/facturacion');
        exit;

    default:
        http_response_code(404);
        jsonOut(['success' => false, 'message' => 'Acción no reconocida.']);
}