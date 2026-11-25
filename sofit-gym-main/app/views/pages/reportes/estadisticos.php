<?php
$this->pushJs("pages/reportes/estadisticos.js");
$this->layout("layout", ["title" => "Reportes Estadísticos"]);

// Datos iniciales seguros provenientes del controlador
$definicionReportes = $definicionReportes ?? [];
$moduloActual       = $moduloActual ?? 'resumen_general';
$reporteInicial     = $reporteInicial ?? [
    'exito'     => true,
    'modulo'    => $moduloActual,
    'titulo'    => 'Resumen Ejecutivo del Gimnasio',
    'filtros'   => [],
    'kpis'      => [],
    'graficos'  => [],
    'columnas'  => [],
    'registros' => []
];

// Iconos asociados a cada módulo de reporte
$iconosModulos = [
    'resumen_general'   => 'fa-tachometer-alt',
    'finanzas'          => 'fa-dollar-sign',
    'membresias'        => 'fa-id-card',
    'clientes'          => 'fa-users',
    'asistencias'       => 'fa-clipboard-check',
    'inventario_ventas' => 'fa-boxes',
    'clases'            => 'fa-dumbbell',
    'equipos'           => 'fa-cogs',
    'rutinas'           => 'fa-running',
    'trabajadores'      => 'fa-user-tie'
];

// Etiquetas amigables para los filtros predeterminados
$etiquetasFiltros = [
    'periodo'            => 'Período de Tiempo',
    'id_metodo'          => 'Método de Pago',
    'estado'             => 'Estado',
    'concepto'           => 'Concepto de Ingreso',
    'id_tipo'            => 'Tipo de Plan / Membresía',
    'id_estado'          => 'Estado de Membresía',
    'activo'             => 'Estado en Sistema',
    'rango_edad'         => 'Rango de Edad',
    'con_rutina'         => 'Asignación de Rutina',
    'tipo'               => 'Tipo de Movimiento',
    'franja_horaria'     => 'Franja Horaria',
    'id_categoria'       => 'Categoría de Producto',
    'estado_stock'       => 'Nivel de Inventario',
    'solo_activos'       => 'Disponibilidad',
    'cedula_trabajador'  => 'Entrenador Responsable',
    'tipo_mantenimiento' => 'Tipo de Mantenimiento',
    'id_dificultad'      => 'Nivel de Dificultad',
    'id_rol'             => 'Rol del Trabajador'
];

