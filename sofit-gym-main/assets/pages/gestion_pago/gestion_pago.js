console.log('✅ gestion_pago.js cargado correctamente');

// ============================================================
// HELPERS Y FUNCIONES GLOBALES
// ============================================================
window.escapeHtmlG = function (str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, function (m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m] || m;
    });
};

window.colorEstadoG = function (estado) {
    return ({
        'Activo': 'estado-activo', 'Próximo a vencer': 'estado-proximo',
        'Vencido': 'estado-vencido', 'Moroso': 'badge-atrasado', 'Sin membresía': 'badge-pendiente'
    })[estado] || 'estado-vencido';
};

window._abrirModalAnidado = function (idModal) {
    var el = document.getElementById(idModal);
    if (!el) return null;
    document.body.classList.add('modal-anidado-abierto');
    var instance = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el, { backdrop: 'static', keyboard: false, focus: false });
    el.addEventListener('shown.bs.modal', function () {
        var backdrops = document.querySelectorAll('.modal-backdrop');
        if (backdrops.length > 0) { 
            var ultimo = backdrops[backdrops.length - 1]; 
            ultimo.classList.add('modal-anidado-backdrop'); 
            ultimo.style.zIndex = '1085'; 
        }
        el.style.zIndex = '1090';
    }, { once: true });
    el.addEventListener('hidden.bs.modal', function () {
        document.body.classList.remove('modal-anidado-abierto');
        document.querySelectorAll('.modal-backdrop.modal-anidado-backdrop').forEach(function (bd) { bd.remove(); });
    }, { once: true });
    instance.show();
    return instance;
};

// ============================================================
// PAGINACIÓN HISTORIAL DE PAGOS
// ============================================================
window._historialFilasCache = [];
window._paginaHistorialActual = 1;
window._filasPorPaginaHistorial = 10;

window.renderizarPaginacionHistorial = function () {
    var cont = document.getElementById('paginacionHistorial');
    if (!cont) return;
    var totalItems = window._historialFilasCache.length;
    var porPagina = window._filasPorPaginaHistorial;
    var totalPaginas = Math.ceil(totalItems / porPagina);

    if (totalPaginas <= 1) {
        cont.innerHTML = '';
        return;
    }

    if (window._paginaHistorialActual > totalPaginas) window._paginaHistorialActual = totalPaginas;
    if (window._paginaHistorialActual < 1) window._paginaHistorialActual = 1;

    var actual = window._paginaHistorialActual;
    var html = '';

    html += '<button type="button" class="page-btn' + (actual === 1 ? ' disabled' : '') + '" data-page="' + (actual - 1) + '"' + (actual === 1 ? ' disabled' : '') + '>Anterior</button>';

    function agregarNumero(p) {
        html += '<button type="button" class="page-btn' + (p === actual ? ' active' : '') + '" data-page="' + p + '">' + p + '</button>';
    }
    function agregarPuntos() {
        html += '<span class="page-btn dots">...</span>';
    }

    if (totalPaginas <= 6) {
        for (var i = 1; i <= totalPaginas; i++) agregarNumero(i);
    } else if (actual <= 3) {
        agregarNumero(1);
        agregarNumero(2);
        agregarNumero(3);
        agregarPuntos();
        agregarNumero(totalPaginas);
    } else if (actual >= totalPaginas - 2) {
        agregarNumero(1);
        agregarPuntos();
        for (var j = totalPaginas - 2; j <= totalPaginas; j++) agregarNumero(j);
    } else {
        agregarNumero(1);
        agregarPuntos();
        agregarNumero(actual);
        agregarPuntos();
        agregarNumero(totalPaginas);
    }

    html += '<button type="button" class="page-btn' + (actual === totalPaginas ? ' disabled' : '') + '" data-page="' + (actual + 1) + '"' + (actual === totalPaginas ? ' disabled' : '') + '>Siguiente</button>';

    cont.innerHTML = html;
};

