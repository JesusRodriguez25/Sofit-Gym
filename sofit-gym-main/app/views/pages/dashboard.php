<?php

/** @var \App\Services\Auth\CurrentUser $usuario */

// Props
$asistencias ??= [];
$clientesMensuales ??= [];
$ingresosMensuales ??= [];

$this->layout('layout', ['title' => 'Inicio']);
$this->pushJs('lib/chart.js/chart.umd.min.js');
$this->pushJs('pages/dashboard/dashboard.js');

function headerTitle(string $icon = "", string $titulo = "")
{
    return <<<HTML
        <h2 class="fs-3 fw-bold text-white mb-4 d-flex align-items-center gap-2"
            style="text-shadow: 0 1px 2px rgba(0,0,0,0.6), 0 0 12px rgba(0,0,0,0.5);">
            <i class="fas {$icon}"></i>
            {$titulo}
        </h2>
    HTML;
}

function cardAcceso(
    string $page,
    string $icon = "",
    string $iconClass = "",
    string $titulo = "",
    string $descripcion = ""
): string {
    return <<<HTML
        <div class="col-12 col-sm-6 col-md-3">
            <a href="?page={$page}" class="card h-100 text-decoration-none border-0 shadow-sm rounded-4 hover-lift">
                <div class="card-body d-flex align-items-start gap-3">
                    <div class="p-3 rounded-3 fs-3 d-flex align-items-center justify-content-center {$iconClass}"
                        style="width: 56px; height: 56px; flex-shrink: 0;">
                        <i class="fas {$icon}"></i>
                    </div>

                    <div class="flex-grow-1 min-w-0">
                        <h4 class="card-title fs-6 fw-bold mb-1 text-dark">{$titulo}</h4>
                        <p class="card-text small text-muted mb-0">{$descripcion}</p>
                    </div>
                </div>
            </a>
        </div>
    HTML;
}

function cardEstadistica(
    string $titulo,
    int|string $valor,
    string $footer,
    string $icon = "fa-chart-simple",
    string $iconClass = "bg-danger bg-opacity-25 text-danger"
): string {
    return <<<HTML
        <div class="col-12 col-sm-6 col-md-4">
            <div class="card h-100 border-0 shadow-sm rounded-4 hover-lift">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="p-3 rounded-3 fs-3 d-flex align-items-center justify-content-center {$iconClass}"
                        style="width: 56px; height: 56px; flex-shrink: 0;">
                        <i class="fas {$icon}"></i>
                    </div>

                    <div class="flex-grow-1 min-w-0">
                        <h4 class="card-title fs-6 fw-semibold text-muted mb-1">{$titulo}</h4>
                        <div class="fs-3 fw-bold text-dark mb-0">{$valor}</div>
                        <span class="small text-muted">{$footer}</span>
                    </div>
                </div>
            </div>
        </div>
    HTML;
}
?>

<div class="container-fluid p-4">
    <main class="main-content">
        <?= $this->insert("topbar") ?>

        <section>
            <?= headerTitle("fa-rocket", "Accesos rápidos") ?>

            <div class="row g-3 mb-5">
                <?= cardAcceso(
                    page: "clientes",
                    icon: "fa-id-card",
                    iconClass: "bg-warning text-warning bg-opacity-25",
                    titulo: "Gestión de clientes",
                    descripcion: "Información y seguimiento biometrico",
                ) ?>

                <?= cardAcceso(
                    page: "gestion_pago",
                    icon: "fa-coins",
                    iconClass: "bg-success text-success bg-opacity-25",
                    titulo: "Gestión de Pago",
                    descripcion: "Control de membresias y vencimientos",
                ) ?>

                <?= cardAcceso(
                    page: "asistencia",
                    icon: "fa-fingerprint",
                    iconClass: "bg-info text-info bg-opacity-25",
                    titulo: "Registro de asistencias",
                    descripcion: "Control de entradas al gimnasio",
                ) ?>

                <?= cardAcceso(
                    page: "clasesGrupales",
                    icon: "fa-calendar-alt",
                    iconClass: "bg-danger text-danger bg-opacity-25",
                    titulo: "Clases grupales",
                    descripcion: "Calendario y horarios de clases",
                ) ?>
            </div>
        </section>

        <section class="row g-1">
            <?= headerTitle("fa-chart-area", "Estadisticas") ?>

            <div class="row g-3">
                <?= cardEstadistica(
                    titulo: "Asistencias de hoy",
                    valor: count($asistencias),
                    footer: "Registradas",
                    icon: "fa-fingerprint",
                    iconClass: "bg-info bg-opacity-25 text-info",
                ) ?>

                <?= cardEstadistica(
                    titulo: "Clientes del mes",
                    valor: count($clientesMensuales),
                    footer: "Membresías activas",
                    icon: "fa-user-check",
                    iconClass: "bg-success bg-opacity-25 text-success",
                ) ?>

                <?= cardEstadistica(
                    titulo: "Ingresos mensuales",
                    valor: "$" . round($ingresosMensuales["total_ingresado"]),
                    footer: "Ganancias totales",
                    icon: "fa-coins",
                    iconClass: "bg-warning bg-opacity-25 text-warning",
                ) ?>
        

<style>
    .glass-card {
        background: rgba(15, 23, 42, 0.20);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.15);
    }

    .hover-lift {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .hover-lift:hover {
        transform: translateY(-3px) !important;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2) !important;
    }

    .cursor-pointer {
        cursor: pointer;
    }

    .dropdown-item:hover {
        background-color: #fef2f2 !important;
    }
</style>