// Etiquetas amigables y formato para KPIs
$metaKpis = [
    'total_clientes'          => ['label' => 'Total Clientes', 'icon' => 'fa-users', 'tipo' => 'numero'],
    'total_trabajadores'      => ['label' => 'Total Trabajadores', 'icon' => 'fa-user-tie', 'tipo' => 'numero'],
    'personas_activas'        => ['label' => 'Personas Activas', 'icon' => 'fa-user-check', 'tipo' => 'numero'],
    'membresias_activas'      => ['label' => 'Membresías Activas', 'icon' => 'fa-id-badge', 'tipo' => 'numero'],
    'membresias_vencidas'     => ['label' => 'Membresías Vencidas', 'icon' => 'fa-calendar-times', 'tipo' => 'numero'],
    'membresias_morosas'      => ['label' => 'Membresías Morosas', 'icon' => 'fa-exclamation-circle', 'tipo' => 'numero'],
    'asistencias_hoy'         => ['label' => 'Asistencias Hoy', 'icon' => 'fa-door-open', 'tipo' => 'numero'],
    'clases_programadas'      => ['label' => 'Clases Programadas', 'icon' => 'fa-calendar-alt', 'tipo' => 'numero'],
    'clases_en_curso'         => ['label' => 'Clases en Curso', 'icon' => 'fa-play-circle', 'tipo' => 'numero'],
    'ingresos_hoy'            => ['label' => 'Ingresos Hoy', 'icon' => 'fa-coins', 'tipo' => 'moneda'],
    'ingresos_mes'            => ['label' => 'Ingresos del Mes', 'icon' => 'fa-chart-line', 'tipo' => 'moneda'],
    'equipos_operativos'      => ['label' => 'Equipos Operativos', 'icon' => 'fa-check-circle', 'tipo' => 'numero'],
    'equipos_en_mantenimiento'=> ['label' => 'En Mantenimiento', 'icon' => 'fa-tools', 'tipo' => 'numero'],
    'productos_bajo_stock'    => ['label' => 'Productos Bajo Stock', 'icon' => 'fa-exclamation-triangle', 'tipo' => 'numero'],
    'pagos_pendientes'        => ['label' => 'Pagos Pendientes', 'icon' => 'fa-clock', 'tipo' => 'numero'],
    'total_recaudado'         => ['label' => 'Total Recaudado', 'icon' => 'fa-hand-holding-usd', 'tipo' => 'moneda'],
    'total_pendiente'         => ['label' => 'Saldo Pendiente / Mora', 'icon' => 'fa-file-invoice-dollar', 'tipo' => 'moneda'],
    'total_transacciones'     => ['label' => 'Transacciones', 'icon' => 'fa-receipt', 'tipo' => 'numero'],
    'ticket_promedio'         => ['label' => 'Ticket Promedio', 'icon' => 'fa-tag', 'tipo' => 'moneda'],
    'total_registros'         => ['label' => 'Total Registros', 'icon' => 'fa-list', 'tipo' => 'numero'],
    'activas'                 => ['label' => 'Activas', 'icon' => 'fa-check', 'tipo' => 'numero'],
    'vencidas'                => ['label' => 'Vencidas', 'icon' => 'fa-times', 'tipo' => 'numero'],
    'morosas'                 => ['label' => 'Morosas', 'icon' => 'fa-ban', 'tipo' => 'numero'],
    'valor_cartera_activa'    => ['label' => 'Cartera Activa Estimada', 'icon' => 'fa-wallet', 'tipo' => 'moneda'],
    'edad_promedio'           => ['label' => 'Edad Promedio (Años)', 'icon' => 'fa-birthday-cake', 'tipo' => 'numero'],
    'total_asistencias'       => ['label' => 'Total Asistencias', 'icon' => 'fa-walking', 'tipo' => 'numero'],
    'clientes_unicos'         => ['label' => 'Clientes Únicos', 'icon' => 'fa-user-friends', 'tipo' => 'numero'],
    'promedio_diario'         => ['label' => 'Promedio Diario', 'icon' => 'fa-calendar-day', 'tipo' => 'numero'],
    'total_productos'         => ['label' => 'Total Productos', 'icon' => 'fa-box', 'tipo' => 'numero'],
    'valor_total_stock'       => ['label' => 'Valoración de Stock', 'icon' => 'fa-warehouse', 'tipo' => 'moneda'],
    'unidades_vendidas'       => ['label' => 'Unidades Vendidas', 'icon' => 'fa-shopping-bag', 'tipo' => 'numero'],
    'total_clases'            => ['label' => 'Total Clases', 'icon' => 'fa-chalkboard-teacher', 'tipo' => 'numero'],
    'total_inscritos'         => ['label' => 'Total Inscritos', 'icon' => 'fa-user-plus', 'tipo' => 'numero'],
    'ocupacion_media'         => ['label' => 'Ocupación Media', 'icon' => 'fa-percentage', 'tipo' => 'porcentaje'],
    'total_equipos'           => ['label' => 'Total Equipos', 'icon' => 'fa-dumbbell', 'tipo' => 'numero'],
    'total_mantenimientos'    => ['label' => 'Mantenimientos', 'icon' => 'fa-wrench', 'tipo' => 'numero'],
    'costo_total'             => ['label' => 'Costo Mantenimiento', 'icon' => 'fa-money-bill-wave', 'tipo' => 'moneda'],
    'rutinas_asignadas'       => ['label' => 'Rutinas Asignadas', 'icon' => 'fa-clipboard-list', 'tipo' => 'numero'],
    'progreso_promedio'       => ['label' => 'Progreso Promedio', 'icon' => 'fa-tasks', 'tipo' => 'porcentaje'],
    'nomina_total'            => ['label' => 'Nómina Total', 'icon' => 'fa-money-check-alt', 'tipo' => 'moneda']
];

$filtrosModuloActual = $definicionReportes[$moduloActual]['filtros'] ?? [];
$filtrosActivos      = $reporteInicial['filtros'] ?? [];
?>