window.mostrarPaginaHistorial = function (pagina) {
    var tablaBody = document.getElementById('tablaPagosBody');
    if (!tablaBody) return;
    window._paginaHistorialActual = pagina;

    var filas = window._historialFilasCache;
    var porPagina = window._filasPorPaginaHistorial;
    var inicio = (pagina - 1) * porPagina;
    var fin = inicio + porPagina;

    tablaBody.innerHTML = '';
    if (!filas.length) {
        tablaBody.innerHTML = '<tr><td colspan="9" class="text-center">No se encontraron pagos.</td></tr>';
        window.renderizarPaginacionHistorial();
        return;
    }

    filas.slice(inicio, fin).forEach(function (tr) {
        tablaBody.appendChild(tr);
    });

    window.renderizarPaginacionHistorial();
};

window.setDatosHistorialPaginado = function (filasOrHtmlList, resetPage) {
    if (resetPage !== false) window._paginaHistorialActual = 1;
    if (Array.isArray(filasOrHtmlList) && filasOrHtmlList.length > 0 && typeof filasOrHtmlList[0] === 'string') {
        var temp = document.createElement('tbody');
        temp.innerHTML = filasOrHtmlList.join('');
        window._historialFilasCache = Array.from(temp.querySelectorAll('tr'));
    } else {
        window._historialFilasCache = Array.isArray(filasOrHtmlList) ? filasOrHtmlList : [];
    }
    window.mostrarPaginaHistorial(window._paginaHistorialActual);
};

// ============================================================
// ESTADO DE CLIENTES
// ============================================================
window._estadoClientesCache = [];
window._filtroEstadoActivo = 'todos';

window.marcarFiltroActivo = function (estado) {
    document.querySelectorAll('.filtro-pill').forEach(function (b) {
        b.classList.remove('active');
        var f = b.getAttribute('data-filtro');
        if (
            (estado === 'todos' && f === 'todos') ||
            (estado === 'Activo' && f === 'Activo') ||
            (estado === 'Próximo a vencer' && f === 'Proximo') ||
            (estado === 'Moroso' && f === 'Moroso') ||
            (estado === 'Sin membresía' && f === 'Sin')
        ) {
            b.classList.add('active');
        }
    });
    document.querySelectorAll('.estado-card').forEach(function (card) {
        card.classList.remove('active');
        if (card.getAttribute('data-estado') === estado) card.classList.add('active');
    });
};

window.cargarEstadoClientes = function () {
    var body = document.getElementById('tablaEstadoClientesBody');
    if (!body) return;
    body.innerHTML = '<tr><td colspan="10" class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>';
    var search = document.getElementById('searchEstadoClientes'), desde = document.getElementById('estadoDesde'), hasta = document.getElementById('estadoHasta');
    var qs = [];
    if (search && search.value.trim()) qs.push('termino=' + encodeURIComponent(search.value.trim()));
    if (desde && desde.value) qs.push('desde=' + encodeURIComponent(desde.value));
    if (hasta && hasta.value) qs.push('hasta=' + encodeURIComponent(hasta.value));
    var url = '?page=gestion_pago&action=estado_clientes';
    if (qs.length) url += '&' + qs.join('&');

    fetch(url).then(r => r.json()).then(data => {
        if (!data.success) { body.innerHTML = '<tr><td colspan="10" class="text-center text-danger">Error al cargar.</td></tr>'; return; }
        window._estadoClientesCache = data.clientes || [];
        var c = data.conteos || {};
        var el;
        el = document.getElementById('cnt_activo'); if (el) el.textContent = c['Activo'] || 0;
        el = document.getElementById('cnt_proximo'); if (el) el.textContent = c['Próximo a vencer'] || 0;
        el = document.getElementById('cnt_vencido'); if (el) el.textContent = c['Vencido'] || 0;
        el = document.getElementById('cnt_moroso'); if (el) el.textContent = c['Moroso'] || 0;
        el = document.getElementById('cnt_sin'); if (el) el.textContent = c['Sin membresía'] || 0;
        window.pintarEstadoClientes();
    }).catch(err => { body.innerHTML = '<tr><td colspan="10" class="text-center text-danger">Error: ' + err.message + '</td></tr>'; });
};

