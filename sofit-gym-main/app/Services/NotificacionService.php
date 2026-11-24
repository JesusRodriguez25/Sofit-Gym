<?php

namespace App\Services;

use App\Core\Tools;
use App\Services\Auth\UserRol;
use App\Models\Notificacion;
use App\Models\NotificacionModel;
use PHPMailer\PHPMailer\PHPMailer;
use Exception;

class NotificacionService
{
    private PHPMailer $mailer;

    public function __construct(
        private NotificacionModel $notifModel = new NotificacionModel(),
    ) {
        $this->mailer = Tools::getMailer();
    }

    /**
     * Envía una notificación a todos los usuarios del sistema.
     */
    public function sendToAll(string $titulo, string $contenido): int
    {
        return $this->notifModel->sendToAll(
            new Notificacion(
                titulo: $titulo,
                contenido: $contenido,
            )
        );
    }

    /**
     * Envía una notificación a todos los usuarios con los roles asignados.
     *
     * @param UserRol[] $roles
     */
    public function sendByRol(array $roles, string $titulo, string $contenido): void
    {
        $roles = array_map(
            fn($item) => $item instanceof UserRol ? $item->value : $item,
            $roles
        );

        $this->notifModel->sendByRol(
            $roles,
            new Notificacion(
                titulo: $titulo,
                contenido: $contenido,
            )
        );
    }

    /**
     * Envía una notificación a uno o varios usuarios.
     */
    public function sendToUsers(array $userIds, string $titulo, string $contenido): void
    {
        $this->notifModel->sendByUsuarios(
            $userIds,
            new Notificacion(
                titulo: $titulo,
                contenido: $contenido,
            )
        );
    }

    /**
     * Envía una notificación por correo electrónico.
     */
    public function sendEmail(string $email, string $subject, string $body): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Error enviando email: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // EVENTOS AUTOMÁTICOS DE LOS MÓDULOS
    // ==========================================

    public function pagoRegistrado(string $cliente, float|string $monto, string $plan = ''): int
    {
        $detallePlan = $plan !== '' ? " ({$plan})" : "";
        return $this->sendToAll(
            "Nuevo pago registrado",
            "Se registró un pago de **\${$monto}** para **{$cliente}**{$detallePlan}."
        );
    }

    public function clienteMoroso(string $cliente, string $cedula): int
    {
        return $this->sendToAll(
            "Cliente marcado como moroso",
            "El cliente **{$cliente}** (C.I. {$cedula}) pasó a estado **Moroso**."
        );
    }

    public function stockBajo(string $producto, int $stockActual, int $stockMinimo): int
    {
        return $this->sendToAll(
            "Stock bajo en productos",
            "El producto **{$producto}** tiene stock bajo: **{$stockActual}** (mínimo: {$stockMinimo})."
        );
    }

    public function equipoEnMantenimiento(string $equipo, string $estado, string $ubicacion = ''): int
    {
        $ubi = $ubicacion !== '' ? ", ubicación: **{$ubicacion}**" : "";
        return $this->sendToAll(
            "Equipo en mantenimiento o fuera de servicio",
            "El equipo **{$equipo}** cambió a estado **{$estado}**{$ubi}."
        );
    }

    public function asistenciaRegistrada(string $cliente, string $hora): int
    {
        return $this->sendToAll(
            "Nueva asistencia registrada",
            "**{$cliente}** registró entrada a las **{$hora}**."
        );
    }
}