<!-- Librería Chart.js para gráficos estadísticos dinámicos -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .container {
        --bs-gutter-x: 0;
        border-radius: 28px;
        background-color: white;
        overflow: hidden;
    }

    .header-banner {
        background: #C62828;
        color: white;
        padding: 1.2rem 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .header-banner h1 {
        font-size: 1.6rem;
        font-weight: 600;
        margin: 0;
    }

    .card-custom {
        background: white;
        border-radius: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03), 0 4px 12px rgba(0, 0, 0, 0.05);
        margin-bottom: 1.8rem;
        border: 1px solid #edf2f7;
    }

    .card-header-custom {
        background: #fafbfc;
        padding: 1rem 1.5rem;
        font-weight: 700;
        font-size: 1.05rem;
        border-bottom: 1px solid #edf2f7;
        color: #1e2a3a;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.75rem;
        border-top-left-radius: 20px;
        border-top-right-radius: 20px;
    }

    .card-body-custom {
        padding: 1.5rem;
    }

    /* Selector rápido de módulos (Pills horizontales) */
    .modulos-pills-wrapper {
        display: flex;
        gap: 0.6rem;
        overflow-x: auto;
        padding-bottom: 0.5rem;
        margin-bottom: 1.5rem;
        scrollbar-width: thin;
    }

    .modulo-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.55rem 1rem;
        border-radius: 999px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #475569;
        font-size: 0.82rem;
        font-weight: 600;
        white-space: nowrap;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .modulo-pill:hover {
        background: #fff5f5;
        border-color: #feb2b2;
        color: #C62828;
    }

    .modulo-pill.active {
        background: #C62828;
        border-color: #C62828;
        color: #ffffff;
        box-shadow: 0 3px 8px rgba(198, 40, 40, 0.25);
    }

    .form-grid-custom {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 1rem;
        align-items: flex-end;
    }

    .form-group-custom {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
    }

    label {
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #5a6e7a;
        margin-bottom: 0;
    }

    .input-custom,
    .select-custom,
    .btn-custom {
        padding: 0.68rem 1rem;
        border-radius: 14px;
        border: 1px solid #cfdfe8;
        font-size: 0.9rem;
        width: 100%;
    }

    .btn-main {
        background: #C62828;
        color: white;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: background 0.2s, transform 0.1s;
    }

    .btn-main:hover {
        background: #b71c1c;
        color: white;
        transform: translateY(-1px);
    }

    .btn-outline-custom {
        background: white;
        color: #475569;
        font-weight: 600;
        border: 1px solid #cbd5e1;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-outline-custom:hover {
        background: #f1f5f9;
        color: #1e293b;
    }

    /* Tarjetas KPI */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1.8rem;
    }

    .kpi-card {
        background: #ffffff;
        border: 1px solid #edf2f7;
        border-left: 4px solid #C62828;
        border-radius: 16px;
        padding: 1.1rem 1.25rem;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: transform 0.15s ease;
    }

    .kpi-card:hover {
        transform: translateY(-2px);
    }

    .kpi-titulo {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        font-weight: 700;
        margin-bottom: 0.3rem;
    }

    .kpi-valor {
        font-size: 1.45rem;
        font-weight: 800;
        color: #1e293b;
        margin: 0;
    }

    .kpi-icono {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: #fff5f5;
        color: #C62828;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    /* Contenedor de Gráficos */
    .graficos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
        gap: 1.5rem;
        margin-bottom: 1.8rem;
    }

    .chart-canvas-wrapper {
        position: relative;
        height: 270px;
        width: 100%;
    }

    /* Tabla y Buscador */
    .table-responsive {
        overflow-x: auto;
        max-height: 520px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85rem;
    }

    th,
    td {
        padding: 0.65rem 0.75rem;
        text-align: left;
        border-bottom: 1px solid #eef2f6;
        vertical-align: middle;
    }

    th {
        background: #f8fafc;
        font-weight: 700;
        color: #334155;
        position: sticky;
        top: 0;
        z-index: 2;
    }

    .buscador-contenedor {
        margin-bottom: 1rem;
        display: flex;
        gap: 0.6rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .buscador-contenedor input {
        flex: 1;
        min-width: 240px;
    }

    #toastMessage {
        position: fixed;
        top: 20px;
        right: 20px;
        background: #323232;
        color: white;
        padding: 12px 20px;
        border-radius: 10px;
        z-index: 9999;
        display: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        font-size: 0.9rem;
    }

    .loading-overlay {
        display: none;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
        padding: 0.6rem 1rem;
        background: #fff5f5;
        color: #C62828;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.85rem;
    }

    @media print {
        body * {
            visibility: hidden;
        }

        #areaImpresionReporte,
        #areaImpresionReporte * {
            visibility: visible;
        }

        #areaImpresionReporte {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            padding: 10px;
        }

        .no-print {
            display: none !important;
        }

        .card-custom {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
        }
    }