window.pintarEstadoClientes = function () {
    var body = document.getElementById('tablaEstadoClientesBody');
    var tabla = document.getElementById('tablaEstadoClientes');
    if (!body) return;
    var lista = window._estadoClientesCache;
    if (window._filtroEstadoActivo !== 'todos') lista = lista.filter(c => c.estado_cliente === window._filtroEstadoActivo);

    var esSoloSinMembresia = (window._filtroEstadoActivo === 'Sin membresía') ||
        (lista.length > 0 && lista.every(c => c.estado_cliente === 'Sin membresía'));
    if (tabla) tabla.classList.toggle('sin-membresia-mode', esSoloSinMembresia);

    if (!lista.length) { body.innerHTML = '<tr><td colspan="10" class="text-center text-muted py-4">Sin resultados.</td></tr>'; return; }

    function fmtFecha(f) {
        if (!f || f === '—' || f === 'null' || f === '0000-00-00') {
            return '<span class="badge bg-secondary text-white" style="font-size:0.65rem;">Nunca</span>';
        }
        var partes = String(f).split(' ')[0].split('-');
        if (partes.length === 3) return partes[2] + '/' + partes[1] + '/' + partes[0];
        return f;
    }

    var html = '';
    lista.forEach(c => {
        var d = parseInt(c.dias_restantes, 10), dHtml = '—';
        if (c.estado_cliente === 'Sin membresía' || isNaN(d)) dHtml = '';
        else if (d < 0) dHtml = '<span style="color:#c62828;font-weight:700;">Vencido hace ' + Math.abs(d) + 'd</span>';
        else if (d === 0) dHtml = '<span class="aviso-vencimiento">⚠️ Hoy</span>';
        else if (d <= 7) dHtml = '<span class="aviso-vencimiento">⚠️ ' + d + 'd</span>';
        else dHtml = '<span style="color:#2e7d32;font-weight:600;">' + d + ' días</span>';

        var nombreSafe = (c.nombre || '').replace(/'/g, ''), cedulaSafe = (c.cedula || '').replace(/'/g, '');
        var botonAccion = '';
        if (c.estado_cliente === 'Moroso') botonAccion = `<button type="button" class="btn-reactivar" onclick="reactivarCliente('${cedulaSafe}', '${nombreSafe}'); return false;"><i class="fas fa-check"></i> Reactivar</button>`;
        else if (c.estado_cliente !== 'Sin membresía') botonAccion = `<button type="button" class="btn-moroso" onclick="marcarClienteMoroso('${cedulaSafe}', '${nombreSafe}'); return false;"><i class="fas fa-user-slash"></i> Marcar moroso</button>`;
        else botonAccion = '';

        html += '<tr><td>' + (c.id_pago || '—') + '</td><td>' + window.escapeHtmlG(c.cedula) + '</td><td>' + window.escapeHtmlG(c.nombre) + '</td><td>' + window.escapeHtmlG(c.tipo_membresia || '—') + '</td><td><span class="' + window.colorEstadoG(c.estado_cliente) + '">' + window.escapeHtmlG(c.estado_cliente) + '</span></td><td>' + fmtFecha(c.ultima_fecha_pago) + '</td><td>' + fmtFecha(c.fecha_fin) + '</td><td>' + dHtml + '</td><td>' + window.escapeHtmlG(c.telefono || '—') + '</td><td>' + botonAccion + '</td></tr>';
    });
    body.innerHTML = html;
};

window.abrirModalEstadoClientes = function () {
    var modalEl = document.getElementById('estadoClientesModal');
    if (!modalEl) return false;
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl); modal.show();
    }
    ['searchEstadoClientes', 'estadoDesde', 'estadoHasta'].forEach(id => { var el = document.getElementById(id); if (el) el.value = ''; });
    window._filtroEstadoActivo = 'todos'; window.marcarFiltroActivo('todos'); window.cargarEstadoClientes();
    return false;
};

