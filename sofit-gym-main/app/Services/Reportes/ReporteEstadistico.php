<?php

namespace App\Services\Reportes;

use App\Models\Database;
use PDO;
use PDOException;
use DateTime;

/**
 * Servicio / Modelo de Reportes Estadísticos para Sofit Gym.
 * Centraliza las consultas analíticas, KPIs, series para gráficos y listados detallados
 * aplicando filtros predeterminados y seguros (Prepared Statements) sobre la BD `sofit_gym`.
 */
class ReporteEstadistico extends Database
{
    /**
     * Devuelve la definición de todos los módulos de reporte disponibles junto con
     * sus filtros predeterminados y catálogos dinámicos desde la base de datos.
     * Ideal para que el Controlador envíe esta configuración a la Vista.
     */
    public function obtenerDefinicionReportes(): array
    {
        $catalogos = $this->obtenerCatalogosBD();

        return [
            'resumen_general' => [
                'titulo'      => 'Resumen Ejecutivo del Gimnasio',
                'descripcion' => 'Indicadores globales en tiempo real desde vw_dashboard_estadisticas.',
                'filtros'     => []
            ],
            'finanzas' => [
                'titulo'      => 'Ingresos, Pagos y Facturación',
                'descripcion' => 'Análisis de ingresos por membresías y ventas de productos.',
                'filtros'     => [
                    'periodo'   => ['hoy' => 'Hoy', 'semana' => 'Últimos 7 días', 'mes_actual' => 'Este mes', 'mes_anterior' => 'Mes anterior', 'anio_actual' => 'Este año', 'todos' => 'Todo el historial', 'personalizado' => 'Rango personalizado'],
                    'id_metodo' => ['' => 'Todos los métodos'] + $catalogos['metodos_pago'],
                    'estado'    => ['' => 'Todos los estados', 'Pagado' => 'Pagado', 'Pendiente' => 'Pendiente', 'Atrasado' => 'Atrasado'],
                    'concepto'  => ['todos' => 'Todos los conceptos', 'membresia' => 'Solo Membresías / Servicios', 'producto' => 'Solo Venta de Productos']
                ]
            ],
            'membresias' => [
                'titulo'      => 'Estado y Distribución de Membresías',
                'descripcion' => 'Control de planes activos, vencidos, morosos y próximos a vencer.',
                'filtros'     => [
                    'id_tipo'   => ['' => 'Todos los planes'] + $catalogos['tipos_membresia'],
                    'id_estado' => ['' => 'Todos los estados'] + $catalogos['estados_membresia'] + ['por_vencer' => 'Próximos a vencer (<= 7 días)'],
                    'periodo'   => ['todos' => 'Todas las fechas', 'mes_actual' => 'Creadas este mes', 'anio_actual' => 'Creadas este año', 'personalizado' => 'Rango personalizado']
                ]
            ],
            'clientes' => [
                'titulo'      => 'Demografía y Actividad de Clientes',
                'descripcion' => 'Listado analítico de clientes con edad, membresía actual y frecuencia de asistencia.',
                'filtros'     => [
                    'activo'      => ['1' => 'Solo activos', '0' => 'Inactivos', '' => 'Todos'],
                    'rango_edad'  => ['' => 'Todas las edades', 'sub20' => 'Menores de 20 años', '20_35' => '20 a 35 años', '36_50' => '36 a 50 años', '50_mas' => 'Mayores de 50 años'],
                    'con_rutina'  => ['' => 'Con o sin rutina', 'si' => 'Con rutina activa', 'no' => 'Sin rutina activa'],
                    'periodo'     => ['todos' => 'Cualquier fecha de registro', 'mes_actual' => 'Registrados este mes', 'anio_actual' => 'Registrados este año', 'personalizado' => 'Rango personalizado']
                ]
            ],
            'asistencias' => [
                'titulo'      => 'Control de Asistencias y Afluencia',
                'descripcion' => 'Flujo de entradas/salidas por fecha y franja horaria.',
                'filtros'     => [
                    'periodo'        => ['mes_actual' => 'Este mes', 'hoy' => 'Hoy', 'semana' => 'Últimos 7 días', 'anio_actual' => 'Este año', 'todos' => 'Todo el historial', 'personalizado' => 'Rango personalizado'],
                    'tipo'           => ['Entrada' => 'Entradas', 'Salida' => 'Salidas', '' => 'Entradas y Salidas'],
                    'franja_horaria' => ['' => 'Todo el día', 'manana' => 'Mañana (05:00 - 11:59)', 'tarde' => 'Tarde (12:00 - 17:59)', 'noche' => 'Noche (18:00 - 23:59)']
                ]
            ],
            'inventario_ventas' => [
                'titulo'      => 'Inventario y Ventas de Productos',
                'descripcion' => 'Valoración de stock, alertas de reposición y productos más vendidos.',
                'filtros'     => [
                    'id_categoria' => ['' => 'Todas las categorías'] + $catalogos['categorias_producto'],
                    'estado_stock' => ['todos' => 'Todo el inventario', 'bajo_stock' => 'Bajo stock (<= mínimo)', 'agotado' => 'Agotados (Stock = 0)', 'optimo' => 'Stock óptimo'],
                    'solo_activos' => ['1' => 'Productos activos', '0' => 'Productos descontinuados', '' => 'Todos']
                ]
            ],
            'clases' => [
                'titulo'      => 'Ocupación y Rendimiento de Clases',
                'descripcion' => 'Cupos disponibles, inscritos y porcentaje de ocupación por entrenador.',
                'filtros'     => [
                    'estado'            => ['' => 'Todos los estados', 'Programado' => 'Programado', 'En curso' => 'En curso', 'Finalizado' => 'Finalizado', 'Cancelado' => 'Cancelado'],
                    'cedula_trabajador' => ['' => 'Todos los entrenadores'] + $catalogos['entrenadores'],
                    'periodo'           => ['todos' => 'Todas las fechas', 'mes_actual' => 'Este mes', 'anio_actual' => 'Este año', 'personalizado' => 'Rango personalizado']
                ]
            ],
            'equipos' => [
                'titulo'      => 'Equipos y Costos de Mantenimiento',
                'descripcion' => 'Operatividad de maquinaria e historial de mantenimientos preventivos/correctivos.',
                'filtros'     => [
                    'estado'             => ['' => 'Todos los estados', 'Operativo' => 'Operativo', 'Mantenimiento' => 'En Mantenimiento', 'Fuera de Servicio' => 'Fuera de Servicio'],
                    'tipo_mantenimiento' => ['' => 'Todos los mantenimientos', 'Preventivo' => 'Preventivo', 'Correctivo' => 'Correctivo']
                ]
            ],
            'rutinas' => [
                'titulo'      => 'Rutinas Asignadas y Progreso Físico',
                'descripcion' => 'Seguimiento de planes de entrenamiento por dificultad y porcentaje de avance.',
                'filtros'     => [
                    'id_dificultad' => ['' => 'Todas las dificultades'] + $catalogos['dificultades'],
                    'estado'        => ['' => 'Todos los estados', 'Activa' => 'Activa', 'Completada' => 'Completada', 'Cancelada' => 'Cancelada']
                ]
            ],
            'trabajadores' => [
                'titulo'      => 'Nómina y Productividad del Personal',
                'descripcion' => 'Distribución de personal por rol, salarios y carga de clases/rutinas.',
                'filtros'     => [
                    'id_rol' => ['' => 'Todos los roles'] + $catalogos['roles_trabajador'],
                    'activo' => ['1' => 'Solo activos', '0' => 'Inactivos', '' => 'Todos']
                ]
            ]
        ];
    }