</style>

<div class="container">
    <!-- Encabezado Principal -->
    <div class="header-banner">
        <div>
            <h1><i class="fas fa-chart-pie me-2"></i> Reportes Estadísticos e Inteligencia de Negocio</h1>
            <small class="opacity-75">Consulta dinámica con filtros predeterminados de toda la base de datos SOFIT GYM</small>
        </div>
        <div class="d-flex gap-2 no-print">
            <a id="btnExportarCsv"
               href="?page=reporteEstadistico&action=exportarCsv&modulo=<?= urlencode($moduloActual) ?>"
               class="btn btn-light btn-sm fw-bold px-3 py-2"
               style="border-radius: 12px; color: #1e293b;">
                <i class="fas fa-file-excel text-success me-1"></i> Exportar CSV
            </a>
            <button type="button"
                    id="btnImprimirReporte"
                    onclick="window.print()"
                    class="btn btn-dark btn-sm fw-bold px-3 py-2"
                    style="border-radius: 12px;">
                <i class="fas fa-print me-1"></i> Imprimir
            </button>
        </div>
    </div>

    <div id="toastMessage"></div>

    <div class="p-4">
        <!-- Alertas de Sesión -->
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?= htmlspecialchars($tipoMensaje ?: 'info') ?> alert-dismissible fade show mb-4" role="alert" style="border-radius: 16px;">
                <i class="fas fa-<?= $tipoMensaje === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                <?= htmlspecialchars($mensaje) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Navegación Rápida de Módulos -->
        <div class="modulos-pills-wrapper no-print" id="navModulosReporte">
            <?php foreach ($definicionReportes as $claveModulo => $infoModulo): ?>
                <?php $icono = $iconosModulos[$claveModulo] ?? 'fa-chart-bar'; ?>
                <button type="button"
                        class="modulo-pill <?= $claveModulo === $moduloActual ? 'active' : '' ?>"
                        data-modulo="<?= htmlspecialchars($claveModulo) ?>">
                    <i class="fas <?= $icono ?>"></i>
                    <span><?= htmlspecialchars($infoModulo['titulo']) ?></span>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- ==================== PANEL DE SELECCIÓN Y FILTROS PREDETERMINADOS ==================== -->
        <div class="card-custom no-print">
            <div class="card-header-custom">
                <span><i class="fas fa-filter text-danger me-1"></i> Configuración y Filtros Predeterminados</span>
                <div class="loading-overlay" id="indicadorCargando">
                    <i class="fas fa-spinner fa-spin"></i> Actualizando estadísticas...
                </div>
            </div>
            <div class="card-body-custom">
                <form id="formFiltrosReporte" autocomplete="off">
                    <div class="row g-3 mb-3 align-items-end">
                        <div class="col-lg-5 col-md-6">
                            <div class="form-group-custom">
                                <label for="selectModuloReporte"><i class="fas fa-layer-group me-1"></i> Módulo / Área de Información</label>
                                <select id="selectModuloReporte" name="modulo" class="form-select select-custom fw-bold">
                                    <?php foreach ($definicionReportes as $claveModulo => $infoModulo): ?>
                                        <option value="<?= htmlspecialchars($claveModulo) ?>" <?= $claveModulo === $moduloActual ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($infoModulo['titulo']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-7 col-md-6">
                            <div class="p-2 px-3 bg-light rounded-3 border" style="border-radius: 14px !important;">
                                <small class="text-muted d-block fw-bold text-uppercase" style="font-size: 0.7rem;">Descripción del Reporte</small>
                                <span id="descripcionModuloActual" class="text-dark small">
                                    <?= htmlspecialchars($definicionReportes[$moduloActual]['descripcion'] ?? '') ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Contenedor de Filtros Predeterminados (se actualiza dinámicamente al cambiar módulo) -->
                    <div class="form-grid-custom mb-3" id="contenedorFiltrosDinamicos">
                        <?php if (!empty($filtrosModuloActual)): ?>
                            <?php foreach ($filtrosModuloActual as $nombreFiltro => $opciones): ?>
                                <div class="form-group-custom">
                                    <label for="filtro_<?= htmlspecialchars($nombreFiltro) ?>">
                                        <?= htmlspecialchars($etiquetasFiltros[$nombreFiltro] ?? ucfirst(str_replace('_', ' ', $nombreFiltro))) ?>
                                    </label>
                                    <select id="filtro_<?= htmlspecialchars($nombreFiltro) ?>"
                                            name="<?= htmlspecialchars($nombreFiltro) ?>"
                                            class="form-select select-custom filtro-dinamico">
                                        <?php foreach ($opciones as $valorOp => $textoOp): ?>
                                            <?php $seleccionado = isset($filtrosActivos[$nombreFiltro]) && (string)$filtrosActivos[$nombreFiltro] === (string)$valorOp; ?>
                                            <option value="<?= htmlspecialchars((string)$valorOp) ?>" <?= $seleccionado ? 'selected' : '' ?>>
                                                <?= htmlspecialchars((string)$textoOp) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-muted small py-2">
                                <i class="fas fa-info-circle me-1"></i> Este reporte muestra los indicadores globales consolidados en tiempo real sin necesidad de filtros adicionales.
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Rango de Fechas Personalizado (visible solo cuando periodo = 'personalizado') -->
                    <?php $mostrarFechasCustom = ($filtrosActivos['periodo'] ?? '') === 'personalizado'; ?>
                    <div class="row g-3 mb-3" id="rangoFechasPersonalizado" style="display: <?= $mostrarFechasCustom ? 'flex' : 'none' ?>;">
                        <div class="col-md-3">
                            <div class="form-group-custom">
                                <label for="filtroFechaInicio">Fecha Desde</label>
                                <input type="date"
                                       id="filtroFechaInicio"
                                       name="fecha_inicio"
                                       class="form-control input-custom"
                                       value="<?= htmlspecialchars($filtrosActivos['fecha_inicio'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group-custom">
                                <label for="filtroFechaFin">Fecha Hasta</label>
                                <input type="date"
                                       id="filtroFechaFin"
                                       name="fecha_fin"
                                       class="form-control input-custom"
                                       value="<?= htmlspecialchars($filtrosActivos['fecha_fin'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="d-flex flex-wrap gap-2 justify-content-end pt-2 border-top">
                        <button type="button" id="btnResetearFiltros" class="btn-custom btn-outline-custom" style="width: auto;">
                            <i class="fas fa-undo me-1"></i> Restablecer Filtros
                        </button>
                        <button type="submit" id="btnAplicarFiltros" class="btn-custom btn-main px-4" style="width: auto;">
                            <i class="fas fa-sync-alt me-1"></i> Generar Reporte
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ==================== ÁREA IMPRIMIBLE DE RESULTADOS ==================== -->
        <div id="areaImpresionReporte">
            <!-- 1. TARJETAS KPI -->
            <div class="kpi-grid" id="contenedorKpis">
                <?php if (!empty($reporteInicial['kpis'])): ?>
                    <?php foreach ($reporteInicial['kpis'] as $claveKpi => $valorKpi): ?>
                        <?php
                        $infoKpi = $metaKpis[$claveKpi] ?? [
                            'label' => ucfirst(str_replace('_', ' ', $claveKpi)),
                            'icon'  => 'fa-chart-bar',
                            'tipo'  => 'numero'
                        ];
                        if ($infoKpi['tipo'] === 'moneda') {
                            $valorFormateado = '$' . number_format((float)$valorKpi, 2);
                        } elseif ($infoKpi['tipo'] === 'porcentaje') {
                            $valorFormateado = number_format((float)$valorKpi, 1) . '%';
                        } else {
                            $valorFormateado = is_numeric($valorKpi) ? number_format((float)$valorKpi, 0) : htmlspecialchars((string)$valorKpi);
                        }
                        ?>
                        <div class="kpi-card">
                            <div>
                                <div class="kpi-titulo"><?= htmlspecialchars($infoKpi['label']) ?></div>
                                <h3 class="kpi-valor"><?= $valorFormateado ?></h3>
                            </div>
                            <div class="kpi-icono">
                                <i class="fas <?= $infoKpi['icon'] ?>"></i>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- 2. SECCIÓN DE GRÁFICOS ESTADÍSTICOS -->
            <div id="seccionGraficos" style="display: <?= !empty($reporteInicial['graficos']) ? 'block' : 'none' ?>;">
                <div class="graficos-grid" id="contenedorGraficos">
                    <?php if (!empty($reporteInicial['graficos'])): ?>
                        <?php foreach ($reporteInicial['graficos'] as $claveGrafico => $serie): ?>
                            <div class="card-custom mb-0">
                                <div class="card-header-custom">
                                    <span><i class="fas fa-chart-area text-danger me-1"></i> <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $claveGrafico))) ?></span>
                                </div>
                                <div class="card-body-custom">
                                    <div class="chart-canvas-wrapper">
                                        <canvas id="canvas_grafico_<?= htmlspecialchars($claveGrafico) ?>"></canvas>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 3. TABLA DETALLADA DE REGISTROS -->
            <div class="card-custom">
                <div class="card-header-custom">
                    <span id="tituloTablaReporte">
                        <i class="fas fa-table text-danger me-1"></i> <?= htmlspecialchars($reporteInicial['titulo'] ?? 'Resultados del Reporte') ?>
                    </span>
                    <span class="badge bg-danger rounded-pill px-3" id="contadorRegistrosBadge">
                        <?= count($reporteInicial['registros'] ?? []) ?> Registros
                    </span>
                </div>
                <div class="card-body-custom">
                    <div class="buscador-contenedor no-print">
                        <input type="text"
                               id="buscarRegistroTabla"
                               class="form-control input-custom"
                               placeholder="Filtrar rápidamente dentro de los resultados mostrados (cédula, nombre, estado, plan...)">
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle" id="tablaReporteEstadistico">
                            <thead id="tablaReporteHead">
                                <tr>
                                    <?php foreach (($reporteInicial['columnas'] ?? []) as $col): ?>
                                        <th><?= htmlspecialchars($col) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody id="tablaReporteBody">
                                <?php if (!empty($reporteInicial['registros'])): ?>
                                    <?php foreach ($reporteInicial['registros'] as $fila): ?>
                                        <tr>
                                            <?php foreach ($fila as $campo => $valor): ?>
                                                <?php
                                                // Ocultar columna auxiliar interna 'hora_num' en el renderizado inicial de asistencias
                                                if ($moduloActual === 'asistencias' && $campo === 'hora_num') {
                                                    continue;
                                                }
                                                ?>
                                                <td><?= htmlspecialchars((string)($valor ?? '—')) ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="<?= max(1, count($reporteInicial['columnas'] ?? [])) ?>" class="text-center py-4 text-muted">
                                            <i class="fas fa-folder-open fa-2x mb-2 d-block"></i>
                                            No se encontraron registros para los filtros seleccionados.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidratación de datos iniciales para pages/estadisticos/estadisticos.js -->
<script>
    window.REPORTES_APP = {
        definicion: <?= json_encode($definicionReportes, JSON_UNESCAPED_UNICODE) ?>,
        moduloActual: <?= json_encode($moduloActual, JSON_UNESCAPED_UNICODE) ?>,
        reporteInicial: <?= json_encode($reporteInicial, JSON_UNESCAPED_UNICODE) ?>,
        etiquetasFiltros: <?= json_encode($etiquetasFiltros, JSON_UNESCAPED_UNICODE) ?>,
        metaKpis: <?= json_encode($metaKpis, JSON_UNESCAPED_UNICODE) ?>
    };
</script>