window.filtrarEstadoClientes = function (estado) { window._filtroEstadoActivo = estado; window.marcarFiltroActivo(estado); window.pintarEstadoClientes(); return false; };
window.aplicarFiltrosEstado = function () { window.cargarEstadoClientes(); return false; };
window.refrescarEstadoClientes = function () {
    ['searchEstadoClientes', 'estadoDesde', 'estadoHasta'].forEach(id => { var el = document.getElementById(id); if (el) el.value = ''; });
    window._filtroEstadoActivo = 'todos'; window.marcarFiltroActivo('todos'); window.cargarEstadoClientes(); return false;
};

window._cedulaMorosoPendiente = '';
window.marcarClienteMoroso = function (cedula, nombre) {
    if (!cedula) return false;
    window._cedulaMorosoPendiente = cedula;
    document.getElementById('moroso_cedula').textContent = cedula;
    document.getElementById('moroso_nombre').textContent = nombre || '—';
    window._abrirModalAnidado('marcarMorosoModal'); return false;
};

window._cedulaReactivarPendiente = '';
window.reactivarCliente = function (cedula, nombre) {
    if (!cedula) return false;
    window._cedulaReactivarPendiente = cedula;
    document.getElementById('reactivar_cedula').textContent = cedula;
    document.getElementById('reactivar_nombre').textContent = nombre || '—';
    window._abrirModalAnidado('reactivarModal'); return false;
};

document.addEventListener('input', function (e) {
    if (e.target && e.target.id === 'searchEstadoClientes') {
        clearTimeout(window._estadoTimeout); window._estadoTimeout = setTimeout(window.pintarEstadoClientes, 250);
    }
});