    /**
     * Punto de entrada principal para el Controlador.
     * Ejecuta el reporte solicitado aplicando los filtros enviados.
     *
     * @param string $modulo  Clave del reporte (ej. 'finanzas', 'membresias', 'asistencias', etc.)
     * @param array  $filtros Parámetros recibidos desde GET/POST
     * @return array Estructura unificada con KPIs, gráficos, columnas y registros
     */
    public function generarReporte(string $modulo, array $filtros = []): array
    {
        return match ($modulo) {
            'resumen_general'   => $this->reporteResumenGeneral(),
            'finanzas'          => $this->reporteFinanzas($filtros),
            'membresias'        => $this->reporteMembresias($filtros),
            'clientes'          => $this->reporteClientes($filtros),
            'asistencias'       => $this->reporteAsistencias($filtros),
            'inventario_ventas' => $this->reporteInventarioVentas($filtros),
            'clases'            => $this->reporteClases($filtros),
            'equipos'           => $this->reporteEquipos($filtros),
            'rutinas'           => $this->reporteRutinas($filtros),
            'trabajadores'      => $this->reporteTrabajadores($filtros),
            default             => [
                'exito'   => false,
                'mensaje' => "El módulo de reporte '{$modulo}' no está registrado."
            ]
        };
    }

    /**
     * 1. RESUMEN GENERAL (Usa la vista `vw_dashboard_estadisticas`)
     */
    public function reporteResumenGeneral(): array
    {
        try {
            $sql = "SELECT * FROM vw_dashboard_estadisticas LIMIT 1";
            $stmt = $this->pdo->query($sql);
            $datos = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            return [
                'exito'     => true,
                'modulo'    => 'resumen_general',
                'titulo'    => 'Resumen Ejecutivo del Gimnasio',
                'filtros'   => [],
                'kpis'      => $datos,
                'graficos'  => [
                    'membresias' => [
                        'labels' => ['Activas', 'Vencidas', 'Morosas'],
                        'values' => [
                            (int)($datos['membresias_activas'] ?? 0),
                            (int)($datos['membresias_vencidas'] ?? 0),
                            (int)($datos['membresias_morosas'] ?? 0),
                        ]
                    ],
                    'equipos' => [
                        'labels' => ['Operativos', 'En Mantenimiento'],
                        'values' => [
                            (int)($datos['equipos_operativos'] ?? 0),
                            (int)($datos['equipos_en_mantenimiento'] ?? 0),
                        ]
                    ]
                ],
                'columnas'  => ['Indicador', 'Valor Actual'],
                'registros' => $this->formatearClaveValor($datos)
            ];
        } catch (PDOException $e) {
            error_log("Error en ReporteEstadistico::reporteResumenGeneral: " . $e->getMessage());
            return $this->respuestaError('Error al obtener el resumen general.');
        }
    }

