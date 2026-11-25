document.addEventListener('DOMContentLoaded', function() {

    // ==================== CONFIGURACIÓN INICIAL HIDRATADA DESDE PHP ====================
    const APP_DATA           = window.REPORTES_APP || {};
    const definicionReportes = APP_DATA.definicion || {};
    const etiquetasFiltros   = APP_DATA.etiquetasFiltros || {};
    const metaKpis           = APP_DATA.metaKpis || {};
    let moduloActual         = APP_DATA.moduloActual || 'resumen_general';

    // Registro de instancias activas de Chart.js para destruirlas antes de redibujar
    let instanciasGraficos = {};

    // Paleta institucional SOFIT GYM para gráficos estadísticos
    const PALETA_COLORES = [
        '#C62828', // Rojo principal Sofit Gym
        '#1E293B', // Pizarra oscuro
        '#2E7D32', // Verde esmeralda
        '#EF6C00', // Naranja ámbar
        '#1565C0', // Azul cobalto
        '#6A1B9A', // Púrpura profundo
        '#00838F', // Cian oscuro
        '#AD1457', // Magenta
        '#4E342E', // Marrón cálido
        '#37474F'  // Gris acero
    ];

    // ==================== ELEMENTOS DE LA INTERFAZ ====================
    const formFiltros        = document.getElementById('formFiltrosReporte');
    const selectModulo       = document.getElementById('selectModuloReporte');
    const navModulos         = document.getElementById('navModulosReporte');
    const descripcionModulo  = document.getElementById('descripcionModuloActual');
    const contenedorFiltros  = document.getElementById('contenedorFiltrosDinamicos');
    const rangoFechasCustom  = document.getElementById('rangoFechasPersonalizado');
    const inputFechaInicio   = document.getElementById('filtroFechaInicio');
    const inputFechaFin      = document.getElementById('filtroFechaFin');
    const btnResetear        = document.getElementById('btnResetearFiltros');
    const btnAplicar         = document.getElementById('btnAplicarFiltros');
    const btnExportarCsv     = document.getElementById('btnExportarCsv');
    const indicadorCargando  = document.getElementById('indicadorCargando');

    const contenedorKpis     = document.getElementById('contenedorKpis');
    const seccionGraficos    = document.getElementById('seccionGraficos');
    const contenedorGraficos = document.getElementById('contenedorGraficos');
    const tituloTabla        = document.getElementById('tituloTablaReporte');
    const contadorBadge      = document.getElementById('contadorRegistrosBadge');
    const buscarTablaInput   = document.getElementById('buscarRegistroTabla');
    const tablaHead          = document.getElementById('tablaReporteHead');
    const tablaBody          = document.getElementById('tablaReporteBody');

    // Obtener parámetro 'page' actual de la URL (por defecto 'reporteEstadistico')
    const urlParamsActuales = new URLSearchParams(window.location.search);
    const paginaControlador = urlParamsActuales.get('page') || 'reporteEstadistico';

    // ==================== TOAST NOTIFICACIONES (MISMO PATRÓN QUE PRODUCTOS.JS) ====================
    function showMessage(message, type = 'success') {
        const toast = document.getElementById('toastMessage');
        if (!toast) return;

        toast.textContent = message;
        toast.className = '';
        toast.classList.add(type);

        if (type === 'error') {
            toast.style.background = '#C62828';
        } else if (type === 'warning') {
            toast.style.background = '#ED6C02';
        } else {
            toast.style.background = '#2E7D32';
        }

        toast.style.display = 'block';
        setTimeout(() => {
            toast.style.display = 'none';
        }, 3200);
    }

    // ==================== GESTIÓN DE MÓDULOS Y FILTROS DINÁMICOS ====================

    /**
     * Cambia el módulo de reporte activo, sincroniza las píldoras y el select,
     * reconstruye los filtros predeterminados y consulta los nuevos datos.
     */
    function seleccionarModulo(nuevoModulo) {
        if (!definicionReportes[nuevoModulo]) return;

        moduloActual = nuevoModulo;

        // 1. Sincronizar el <select> principal
        if (selectModulo && selectModulo.value !== nuevoModulo) {
            selectModulo.value = nuevoModulo;
        }

        // 2. Sincronizar las píldoras horizontales
        if (navModulos) {
            navModulos.querySelectorAll('.modulo-pill').forEach(pill => {
                if (pill.getAttribute('data-modulo') === nuevoModulo) {
                    pill.classList.add('active');
                    pill.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                } else {
                    pill.classList.remove('active');
                }
            });
        }

        // 3. Actualizar descripción contextual
        const infoModulo = definicionReportes[nuevoModulo];
        if (descripcionModulo) {
            descripcionModulo.textContent = infoModulo.descripcion || '';
        }

        // 4. Renderizar los filtros predeterminados del nuevo módulo
        renderizarFiltrosModulo(nuevoModulo);

        // 5. Ejecutar consulta inmediata del nuevo reporte
        consultarReporte();
    }

    /**
     * Construye dinámicamente los <select> de filtros predeterminados para un módulo.
     */
    function renderizarFiltrosModulo(modulo) {
        if (!contenedorFiltros) return;

        const infoModulo = definicionReportes[modulo] || {};
        const filtros    = infoModulo.filtros || {};
        const claves     = Object.keys(filtros);

        // Reiniciar fechas personalizadas al cambiar de módulo
        if (inputFechaInicio) inputFechaInicio.value = '';
        if (inputFechaFin) inputFechaFin.value = '';

        if (claves.length === 0) {
            contenedorFiltros.innerHTML = `
                <div class="text-muted small py-2">
                    <i class="fas fa-info-circle me-1"></i> Este reporte muestra los indicadores globales consolidados en tiempo real sin necesidad de filtros adicionales.
                </div>
            `;
            actualizarVisibilidadFechasCustom();
            return;
        }

        let html = '';
        claves.forEach(nombreFiltro => {
            const opciones = filtros[nombreFiltro] || {};
            const etiqueta = etiquetasFiltros[nombreFiltro] || formatearTituloClave(nombreFiltro);

            let opcionesHtml = '';
            Object.entries(opciones).forEach(([valorOp, textoOp]) => {
                opcionesHtml += `<option value="${escapeHtml(valorOp)}">${escapeHtml(textoOp)}</option>`;
            });

            html += `
                <div class="form-group-custom">
                    <label for="filtro_${escapeHtml(nombreFiltro)}">${escapeHtml(etiqueta)}</label>
                    <select id="filtro_${escapeHtml(nombreFiltro)}"
                            name="${escapeHtml(nombreFiltro)}"
                            class="form-select select-custom filtro-dinamico">
                        ${opcionesHtml}
                    </select>
                </div>
            `;
        });

        contenedorFiltros.innerHTML = html;
        asignarEventosFiltrosDinamicos();
        actualizarVisibilidadFechasCustom();
    }

    /**
     * Muestra u oculta el bloque de fechas 'Desde / Hasta' según si 'periodo' === 'personalizado'.
     */
    function actualizarVisibilidadFechasCustom() {
        if (!rangoFechasCustom) return;

        const selectPeriodo = contenedorFiltros
            ? contenedorFiltros.querySelector('select[name="periodo"]')
            : null;

        if (selectPeriodo && selectPeriodo.value === 'personalizado') {
            rangoFechasCustom.style.display = 'flex';
        } else {
            rangoFechasCustom.style.display = 'none';
            if (inputFechaInicio) inputFechaInicio.value = '';
            if (inputFechaFin) inputFechaFin.value = '';
        }
    }

    /**
     * Asocia eventos 'change' a los selectores dinámicos recién renderizados.
     */
    function asignarEventosFiltrosDinamicos() {
        if (!contenedorFiltros) return;

        contenedorFiltros.querySelectorAll('.filtro-dinamico').forEach(select => {
            select.addEventListener('change', function() {
                if (this.name === 'periodo') {
                    actualizarVisibilidadFechasCustom();
                    if (this.value === 'personalizado') {
                        if (inputFechaInicio) inputFechaInicio.focus();
                        actualizarEnlaceExportacion();
                        return; // Esperar a que el usuario seleccione fechas y pulse Generar Reporte
                    }
                }
                consultarReporte();
            });
        });
    }

    // ==================== CONSTRUCCIÓN DE PARÁMETROS Y EXPORTACIÓN CSV ====================

    /**
     * Recopila el módulo actual y todos los filtros activos del formulario en un URLSearchParams.
     */
    function obtenerParametrosConsulta() {
        const params = new URLSearchParams();
        params.set('modulo', moduloActual);

        if (formFiltros) {
            const formData = new FormData(formFiltros);
            for (const [clave, valor] of formData.entries()) {
                if (clave === 'modulo') continue;
                params.set(clave, valor.toString().trim());
            }
        }

        return params;
    }

    /**
     * Mantiene sincronizado el enlace del botón "Exportar CSV" con los filtros actuales.
     */
    function actualizarEnlaceExportacion() {
        if (!btnExportarCsv) return;

        const params = obtenerParametrosConsulta();
        params.set('page', paginaControlador);
        params.set('action', 'exportarCsv');

        btnExportarCsv.href = `?${params.toString()}`;
    }

    // ==================== CONSULTA AJAX AL CONTROLADOR ====================

    /**
     * Consulta al backend (`?page=reporteEstadistico&action=consultar`) y actualiza
     * KPIs, gráficos y tabla detallada sin recargar la página.
     */
    async function consultarReporte() {
        const params = obtenerParametrosConsulta();

        // Validar rango de fechas personalizado si aplica
        if (params.get('periodo') === 'personalizado') {
            const fInicio = params.get('fecha_inicio') || '';
            const fFin    = params.get('fecha_fin') || '';

            if (!fInicio || !fFin) {
                showMessage('⚠️ Seleccione tanto la fecha inicial como la fecha final.', 'warning');
                return;
            }
            if (fInicio > fFin) {
                showMessage('⚠️ La fecha "Desde" no puede ser mayor que la fecha "Hasta".', 'error');
                return;
            }
        }

        actualizarEnlaceExportacion();
        mostrarEstadoCarga(true);

        try {
            const queryParams = new URLSearchParams(params);
            queryParams.set('page', paginaControlador);
            queryParams.set('action', 'consultar');

            const response = await fetch(`?${queryParams.toString()}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (!response.ok || !data.exito) {
                showMessage(data.mensaje || '❌ No se pudo generar el reporte estadístico.', 'error');
                return;
            }

            renderizarReporteCompleto(data);
        } catch (error) {
            console.error('Error consultando reporte estadístico:', error);
            showMessage('❌ Error de conexión al consultar los datos estadísticos.', 'error');
        } finally {
            mostrarEstadoCarga(false);
        }
    }

    function mostrarEstadoCarga(cargando) {
        if (indicadorCargando) {
            indicadorCargando.style.display = cargando ? 'flex' : 'none';
        }
        if (btnAplicar) {
            btnAplicar.disabled = cargando;
        }
    }

    // ==================== RENDERIZADO DE KPIs, GRÁFICOS Y TABLA ====================

    /**
     * Orquesta la actualización visual completa del reporte recibido por JSON.
     */
    function renderizarReporteCompleto(data) {
        renderizarKpis(data.kpis || {});
        renderizarGraficos(data.graficos || {});
        renderizarTabla(
            data.modulo || moduloActual,
            data.titulo || 'Resultados del Reporte',
            data.columnas || [],
            data.registros || []
        );
    }

    /**
     * Renderiza las tarjetas KPI aplicando formato numérico, monetario o porcentual.
     */
    function renderizarKpis(kpis) {
        if (!contenedorKpis) return;

        const entradas = Object.entries(kpis);
        if (entradas.length === 0) {
            contenedorKpis.innerHTML = '';
            return;
        }

        let html = '';
        entradas.forEach(([clave, valor]) => {
            const meta = metaKpis[clave] || {
                label: formatearTituloClave(clave),
                icon: 'fa-chart-bar',
                tipo: 'numero'
            };

            const valorFormateado = formatearValorKpi(valor, meta.tipo);

            html += `
                <div class="kpi-card">
                    <div>
                        <div class="kpi-titulo">${escapeHtml(meta.label)}</div>
                        <h3 class="kpi-valor">${escapeHtml(valorFormateado)}</h3>
                    </div>
                    <div class="kpi-icono">
                        <i class="fas ${escapeHtml(meta.icon)}"></i>
                    </div>
                </div>
            `;
        });

        contenedorKpis.innerHTML = html;
    }

    /**
     * Destruye gráficos previos y dibuja los nuevos gráficos con Chart.js.
     */
    function renderizarGraficos(graficos) {
        // Destruir todas las instancias anteriores de Chart.js
        Object.values(instanciasGraficos).forEach(chart => {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        });
        instanciasGraficos = {};

        if (!seccionGraficos || !contenedorGraficos) return;

        const clavesGraficos = Object.keys(graficos);
        if (clavesGraficos.length === 0) {
            seccionGraficos.style.display = 'none';
            contenedorGraficos.innerHTML = '';
            return;
        }

        seccionGraficos.style.display = 'block';

        // 1. Crear estructura HTML de tarjetas y canvas
        let htmlTarjetas = '';
        clavesGraficos.forEach(clave => {
            const tituloGrafico = formatearTituloClave(clave);
            htmlTarjetas += `
                <div class="card-custom mb-0">
                    <div class="card-header-custom">
                        <span><i class="fas fa-chart-area text-danger me-1"></i> ${escapeHtml(tituloGrafico)}</span>
                    </div>
                    <div class="card-body-custom">
                        <div class="chart-canvas-wrapper" id="wrapper_grafico_${escapeHtml(clave)}">
                            <canvas id="canvas_grafico_${escapeHtml(clave)}"></canvas>
                        </div>
                    </div>
                </div>
            `;
        });
        contenedorGraficos.innerHTML = htmlTarjetas;

        // 2. Instanciar Chart.js sobre cada canvas
        if (typeof Chart === 'undefined') {
            console.warn('Chart.js no está cargado en la página.');
            return;
        }

        clavesGraficos.forEach(clave => {
            const serie   = graficos[clave] || { labels: [], values: [] };
            const labels  = Array.isArray(serie.labels) ? serie.labels : [];
            const values  = Array.isArray(serie.values) ? serie.values.map(v => Number(v) || 0) : [];
            const wrapper = document.getElementById(`wrapper_grafico_${clave}`);
            const canvas  = document.getElementById(`canvas_grafico_${clave}`);

            if (!canvas || !wrapper) return;

            if (labels.length === 0 || values.length === 0) {
                wrapper.innerHTML = `
                    <div class="h-100 d-flex flex-column align-items-center justify-content-center text-muted">
                        <i class="fas fa-chart-bar fa-2x mb-2 opacity-50"></i>
                        <small>Sin datos suficientes para graficar en este rango.</small>
                    </div>
                `;
                return;
            }

            const tipoChart = determinarTipoGrafico(clave, labels.length);
            const esMoneda  = clave.includes('ingreso') || clave.includes('tendencia') || clave.includes('costo');

            const dataset = {
                label: formatearTituloClave(clave),
                data: values,
                borderWidth: tipoChart === 'line' ? 3 : 1.5,
                borderRadius: tipoChart === 'bar' ? 8 : 0
            };

            if (tipoChart === 'line') {
                dataset.borderColor = '#C62828';
                dataset.backgroundColor = 'rgba(198, 40, 40, 0.12)';
                dataset.pointBackgroundColor = '#C62828';
                dataset.pointRadius = 4;
                dataset.tension = 0.32;
                dataset.fill = true;
            } else {
                dataset.backgroundColor = labels.map((_, idx) => PALETA_COLORES[idx % PALETA_COLORES.length]);
                dataset.borderColor = tipoChart === 'doughnut' ? '#ffffff' : dataset.backgroundColor;
            }

            instanciasGraficos[clave] = new Chart(canvas.getContext('2d'), {
                type: tipoChart,
                data: {
                    labels: labels,
                    datasets: [dataset]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: tipoChart === 'doughnut',
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 14,
                                font: { size: 12 }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const val = context.parsed.y !== undefined ? context.parsed.y : context.parsed;
                                    const numFormateado = esMoneda
                                        ? '$' + Number(val).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                                        : Number(val).toLocaleString('es-VE');
                                    return ` ${context.label}: ${numFormateado}`;
                                }
                            }
                        }
                    },
                    scales: tipoChart === 'doughnut' ? {} : {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: {
                                callback: function(value) {
                                    return esMoneda ? '$' + value : value;
                                }
                            }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        });
    }

    /**
     * Renderiza los encabezados y filas de la tabla de resultados detallados.
     */
    function renderizarTabla(modulo, titulo, columnas, registros) {
        if (tituloTabla) {
            tituloTabla.innerHTML = `<i class="fas fa-table text-danger me-1"></i> ${escapeHtml(titulo)}`;
        }

        if (contadorBadge) {
            contadorBadge.textContent = `${registros.length} Registros`;
        }

        if (tablaHead) {
            let headHtml = '<tr>';
            columnas.forEach(col => {
                headHtml += `<th>${escapeHtml(col)}</th>`;
            });
            headHtml += '</tr>';
            tablaHead.innerHTML = headHtml;
        }

        if (!tablaBody) return;

        if (!registros || registros.length === 0) {
            const colspan = Math.max(1, columnas.length);
            tablaBody.innerHTML = `
                <tr>
                    <td colspan="${colspan}" class="text-center py-4 text-muted">
                        <i class="fas fa-folder-open fa-2x mb-2 d-block"></i>
                        No se encontraron registros para los filtros seleccionados.
                    </td>
                </tr>
            `;
            return;
        }

        let bodyHtml = '';
        registros.forEach(fila => {
            bodyHtml += '<tr>';
            Object.entries(fila).forEach(([campo, valor]) => {
                // Ocultar columna auxiliar interna 'hora_num' en el módulo de asistencias
                if (modulo === 'asistencias' && campo === 'hora_num') {
                    return;
                }
                bodyHtml += `<td>${formatearCeldaTabla(campo, valor)}</td>`;
            });
            bodyHtml += '</tr>';
        });

        tablaBody.innerHTML = bodyHtml;

        // Reaplicar filtro de búsqueda local si el usuario tiene texto escrito
        if (buscarTablaInput && buscarTablaInput.value.trim() !== '') {
            buscarTablaInput.dispatchEvent(new Event('input'));
        }
    }

    // ==================== BUSCADOR LOCAL EN TABLA (IGUAL A PRODUCTOS.JS) ====================
    if (buscarTablaInput) {
        buscarTablaInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            if (!tablaBody) return;

            const rows = tablaBody.querySelectorAll('tr');
            let totalFilasDatos = 0;
            let filasVisibles   = 0;

            rows.forEach(row => {
                // Ignorar filas informativas vacías de 1 sola columna
                if (row.cells.length === 1) return;

                totalFilasDatos++;
                const rowText = row.textContent.toLowerCase();
                if (rowText.includes(searchTerm)) {
                    row.style.display = '';
                    filasVisibles++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (contadorBadge && totalFilasDatos > 0) {
                contadorBadge.textContent = searchTerm !== ''
                    ? `${filasVisibles} de ${totalFilasDatos} Registros`
                    : `${totalFilasDatos} Registros`;
            }
        });
    }

    // ==================== EVENTOS PRINCIPALES DE NAVEGACIÓN Y FORMULARIO ====================

    // Clic en píldoras rápidas de módulos
    if (navModulos) {
        navModulos.querySelectorAll('.modulo-pill').forEach(pill => {
            pill.addEventListener('click', function() {
                const moduloSeleccionado = this.getAttribute('data-modulo');
                if (moduloSeleccionado && moduloSeleccionado !== moduloActual) {
                    seleccionarModulo(moduloSeleccionado);
                }
            });
        });
    }

    // Cambio en el <select> de módulo
    if (selectModulo) {
        selectModulo.addEventListener('change', function() {
            seleccionarModulo(this.value);
        });
    }

    // Envío del formulario de filtros ("Generar Reporte")
    if (formFiltros) {
        formFiltros.addEventListener('submit', function(e) {
            e.preventDefault();
            consultarReporte();
        });
    }

    // Botón "Restablecer Filtros"
    if (btnResetear) {
        btnResetear.addEventListener('click', function() {
            if (buscarTablaInput) buscarTablaInput.value = '';
            renderizarFiltrosModulo(moduloActual);
            consultarReporte();
            showMessage('Filtros restablecidos a sus valores predeterminados.', 'success');
        });
    }

    // ==================== FUNCIONES AUXILIARES DE FORMATO ====================

    function determinarTipoGrafico(claveGrafico, cantidadItems) {
        if (claveGrafico.includes('tendencia') || claveGrafico.includes('por_dia')) {
            return 'line';
        }
        if (
            cantidadItems <= 5 &&
            (claveGrafico.includes('estado') ||
             claveGrafico.includes('turno') ||
             claveGrafico.includes('rol') ||
             claveGrafico.includes('dificultad') ||
             claveGrafico === 'membresias' ||
             claveGrafico === 'equipos')
        ) {
            return 'doughnut';
        }
        return 'bar';
    }

    function formatearValorKpi(valor, tipo) {
        const numero = Number(valor);
        if (tipo === 'moneda' && !isNaN(numero)) {
            return '$' + numero.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        if (tipo === 'porcentaje' && !isNaN(numero)) {
            return numero.toFixed(1) + '%';
        }
        if (!isNaN(numero)) {
            return numero.toLocaleString('en-US', { maximumFractionDigits: 1 });
        }
        return valor !== null && valor !== undefined ? String(valor) : '0';
    }

    function formatearCeldaTabla(campo, valor) {
        if (valor === null || valor === undefined || valor === '') {
            return '<span class="text-muted">—</span>';
        }

        const texto = String(valor);
        const textoEscapado = escapeHtml(texto);

        // Resaltar estados con badges visuales manteniendo el texto buscable
        if (campo === 'estado' || campo === 'estado_membresia' || campo === 'tipo') {
            const valLower = texto.toLowerCase();
            if (['activo', 'pagado', 'operativo', 'completada', 'entrada'].includes(valLower)) {
                return `<span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-2 py-1">${textoEscapado}</span>`;
            }
            if (['pendiente', 'en mantenimiento', 'en curso', 'programado'].includes(valLower)) {
                return `<span class="badge bg-warning bg-opacity-10 text-dark border border-warning-subtle px-2 py-1">${textoEscapado}</span>`;
            }
            if (['vencido', 'moroso', 'atrasado', 'inactivo', 'cancelado', 'cancelada', 'fuera de servicio', 'salida'].includes(valLower)) {
                return `<span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-2 py-1">${textoEscapado}</span>`;
            }
        }

        // Formatear columnas monetarias
        if (['monto', 'tarifa_plan', 'precio_venta', 'valor_inventario', 'ingresos_generados', 'costo_total_mantenimiento', 'salario'].includes(campo)) {
            const num = Number(valor);
            if (!isNaN(num)) {
                return `<strong>$${num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>`;
            }
        }

        return textoEscapado;
    }

    function formatearTituloClave(clave) {
        return clave
            .replace(/_/g, ' ')
            .replace(/\b\w/g, letra => letra.toUpperCase());
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return str.toString().replace(/[&<>"]/g, m => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;'
        }[m] || m));
    }

    // ==================== INICIALIZACIÓN EN CARGA DE PÁGINA ====================
    asignarEventosFiltrosDinamicos();
    actualizarVisibilidadFechasCustom();
    actualizarEnlaceExportacion();

    // Renderizar gráficos iniciales provenientes de $reporteInicial
    if (APP_DATA.reporteInicial && APP_DATA.reporteInicial.graficos) {
        renderizarGraficos(APP_DATA.reporteInicial.graficos);
    }
});