// ============================================================
// INICIALIZACIÓN (Pagos y Modales)
// ============================================================
function initGestionPago() {
    // Auto-ocultar alertas
    document.querySelectorAll('.alert:not(.alert-dynamic)').forEach(alert => {
        setTimeout(() => { alert.classList.add('fade-out'); setTimeout(() => alert.remove(), 500); }, 4000);
    });

    function hideModal(el) { if (!el || typeof bootstrap === 'undefined') return; var modal = bootstrap.Modal.getInstance(el); if (modal) modal.hide(); }

    // Inicializar paginación con las filas iniciales renderizadas por PHP
    var tablaBody = document.getElementById('tablaPagosBody');
    if (tablaBody) {
        var filasIniciales = Array.from(tablaBody.querySelectorAll('tr')).filter(function (tr) {
            return tr.getAttribute('data-id') !== null;
        });
        if (filasIniciales.length > 0) {
            window.setDatosHistorialPaginado(filasIniciales, true);
        }
    }

    // Click en botones de paginación del historial
    var pagCont = document.getElementById('paginacionHistorial');
    if (pagCont) {
        pagCont.addEventListener('click', function (e) {
            var btn = e.target.closest('.page-btn');
            if (!btn || btn.disabled || btn.classList.contains('disabled') || btn.classList.contains('dots')) return;
            var p = parseInt(btn.getAttribute('data-page'), 10);
            if (!isNaN(p)) {
                window.mostrarPaginaHistorial(p);
            }
        });
    }

    // Buscador de Pagos
    var searchInput = document.getElementById('searchPagos'), timeoutId = null;
    function buscarPagos() {
        var termino = searchInput ? searchInput.value.trim() : '';
        fetch('?page=gestion_pago&action=buscar_ajax&termino=' + encodeURIComponent(termino))
            .then(r => r.json()).then(data => {
                var lista = Array.isArray(data) ? data : (data.data || []);
                if (!lista.length) {
                    window.setDatosHistorialPaginado([], true);
                    return;
                }
                var htmlRows = [];
                lista.forEach(p => {
                    var estPago = p.estado_pago === 'Pagado' ? '<span class="badge-pagado">Pagado</span>' : (p.estado_pago === 'Atrasado' ? '<span class="badge-atrasado">Atrasado</span>' : '<span class="badge-pendiente">Pendiente</span>');
                    var estCli = p.estado_cliente === 'Activo' ? 'estado-activo' : (p.estado_cliente === 'Próximo a vencer' ? 'estado-proximo' : 'estado-vencido');
                    var diasHtml = '', dias = parseInt(p.dias_restantes, 10) || 0;
                    if (dias < 0) diasHtml = 'Vencido hace ' + Math.abs(dias) + ' días';
                    else if (dias === 0) diasHtml = '<span class="aviso-vencimiento">⚠️ ¡Vence hoy!</span>';
                    else if (dias <= 5) diasHtml = '<span class="aviso-vencimiento">⚠️ ¡Te quedan ' + dias + ' días!</span>';
                    else diasHtml = 'Faltan ' + dias + ' días';
                    var nCorto = p.nombre_cliente ? String(p.nombre_cliente).split(' ')[0] : '';

                    htmlRows.push(`<tr data-id="${p.id_pago}"><td>${p.id_pago}</td><td>${window.escapeHtmlG(p.cedula_cliente)}</td><td>${window.escapeHtmlG(nCorto)}</td><td>$${parseFloat(p.monto).toFixed(2)}</td><td>${window.escapeHtmlG(p.metodo_pago||'')}</td><td>${estPago}</td><td><span class="${estCli}">${window.escapeHtmlG(p.estado_cliente||'')}</span></td><td>${diasHtml}</td><td><div class="acciones-botones"><button class="btn btn-sm btn-warning editar-btn" data-bs-toggle="modal" data-bs-target="#editarModal" data-id="${p.id_pago}" data-cliente="${window.escapeHtmlG(p.cedula_cliente)}" data-nombre="${window.escapeHtmlG(p.nombre_cliente||'')}" data-monto="${p.monto}" data-metodo="${window.escapeHtmlG(p.metodo_pago||'')}" data-estado="${window.escapeHtmlG(p.estado_pago||'')}" data-fecha_pago="${p.fecha_pago}" data-fecha_vencimiento="${p.fecha_vencimiento||''}"><i class="fas fa-edit"></i> Editar</button><button class="btn btn-sm btn-danger eliminar-btn" data-bs-toggle="modal" data-bs-target="#eliminarModal" data-id="${p.id_pago}"><i class="fas fa-trash-alt"></i> Eliminar</button><button class="btn btn-sm btn-info ver-btn" data-bs-toggle="modal" data-bs-target="#verModal" data-id="${p.id_pago}" data-cliente="${window.escapeHtmlG(p.cedula_cliente)}" data-nombre="${window.escapeHtmlG(nCorto)}" data-monto="${p.monto}" data-metodo="${window.escapeHtmlG(p.metodo_pago||'')}" data-estado="${window.escapeHtmlG(p.estado_pago||'')}" data-fecha_pago="${p.fecha_pago}" data-fecha_vencimiento="${p.fecha_vencimiento||''}"><i class="fas fa-eye"></i> Ver</button></div></td></tr>`);
                });
                window.setDatosHistorialPaginado(htmlRows, true);
            }).catch(err => { console.error(err); });
    }
    if (searchInput) searchInput.addEventListener('keyup', () => { clearTimeout(timeoutId); timeoutId = setTimeout(buscarPagos, 400); });
    var btnBuscar = document.getElementById('btnBuscar'); if (btnBuscar) btnBuscar.addEventListener('click', buscarPagos);

    // Acciones de la tabla (Editar/Eliminar/Ver)
    if (tablaBody) {
        tablaBody.addEventListener('click', e => {
            var bE = e.target.closest('.editar-btn');
            if (bE) {
                document.getElementById('edit_id').value = bE.dataset.id; document.getElementById('edit_cliente').value = bE.dataset.cliente; document.getElementById('edit_nombre').value = bE.dataset.nombre; document.getElementById('edit_monto').value = bE.dataset.monto; document.getElementById('edit_metodo').value = bE.dataset.metodo; document.getElementById('edit_estado').value = bE.dataset.estado; document.getElementById('edit_fecha_pago').value = bE.dataset.fecha_pago; document.getElementById('edit_fecha_vencimiento').value = bE.dataset.fecha_vencimiento; return;
            }
            var bEl = e.target.closest('.eliminar-btn');
            if (bEl) { document.getElementById('delete_id').value = bEl.dataset.id; document.getElementById('confirmDeleteBtn').href = '?page=gestion_pago&action=eliminar&eliminar_pago=' + bEl.dataset.id; return; }
            var bV = e.target.closest('.ver-btn');
            if (bV) {
                document.getElementById('ver_id').innerText = bV.dataset.id; document.getElementById('ver_cedula').innerText = bV.dataset.cliente; document.getElementById('ver_nombre').innerText = bV.dataset.nombre; document.getElementById('ver_monto').innerText = '$ ' + parseFloat(bV.dataset.monto).toFixed(2); document.getElementById('ver_metodo').innerText = bV.dataset.metodo; document.getElementById('ver_estado').innerText = bV.dataset.estado; document.getElementById('ver_fecha_pago').innerText = bV.dataset.fecha_pago; document.getElementById('ver_fecha_vencimiento').innerText = bV.dataset.fecha_vencimiento;
            }
        });
    }

    // Selección de método de pago
    document.querySelectorAll('.method-item').forEach(item => {
        item.addEventListener('click', function () {
            document.getElementById('selected_metodo').value = item.getAttribute('data-metodo');
            document.getElementById('metodo_selected_text').innerText = item.innerText.trim();
            hideModal(document.getElementById('metodoModal'));
        });
    });

    // Validación visual del monto
    var montoInput = document.getElementById('monto_input');
    if (montoInput) { 
        montoInput.addEventListener('input', function () { 
            if(parseFloat(this.value) > 0) this.classList.add('monto-valid'); 
            else this.classList.remove('monto-valid'); 
        }); 
    }

    // Selección de plan y precio
    window._precioPlanSeleccionado = 0; window._planTextoSeleccionado = '';
    document.querySelectorAll('.plan-item').forEach(item => {
        item.addEventListener('click', function () {
            var plan = item.getAttribute('data-plan'), text = item.getAttribute('data-text'), precio = parseFloat(item.getAttribute('data-precio')) || 0;
            document.getElementById('selected_plan').value = plan;
            if (plan === '') { 
                document.getElementById('plan_selected_text').innerText = text; 
                window._precioPlanSeleccionado = 0; 
                window._planTextoSeleccionado = text; 
            }
            else { 
                document.getElementById('plan_selected_text').innerText = text + ' — $' + precio.toFixed(2); 
                window._precioPlanSeleccionado = precio; 
                window._planTextoSeleccionado = text; 
            }
            hideModal(document.getElementById('planModal'));
        });
    });

    // Envío del formulario y validaciones
    var formRegistro = document.getElementById('formRegistroPago');
    if (formRegistro) {
        formRegistro.addEventListener('submit', function (e) {
            var cedula = document.getElementById('selected_cliente_id').value;
            var montoVal = montoInput.value;

            if (!cedula) { 
                e.preventDefault(); 
                window._mostrarToast('Debe seleccionar un cliente primero.', 'error'); 
                return; 
            }
            if (!montoVal || parseFloat(montoVal) <= 0) { 
                e.preventDefault(); 
                window._mostrarToast('El monto ingresado no es válido.', 'error'); 
                return; 
            }

            // Validación de Pago Parcial
            if (window._precioPlanSeleccionado > 0) {
                var ingresado = parseFloat(montoVal) || 0;
                if (ingresado < window._precioPlanSeleccionado) {
                    e.preventDefault();
                    document.getElementById('parcial_plan').textContent = window._planTextoSeleccionado;
                    document.getElementById('parcial_precio').textContent = window._precioPlanSeleccionado.toFixed(2);
                    document.getElementById('parcial_monto').textContent = ingresado.toFixed(2);
                    document.getElementById('parcial_deuda').textContent = (window._precioPlanSeleccionado - ingresado).toFixed(2);
                    window._abrirModalAnidado('confirmarParcialModal');
                    return;
                }
            }

            // BLOQUEO ANTI DOBLE-CLIC (Para pagos completos)
            var btnSubmit = formRegistro.querySelector('button[type="submit"]');
            if (btnSubmit) {
                if (btnSubmit.disabled) { e.preventDefault(); return; }
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando...';
            }
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initGestionPago);
} else {
    initGestionPago();
}

// ============================================================
// HANDLERS DE MODALES ANIDADOS
// ============================================================

// Confirmar Pago Parcial
document.addEventListener('click', function (e) {
    if (!e.target.closest('#confirmarPagoParcial')) return;
    var modalEl = document.getElementById('confirmarParcialModal');
    if (modalEl) { var modal = bootstrap.Modal.getInstance(modalEl); if (modal) modal.hide(); }
    
    var form = document.getElementById('formRegistroPago'), montoInput = document.getElementById('monto_input');
    if (form && montoInput) {
        var btnSubmit = form.querySelector('button[type="submit"]');
        if (btnSubmit) { 
            btnSubmit.disabled = true; 
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando...'; 
        }
        form.submit(); // Este envío NO vuelve a disparar el evento 'submit', va directo al servidor
    }
});

// Confirmar Marcar Moroso
document.addEventListener('click', function (e) {
    if (!e.target.closest('#confirmarMarcarMoroso')) return;
    var cedula = window._cedulaMorosoPendiente || ''; if (!cedula) return;
    var modalEl = document.getElementById('marcarMorosoModal'); if (modalEl) { var modal = bootstrap.Modal.getInstance(modalEl); if (modal) modal.hide(); }
    var fd = new FormData(); fd.append('cedula', cedula);
    fetch('?page=gestion_pago&action=marcar_moroso', { method: 'POST', body: fd }).then(r => r.json()).then(data => {
        if (data.success) { window.cargarEstadoClientes(); window._mostrarToast(data.message || '✅ Cliente moroso.'); }
        else { window._mostrarToast('❌ ' + (data.message || 'Error'), 'error'); }
    }).catch(err => { window._mostrarToast('❌ Error de conexión.', 'error'); });
});

// Confirmar Reactivar Cliente
document.addEventListener('click', function (e) {
    if (!e.target.closest('#confirmarReactivar')) return;
    var cedula = window._cedulaReactivarPendiente || ''; if (!cedula) return;
    var modalEl = document.getElementById('reactivarModal'); if (modalEl) { var modal = bootstrap.Modal.getInstance(modalEl); if (modal) modal.hide(); }
    var fd = new FormData(); fd.append('cedula', cedula);
    fetch('?page=gestion_pago&action=reactivar_cliente', { method: 'POST', body: fd }).then(r => r.json()).then(data => {
        if (data.success) { window.cargarEstadoClientes(); window._mostrarToast(data.message || '✅ Cliente reactivado.'); }
        else { window._mostrarToast('❌ ' + (data.message || 'Error'), 'error'); }
    }).catch(err => { window._mostrarToast('❌ Error de conexión.', 'error'); });
});

// Helper para lanzar Toasts (Notificaciones flotantes)
window._mostrarToast = function (msg, tipo) {
    var type = tipo === 'error' ? 'danger' : 'success', icon = type === 'success' ? 'check-circle' : 'exclamation-triangle';
    var container = document.getElementById('toastContainerGlobal');
    if (!container) { container = document.createElement('div'); container.id = 'toastContainerGlobal'; document.body.appendChild(container); }
    var div = document.createElement('div');
    div.className = 'alert alert-' + type + ' alert-dismissible fade show'; div.role = 'alert';
    div.innerHTML = '<i class="fas fa-' + icon + '"></i> ' + msg + ' <button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    container.appendChild(div);
    setTimeout(() => { div.classList.add('fade-out'); setTimeout(() => div.remove(), 500); }, 4000);
};