    /**
     * 2. REPORTE FINANCIERO Y DE PAGOS
     */
    public function reporteFinanzas(array $filtros = []): array
    {
        try {
            [$fechaInicio, $fechaFin] = $this->resolverRangoFechas(
                $filtros['periodo'] ?? 'todos',
                $filtros['fecha_inicio'] ?? null,
                $filtros['fecha_fin'] ?? null
            );

            $condiciones = ['1=1'];
            $params = [];

            if ($fechaInicio && $fechaFin) {
                $condiciones[] = "p.fecha_pago BETWEEN :fecha_inicio AND :fecha_fin";
                $params['fecha_inicio'] = $fechaInicio;
                $params['fecha_fin']    = $fechaFin;
            }

            if (!empty($filtros['id_metodo'])) {
                $condiciones[] = "p.id_metodo = :id_metodo";
                $params['id_metodo'] = (int)$filtros['id_metodo'];
            }

            if (!empty($filtros['estado'])) {
                $condiciones[] = "p.estado = :estado";
                $params['estado'] = $filtros['estado'];
            }

            $concepto = $filtros['concepto'] ?? 'todos';
            if ($concepto === 'producto') {
                $condiciones[] = "vp.id_venta IS NOT NULL";
            } elseif ($concepto === 'membresia') {
                $condiciones[] = "vp.id_venta IS NULL";
            }

            $where = implode(' AND ', $condiciones);

            // Consulta principal de registros detallados
            $sql = "SELECT 
                        p.id_pago,
                        p.fecha_pago,
                        COALESCE(p.cedula_cliente, 'Sin asignar') AS cedula_cliente,
                        COALESCE(CONCAT(pe.nombre, ' ', pe.apellido), 'Cliente General') AS cliente,
                        mp.nombre AS metodo_pago,
                        CASE 
                            WHEN vp.id_venta IS NOT NULL THEN CONCAT('Venta Producto: ', pr.nombre, ' (x', vp.cantidad_vendida, ')')
                            ELSE 'Membresía / Servicio'
                        END AS concepto,
                        p.monto,
                        p.estado
                    FROM pago p
                    INNER JOIN metodo_pago mp ON p.id_metodo = mp.id_metodo
                    LEFT JOIN persona pe ON p.cedula_cliente = pe.cedula
                    LEFT JOIN venta_producto vp ON p.id_pago = vp.id_pago
                    LEFT JOIN producto pr ON vp.codigo_producto = pr.codigo_producto
                    WHERE {$where}
                    ORDER BY p.fecha_pago DESC, p.id_pago DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Cálculo de KPIs y agrupación para gráficos
            $totalRecaudado = 0.0;
            $totalPendiente = 0.0;
            $porMetodo      = [];
            $porFecha       = [];

            foreach ($registros as $fila) {
                $monto = (float)$fila['monto'];
                if ($fila['estado'] === 'Pagado') {
                    $totalRecaudado += $monto;
                    $metodo = $fila['metodo_pago'];
                    $porMetodo[$metodo] = ($porMetodo[$metodo] ?? 0) + $monto;

                    $fecha = $fila['fecha_pago'];
                    $porFecha[$fecha] = ($porFecha[$fecha] ?? 0) + $monto;
                } else {
                    $totalPendiente += $monto;
                }
            }

            ksort($porFecha);
            $cantidadPagos = count($registros);

            return [
                'exito'     => true,
                'modulo'    => 'finanzas',
                'titulo'    => 'Reporte Financiero y Facturación',
                'filtros'   => array_merge($filtros, ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin]),
                'kpis'      => [
                    'total_recaudado'   => round($totalRecaudado, 2),
                    'total_pendiente'   => round($totalPendiente, 2),
                    'total_transacciones' => $cantidadPagos,
                    'ticket_promedio'   => $cantidadPagos > 0 ? round($totalRecaudado / $cantidadPagos, 2) : 0.00
                ],
                'graficos'  => [
                    'ingresos_por_metodo' => [
                        'labels' => array_keys($porMetodo),
                        'values' => array_values($porMetodo)
                    ],
                    'tendencia_diaria' => [
                        'labels' => array_keys($porFecha),
                        'values' => array_values($porFecha)
                    ]
                ],
                'columnas'  => ['ID Pago', 'Fecha', 'Cédula', 'Cliente', 'Método', 'Concepto', 'Monto ($)', 'Estado'],
                'registros' => $registros
            ];
        } catch (PDOException $e) {
            error_log("Error en ReporteEstadistico::reporteFinanzas: " . $e->getMessage());
            return $this->respuestaError('Error al generar el reporte financiero.');
        }
    }

    /**
     * 3. REPORTE DE MEMBRESÍAS
     */
    public function reporteMembresias(array $filtros = []): array
    {
        try {
            [$fechaInicio, $fechaFin] = $this->resolverRangoFechas(
                $filtros['periodo'] ?? 'todos',
                $filtros['fecha_inicio'] ?? null,
                $filtros['fecha_fin'] ?? null
            );

            $condiciones = ['1=1'];
            $params = [];

            if ($fechaInicio && $fechaFin) {
                $condiciones[] = "DATE(m.fecha_creacion) BETWEEN :fecha_inicio AND :fecha_fin";
                $params['fecha_inicio'] = $fechaInicio;
                $params['fecha_fin']    = $fechaFin;
            }

            if (!empty($filtros['id_tipo'])) {
                $condiciones[] = "m.id_tipo = :id_tipo";
                $params['id_tipo'] = (int)$filtros['id_tipo'];
            }

            if (!empty($filtros['id_estado'])) {
                if ($filtros['id_estado'] === 'por_vencer') {
                    $condiciones[] = "m.id_estado = 1 AND DATEDIFF(m.fecha_fin, CURDATE()) BETWEEN 0 AND 7";
                } else {
                    $condiciones[] = "m.id_estado = :id_estado";
                    $params['id_estado'] = (int)$filtros['id_estado'];
                }
            }

            $where = implode(' AND ', $condiciones);

            $sql = "SELECT 
                        m.id_membresia,
                        m.cedula_cliente,
                        CONCAT(pe.nombre, ' ', pe.apellido) AS cliente,
                        pe.telefono,
                        tm.nombre AS plan,
                        tm.monto AS tarifa_plan,
                        em.nombre AS estado,
                        m.fecha_inicio,
                        m.fecha_fin,
                        fn_dias_restantes(m.fecha_fin) AS dias_restantes
                    FROM membresia m
                    INNER JOIN tipo_membresia tm ON m.id_tipo = tm.id_tipo
                    INNER JOIN estado_membresia em ON m.id_estado = em.id_estado
                    INNER JOIN persona pe ON m.cedula_cliente = pe.cedula
                    WHERE {$where}
                    ORDER BY m.fecha_fin DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $porPlan   = [];
            $porEstado = [];
            $valorEstimado = 0.0;

            foreach ($registros as $fila) {
                $plan   = $fila['plan'];
                $estado = $fila['estado'];
                $porPlan[$plan]     = ($porPlan[$plan] ?? 0) + 1;
                $porEstado[$estado] = ($porEstado[$estado] ?? 0) + 1;
                if ($estado === 'Activo') {
                    $valorEstimado += (float)$fila['tarifa_plan'];
                }
            }

            return [
                'exito'     => true,
                'modulo'    => 'membresias',
                'titulo'    => 'Reporte de Membresías y Planes',
                'filtros'   => $filtros,
                'kpis'      => [
                    'total_registros'     => count($registros),
                    'activas'             => $porEstado['Activo'] ?? 0,
                    'vencidas'            => $porEstado['Vencido'] ?? 0,
                    'morosas'             => $porEstado['Moroso'] ?? 0,
                    'valor_cartera_activa'=> round($valorEstimado, 2)
                ],
                'graficos'  => [
                    'distribucion_planes' => [
                        'labels' => array_keys($porPlan),
                        'values' => array_values($porPlan)
                    ],
                    'distribucion_estados' => [
                        'labels' => array_keys($porEstado),
                        'values' => array_values($porEstado)
                    ]
                ],
                'columnas'  => ['ID', 'Cédula', 'Cliente', 'Teléfono', 'Plan', 'Tarifa ($)', 'Estado', 'Inicio', 'Fin', 'Días Restantes'],
                'registros' => $registros
            ];
        } catch (PDOException $e) {
            error_log("Error en ReporteEstadistico::reporteMembresias: " . $e->getMessage());
            return $this->respuestaError('Error al generar el reporte de membresías.');
        }
    }

    /**
     * 4. REPORTE DE CLIENTES Y DEMOGRAFÍA
     */
    public function reporteClientes(array $filtros = []): array
    {
        try {
            [$fechaInicio, $fechaFin] = $this->resolverRangoFechas(
                $filtros['periodo'] ?? 'todos',
                $filtros['fecha_inicio'] ?? null,
                $filtros['fecha_fin'] ?? null
            );

            $condiciones = ['1=1'];
            $params = [];

            if (isset($filtros['activo']) && $filtros['activo'] !== '') {
                $condiciones[] = "pe.activo = :activo";
                $params['activo'] = (int)$filtros['activo'];
            }

            if ($fechaInicio && $fechaFin) {
                $condiciones[] = "DATE(c.fecha_creacion) BETWEEN :fecha_inicio AND :fecha_fin";
                $params['fecha_inicio'] = $fechaInicio;
                $params['fecha_fin']    = $fechaFin;
            }

            if (!empty($filtros['rango_edad'])) {
                switch ($filtros['rango_edad']) {
                    case 'sub20':
                        $condiciones[] = "fn_calcular_edad(pe.fecha_nacimiento) < 20";
                        break;
                    case '20_35':
                        $condiciones[] = "fn_calcular_edad(pe.fecha_nacimiento) BETWEEN 20 AND 35";
                        break;
                    case '36_50':
                        $condiciones[] = "fn_calcular_edad(pe.fecha_nacimiento) BETWEEN 36 AND 50";
                        break;
                    case '50_mas':
                        $condiciones[] = "fn_calcular_edad(pe.fecha_nacimiento) > 50";
                        break;
                }
            }

            if (!empty($filtros['con_rutina'])) {
                if ($filtros['con_rutina'] === 'si') {
                    $condiciones[] = "EXISTS (SELECT 1 FROM rutina_asignada ra WHERE ra.cedula_cliente = c.cedula AND ra.estado = 'Activa')";
                } elseif ($filtros['con_rutina'] === 'no') {
                    $condiciones[] = "NOT EXISTS (SELECT 1 FROM rutina_asignada ra WHERE ra.cedula_cliente = c.cedula AND ra.estado = 'Activa')";
                }
            }

            $where = implode(' AND ', $condiciones);

            $sql = "SELECT 
                        c.cedula,
                        CONCAT(pe.nombre, ' ', pe.apellido) AS nombre_completo,
                        pe.correo,
                        pe.telefono,
                        pe.direccion,
                        fn_calcular_edad(pe.fecha_nacimiento) AS edad,
                        DATE(c.fecha_creacion) AS fecha_registro,
                        (SELECT COUNT(*) FROM asistencia_gimnasio a WHERE a.cedula_persona = c.cedula AND a.tipo = 'Entrada') AS total_asistencias,
                        COALESCE((
                            SELECT em.nombre 
                            FROM membresia m 
                            INNER JOIN estado_membresia em ON m.id_estado = em.id_estado 
                            WHERE m.cedula_cliente = c.cedula 
                            ORDER BY m.fecha_fin DESC LIMIT 1
                        ), 'Sin membresía') AS estado_membresia
                    FROM cliente c
                    INNER JOIN persona pe ON c.cedula = pe.cedula
                    WHERE {$where}
                    ORDER BY pe.nombre ASC, pe.apellido ASC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $sumaEdades = 0;
            $conteoEdades = 0;
            $porCiudad = [];

            foreach ($registros as $fila) {
                if ($fila['edad'] !== null) {
                    $sumaEdades += (int)$fila['edad'];
                    $conteoEdades++;
                }
                $ciudad = !empty($fila['direccion']) ? $fila['direccion'] : 'No especificada';
                $porCiudad[$ciudad] = ($porCiudad[$ciudad] ?? 0) + 1;
            }

            arsort($porCiudad);

            return [
                'exito'     => true,
                'modulo'    => 'clientes',
                'titulo'    => 'Reporte Demográfico y Actividad de Clientes',
                'filtros'   => $filtros,
                'kpis'      => [
                    'total_clientes'    => count($registros),
                    'edad_promedio'     => $conteoEdades > 0 ? round($sumaEdades / $conteoEdades, 1) : 0,
                    'total_asistencias' => array_sum(array_column($registros, 'total_asistencias'))
                ],
                'graficos'  => [
                    'clientes_por_ubicacion' => [
                        'labels' => array_slice(array_keys($porCiudad), 0, 8),
                        'values' => array_slice(array_values($porCiudad), 0, 8)
                    ]
                ],
                'columnas'  => ['Cédula', 'Cliente', 'Correo', 'Teléfono', 'Dirección', 'Edad', 'Registro', 'Asistencias', 'Membresía'],
                'registros' => $registros
            ];
        } catch (PDOException $e) {
            error_log("Error en ReporteEstadistico::reporteClientes: " . $e->getMessage());
            return $this->respuestaError('Error al generar el reporte de clientes.');
        }
    }

    /**
     * 5. REPORTE DE ASISTENCIAS Y AFLUENCIA
     */
    public function reporteAsistencias(array $filtros = []): array
    {
        try {
            [$fechaInicio, $fechaFin] = $this->resolverRangoFechas(
                $filtros['periodo'] ?? 'todos',
                $filtros['fecha_inicio'] ?? null,
                $filtros['fecha_fin'] ?? null
            );

            $condiciones = ['1=1'];
            $params = [];

            if ($fechaInicio && $fechaFin) {
                $condiciones[] = "DATE(a.fecha) BETWEEN :fecha_inicio AND :fecha_fin";
                $params['fecha_inicio'] = $fechaInicio;
                $params['fecha_fin']    = $fechaFin;
            }

            if (!empty($filtros['tipo'])) {
                $condiciones[] = "a.tipo = :tipo";
                $params['tipo'] = $filtros['tipo'];
            }

            if (!empty($filtros['franja_horaria'])) {
                switch ($filtros['franja_horaria']) {
                    case 'manana':
                        $condiciones[] = "HOUR(a.fecha) BETWEEN 5 AND 11";
                        break;
                    case 'tarde':
                        $condiciones[] = "HOUR(a.fecha) BETWEEN 12 AND 17";
                        break;
                    case 'noche':
                        $condiciones[] = "HOUR(a.fecha) BETWEEN 18 AND 23";
                        break;
                }
            }

            $where = implode(' AND ', $condiciones);

            $sql = "SELECT 
                        a.id_asistencia,
                        a.cedula_persona,
                        CONCAT(pe.nombre, ' ', pe.apellido) AS cliente,
                        a.tipo,
                        DATE(a.fecha) AS fecha_dia,
                        TIME(a.fecha) AS hora,
                        HOUR(a.fecha) AS hora_num
                    FROM asistencia_gimnasio a
                    INNER JOIN persona pe ON a.cedula_persona = pe.cedula
                    WHERE {$where}
                    ORDER BY a.fecha DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $porDia   = [];
            $porFranja = ['Mañana (05-12h)' => 0, 'Tarde (12-18h)' => 0, 'Noche (18-24h)' => 0];
            $clientesUnicos = [];

            foreach ($registros as $fila) {
                $dia = $fila['fecha_dia'];
                $porDia[$dia] = ($porDia[$dia] ?? 0) + 1;
                $clientesUnicos[$fila['cedula_persona']] = true;

                $h = (int)$fila['hora_num'];
                if ($h < 12) {
                    $porFranja['Mañana (05-12h)']++;
                } elseif ($h < 18) {
                    $porFranja['Tarde (12-18h)']++;
                } else {
                    $porFranja['Noche (18-24h)']++;
                }
            }

            ksort($porDia);

            return [
                'exito'     => true,
                'modulo'    => 'asistencias',
                'titulo'    => 'Reporte de Asistencias y Horarios Pico',
                'filtros'   => $filtros,
                'kpis'      => [
                    'total_asistencias' => count($registros),
                    'clientes_unicos'   => count($clientesUnicos),
                    'promedio_diario'   => count($porDia) > 0 ? round(count($registros) / count($porDia), 1) : 0
                ],
                'graficos'  => [
                    'asistencias_por_dia' => [
                        'labels' => array_keys($porDia),
                        'values' => array_values($porDia)
                    ],
                    'afluencia_por_turno' => [
                        'labels' => array_keys($porFranja),
                        'values' => array_values($porFranja)
                    ]
                ],
                'columnas'  => ['ID', 'Cédula', 'Cliente', 'Movimiento', 'Fecha', 'Hora'],
                'registros' => $registros
            ];
        } catch (PDOException $e) {
            error_log("Error en ReporteEstadistico::reporteAsistencias: " . $e->getMessage());
            return $this->respuestaError('Error al generar el reporte de asistencias.');
        }
    }

    /**
     * 6. REPORTE DE INVENTARIO Y VENTAS DE PRODUCTOS
     */
    public function reporteInventarioVentas(array $filtros = []): array
    {
        try {
            $condiciones = ['1=1'];
            $params = [];

            if (isset($filtros['solo_activos']) && $filtros['solo_activos'] !== '') {
                $condiciones[] = "p.activo = :activo";
                $params['activo'] = (int)$filtros['solo_activos'];
            }

            if (!empty($filtros['id_categoria'])) {
                $condiciones[] = "p.id_categoria = :id_categoria";
                $params['id_categoria'] = (int)$filtros['id_categoria'];
            }

            $estadoStock = $filtros['estado_stock'] ?? 'todos';
            if ($estadoStock === 'bajo_stock') {
                $condiciones[] = "p.stock_actual <= p.stock_minimo";
            } elseif ($estadoStock === 'agotado') {
                $condiciones[] = "p.stock_actual = 0";
            } elseif ($estadoStock === 'optimo') {
                $condiciones[] = "p.stock_actual > p.stock_minimo";
            }

            $where = implode(' AND ', $condiciones);

            $sql = "SELECT 
                        p.codigo_producto,
                        p.nombre,
                        cp.nombre AS categoria,
                        um.abreviatura AS unidad,
                        p.precio_venta,
                        p.stock_minimo,
                        p.stock_actual,
                        (p.precio_venta * p.stock_actual) AS valor_inventario,
                        COALESCE(SUM(vp.cantidad_vendida), 0) AS unidades_vendidas,
                        COALESCE(SUM(vp.cantidad_vendida * p.precio_venta), 0) AS ingresos_generados,
                        CASE WHEN p.activo = 1 THEN 'Activo' ELSE 'Inactivo' END AS estado
                    FROM producto p
                    INNER JOIN categoria_producto cp ON p.id_categoria = cp.id_categoria
                    INNER JOIN unidad_medida um ON p.id_unidad = um.id_unidad
                    LEFT JOIN venta_producto vp ON p.codigo_producto = vp.codigo_producto
                    WHERE {$where}
                    GROUP BY p.codigo_producto, p.nombre, cp.nombre, um.abreviatura, p.precio_venta, p.stock_minimo, p.stock_actual, p.activo
                    ORDER BY unidades_vendidas DESC, p.nombre ASC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $valorTotalStock = 0.0;
            $bajoStockCount  = 0;
            $porCategoria    = [];

            foreach ($registros as $fila) {
                $valorTotalStock += (float)$fila['valor_inventario'];
                if ((int)$fila['stock_actual'] <= (int)$fila['stock_minimo']) {
                    $bajoStockCount++;
                }
                $cat = $fila['categoria'];
                $porCategoria[$cat] = ($porCategoria[$cat] ?? 0) + (int)$fila['stock_actual'];
            }

            return [
                'exito'     => true,
                'modulo'    => 'inventario_ventas',
                'titulo'    => 'Reporte de Inventario y Rotación de Productos',
                'filtros'   => $filtros,
                'kpis'      => [
                    'total_productos'    => count($registros),
                    'valor_total_stock'  => round($valorTotalStock, 2),
                    'productos_bajo_stock' => $bajoStockCount,
                    'unidades_vendidas'  => array_sum(array_column($registros, 'unidades_vendidas'))
                ],
                'graficos'  => [
                    'stock_por_categoria' => [
                        'labels' => array_keys($porCategoria),
                        'values' => array_values($porCategoria)
                    ]
                ],
                'columnas'  => ['Código', 'Producto', 'Categoría', 'Unidad', 'Precio ($)', 'Mínimo', 'Actual', 'Valor Stock ($)', 'Vendidos', 'Ingresos ($)', 'Estado'],
                'registros' => $registros
            ];
        } catch (PDOException $e) {
            error_log("Error en ReporteEstadistico::reporteInventarioVentas: " . $e->getMessage());
            return $this->respuestaError('Error al generar el reporte de inventario.');
        }
    }

    /**
     * 7. REPORTE DE CLASES GRUPALES Y OCUPACIÓN
     */
    public function reporteClases(array $filtros = []): array
    {
        try {
            [$fechaInicio, $fechaFin] = $this->resolverRangoFechas(
                $filtros['periodo'] ?? 'todos',
                $filtros['fecha_inicio'] ?? null,
                $filtros['fecha_fin'] ?? null
            );

            $condiciones = ['1=1'];
            $params = [];

            if ($fechaInicio && $fechaFin) {
                $condiciones[] = "DATE(c.fecha_inicio) BETWEEN :fecha_inicio AND :fecha_fin";
                $params['fecha_inicio'] = $fechaInicio;
                $params['fecha_fin']    = $fechaFin;
            }

            if (!empty($filtros['estado'])) {
                $condiciones[] = "c.estado = :estado";
                $params['estado'] = $filtros['estado'];
            }

            if (!empty($filtros['cedula_trabajador'])) {
                $condiciones[] = "c.cedula_trabajador = :cedula_trabajador";
                $params['cedula_trabajador'] = $filtros['cedula_trabajador'];
            }

            $where = implode(' AND ', $condiciones);

            $sql = "SELECT 
                        c.id_clase,
                        c.nombre AS clase,
                        CONCAT(pe.nombre, ' ', pe.apellido) AS entrenador,
                        c.estado,
                        c.fecha_inicio,
                        c.fecha_fin,
                        c.capacidad_maxima,
                        COUNT(cc.cedula_cliente) AS inscritos,
                        fn_cupos_disponibles(c.id_clase) AS cupos_disponibles,
                        ROUND((COUNT(cc.cedula_cliente) / NULLIF(c.capacidad_maxima, 0)) * 100, 1) AS porcentaje_ocupacion
                    FROM clase c
                    INNER JOIN persona pe ON c.cedula_trabajador = pe.cedula
                    LEFT JOIN clase_cliente cc ON c.id_clase = cc.id_clase
                    WHERE {$where}
                    GROUP BY c.id_clase, c.nombre, pe.nombre, pe.apellido, c.estado, c.fecha_inicio, c.fecha_fin, c.capacidad_maxima
                    ORDER BY c.fecha_inicio DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $porEstado = [];
            foreach ($registros as $fila) {
                $est = $fila['estado'];
                $porEstado[$est] = ($porEstado[$est] ?? 0) + 1;
            }

            return [
                'exito'     => true,
                'modulo'    => 'clases',
                'titulo'    => 'Reporte de Clases Grupales y Ocupación',
                'filtros'   => $filtros,
                'kpis'      => [
                    'total_clases'    => count($registros),
                    'total_inscritos' => array_sum(array_column($registros, 'inscritos')),
                    'ocupacion_media' => count($registros) > 0 ? round(array_sum(array_column($registros, 'porcentaje_ocupacion')) / count($registros), 1) : 0
                ],
                'graficos'  => [
                    'clases_por_estado' => [
                        'labels' => array_keys($porEstado),
                        'values' => array_values($porEstado)
                    ]
                ],
                'columnas'  => ['ID', 'Clase', 'Entrenador', 'Estado', 'Inicio', 'Fin', 'Capacidad', 'Inscritos', 'Disponibles', 'Ocupación (%)'],
                'registros' => $registros
            ];
        } catch (PDOException $e) {
            error_log("Error en ReporteEstadistico::reporteClases: " . $e->getMessage());
            return $this->respuestaError('Error al generar el reporte de clases.');
        }
    }

    /**
     * 8. REPORTE DE EQUIPOS Y MANTENIMIENTO (Usa vista `vw_equipos_resumen`)
     */
    public function reporteEquipos(array $filtros = []): array
    {
        try {
            $condiciones = ['e.activo = 1'];
            $params = [];

            if (!empty($filtros['estado'])) {
                $condiciones[] = "e.estado = :estado";
                $params['estado'] = $filtros['estado'];
            }

            if (!empty($filtros['tipo_mantenimiento'])) {
                $condiciones[] = "EXISTS (SELECT 1 FROM mantenimiento_equipo me WHERE me.codigo_equipo = e.codigo_equipo AND me.tipo = :tipo_mant)";
                $params['tipo_mant'] = $filtros['tipo_mantenimiento'];
            }

            $where = implode(' AND ', $condiciones);

            $sql = "SELECT 
                        e.codigo_equipo,
                        e.nombre,
                        e.tipo,
                        e.estado,
                        e.ubicacion,
                        e.total_mantenimientos,
                        COALESCE(e.ultimo_mantenimiento, 'Sin registros') AS ultimo_mantenimiento,
                        e.costo_total_mantenimiento
                    FROM vw_equipos_resumen e
                    WHERE {$where}
                    ORDER BY e.costo_total_mantenimiento DESC, e.nombre ASC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $porEstado = [];
            foreach ($registros as $fila) {
                $est = $fila['estado'];
                $porEstado[$est] = ($porEstado[$est] ?? 0) + 1;
            }

            return [
                'exito'     => true,
                'modulo'    => 'equipos',
                'titulo'    => 'Reporte de Equipos y Costos de Mantenimiento',
                'filtros'   => $filtros,
                'kpis'      => [
                    'total_equipos'        => count($registros),
                    'total_mantenimientos' => array_sum(array_column($registros, 'total_mantenimientos')),
                    'costo_total'          => round(array_sum(array_column($registros, 'costo_total_mantenimiento')), 2)
                ],
                'graficos'  => [
                    'equipos_por_estado' => [
                        'labels' => array_keys($porEstado),
                        'values' => array_values($porEstado)
                    ]
                ],
                'columnas'  => ['Código', 'Equipo', 'Tipo', 'Estado', 'Ubicación', 'Mantenimientos', 'Último Mant.', 'Costo Total ($)'],
                'registros' => $registros
            ];
        } catch (PDOException $e) {
            error_log("Error en ReporteEstadistico::reporteEquipos: " . $e->getMessage());
            return $this->respuestaError('Error al generar el reporte de equipos.');
        }
    }

    /**
     * 9. REPORTE DE RUTINAS Y PROGRESO
     */
    public function reporteRutinas(array $filtros = []): array
    {
        try {
            $condiciones = ['1=1'];
            $params = [];

            if (!empty($filtros['id_dificultad'])) {
                $condiciones[] = "r.id_dificultad = :id_dificultad";
                $params['id_dificultad'] = (int)$filtros['id_dificultad'];
            }

            if (!empty($filtros['estado'])) {
                $condiciones[] = "ra.estado = :estado";
                $params['estado'] = $filtros['estado'];
            }

            $where = implode(' AND ', $condiciones);

            $sql = "SELECT 
                        ra.id_asignacion,
                        ra.cedula_cliente,
                        CONCAT(pe.nombre, ' ', pe.apellido) AS cliente,
                        r.nombre AS rutina,
                        td.nombre AS dificultad,
                        r.duracion_semanas,
                        ra.fecha_inicio,
                        ra.fecha_fin,
                        ra.estado,
                        ra.progreso
                    FROM rutina_asignada ra
                    INNER JOIN rutina r ON ra.id_rutina = r.id_rutina
                    INNER JOIN tipo_dificultad td ON r.id_dificultad = td.id_dificultad
                    INNER JOIN persona pe ON ra.cedula_cliente = pe.cedula
                    WHERE {$where}
                    ORDER BY ra.fecha_asignacion DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $porDificultad = [];
            foreach ($registros as $fila) {
                $dif = $fila['dificultad'];
                $porDificultad[$dif] = ($porDificultad[$dif] ?? 0) + 1;
            }

            return [
                'exito'     => true,
                'modulo'    => 'rutinas',
                'titulo'    => 'Reporte de Rutinas Asignadas y Progreso',
                'filtros'   => $filtros,
                'kpis'      => [
                    'rutinas_asignadas' => count($registros),
                    'progreso_promedio' => count($registros) > 0 ? round(array_sum(array_column($registros, 'progreso')) / count($registros), 1) : 0
                ],
                'graficos'  => [
                    'rutinas_por_dificultad' => [
                        'labels' => array_keys($porDificultad),
                        'values' => array_values($porDificultad)
                    ]
                ],
                'columnas'  => ['ID', 'Cédula', 'Cliente', 'Rutina', 'Dificultad', 'Semanas', 'Inicio', 'Fin', 'Estado', 'Progreso (%)'],
                'registros' => $registros
            ];
        } catch (PDOException $e) {
            error_log("Error en ReporteEstadistico::reporteRutinas: " . $e->getMessage());
            return $this->respuestaError('Error al generar el reporte de rutinas.');
        }
    }

    /**
     * 10. REPORTE DE TRABAJADORES Y NÓMINA
     */
    public function reporteTrabajadores(array $filtros = []): array
    {
        try {
            $condiciones = ['1=1'];
            $params = [];

            if (!empty($filtros['id_rol'])) {
                $condiciones[] = "t.id_rol = :id_rol";
                $params['id_rol'] = (int)$filtros['id_rol'];
            }

            if (isset($filtros['activo']) && $filtros['activo'] !== '') {
                $condiciones[] = "pe.activo = :activo";
                $params['activo'] = (int)$filtros['activo'];
            }

            $where = implode(' AND ', $condiciones);

            $sql = "SELECT 
                        t.cedula,
                        CONCAT(pe.nombre, ' ', pe.apellido) AS trabajador,
                        rt.nombre AS rol,
                        pe.correo,
                        pe.telefono,
                        t.fecha_contratacion,
                        COALESCE(t.salario, 0) AS salario,
                        (SELECT COUNT(*) FROM clase c WHERE c.cedula_trabajador = t.cedula) AS clases_impartidas,
                        (SELECT COUNT(*) FROM rutina_asignada ra WHERE ra.asignado_por = t.cedula) AS rutinas_asignadas
                    FROM trabajador t
                    INNER JOIN persona pe ON t.cedula = pe.cedula
                    INNER JOIN rol_trabajador rt ON t.id_rol = rt.id_rol
                    WHERE {$where}
                    ORDER BY rt.id_rol ASC, pe.nombre ASC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $porRol = [];
            foreach ($registros as $fila) {
                $rol = $fila['rol'];
                $porRol[$rol] = ($porRol[$rol] ?? 0) + 1;
            }

            return [
                'exito'     => true,
                'modulo'    => 'trabajadores',
                'titulo'    => 'Reporte de Personal, Roles y Nómina',
                'filtros'   => $filtros,
                'kpis'      => [
                    'total_trabajadores' => count($registros),
                    'nomina_total'       => round(array_sum(array_column($registros, 'salario')), 2)
                ],
                'graficos'  => [
                    'personal_por_rol' => [
                        'labels' => array_keys($porRol),
                        'values' => array_values($porRol)
                    ]
                ],
                'columnas'  => ['Cédula', 'Trabajador', 'Rol', 'Correo', 'Teléfono', 'Contratación', 'Salario ($)', 'Clases', 'Rutinas'],
                'registros' => $registros
            ];
        } catch (PDOException $e) {
            error_log("Error en ReporteEstadistico::reporteTrabajadores: " . $e->getMessage());
            return $this->respuestaError('Error al generar el reporte de trabajadores.');
        }
    }

    // =========================================================================
    // MÉTODOS AUXILIARES PRIVADOS
    // =========================================================================

    /**
     * Obtiene las listas maestras reales de la BD para llenar los filtros predeterminados.
     */
    private function obtenerCatalogosBD(): array
    {
        $catalogos = [
            'metodos_pago'        => [],
            'tipos_membresia'     => [],
            'estados_membresia'   => [],
            'categorias_producto' => [],
            'roles_trabajador'    => [],
            'dificultades'        => [],
            'entrenadores'        => []
        ];

        try {
            $catalogos['metodos_pago']        = $this->pdo->query("SELECT id_metodo, nombre FROM metodo_pago ORDER BY nombre")->fetchAll(PDO::FETCH_KEY_PAIR);
            $catalogos['tipos_membresia']     = $this->pdo->query("SELECT id_tipo, nombre FROM tipo_membresia ORDER BY id_tipo")->fetchAll(PDO::FETCH_KEY_PAIR);
            $catalogos['estados_membresia']   = $this->pdo->query("SELECT id_estado, nombre FROM estado_membresia ORDER BY id_estado")->fetchAll(PDO::FETCH_KEY_PAIR);
            $catalogos['categorias_producto'] = $this->pdo->query("SELECT id_categoria, nombre FROM categoria_producto ORDER BY nombre")->fetchAll(PDO::FETCH_KEY_PAIR);
            $catalogos['roles_trabajador']    = $this->pdo->query("SELECT id_rol, nombre FROM rol_trabajador ORDER BY id_rol")->fetchAll(PDO::FETCH_KEY_PAIR);
            $catalogos['dificultades']        = $this->pdo->query("SELECT id_dificultad, nombre FROM tipo_dificultad ORDER BY id_dificultad")->fetchAll(PDO::FETCH_KEY_PAIR);
            $catalogos['entrenadores']        = $this->pdo->query(
                "SELECT t.cedula, CONCAT(p.nombre, ' ', p.apellido) 
                 FROM trabajador t INNER JOIN persona p ON t.cedula = p.cedula 
                 WHERE t.id_rol = 2 AND p.activo = 1 ORDER BY p.nombre"
            )->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) {
            error_log("Error en ReporteEstadistico::obtenerCatalogosBD: " . $e->getMessage());
        }

        return $catalogos;
    }

    /**
     * Traduce un preset de periodo ('hoy', 'semana', 'mes_actual', etc.) a fechas Y-m-d.
     */
    private function resolverRangoFechas(string $periodo, ?string $inicioCustom = null, ?string $finCustom = null): array
    {
        $hoy = new DateTime();

        return match ($periodo) {
            'hoy'          => [$hoy->format('Y-m-d'), $hoy->format('Y-m-d')],
            'semana'       => [(clone $hoy)->modify('-7 days')->format('Y-m-d'), $hoy->format('Y-m-d')],
            'mes_actual'   => [$hoy->format('Y-m-01'), $hoy->format('Y-m-t')],
            'mes_anterior' => [(clone $hoy)->modify('first day of last month')->format('Y-m-d'), (clone $hoy)->modify('last day of last month')->format('Y-m-d')],
            'anio_actual'  => [$hoy->format('Y-01-01'), $hoy->format('Y-12-31')],
            'personalizado'=> [$inicioCustom ?: null, $finCustom ?: null],
            default        => [null, null]
        };
    }

    /**
     * Convierte un array asociativo plano en filas legibles para tablas de resumen.
     */
    private function formatearClaveValor(array $datos): array
    {
        $filas = [];
        foreach ($datos as $clave => $valor) {
            $etiqueta = ucfirst(str_replace('_', ' ', $clave));
            $filas[] = [
                'indicador' => $etiqueta,
                'valor'     => $valor
            ];
        }
        return $filas;
    }

    /**
     * Estructura estándar en caso de error SQL.
     */
    private function respuestaError(string $mensaje): array
    {
        return [
            'exito'     => false,
            'mensaje'   => $mensaje,
            'kpis'      => [],
            'graficos'  => [],
            'columnas'  => [],
            'registros' => []
        ];
    }
}