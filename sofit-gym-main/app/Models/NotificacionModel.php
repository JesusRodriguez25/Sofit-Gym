<?php

namespace App\Models;

use App\Models\Database;
use App\Core\Tools;
use App\Core\Validator;
use DateTimeImmutable;
use PDO;

class NotificacionModel extends Database
{
    private string $table = self::DB_SECURITY . ".notificacion";

    /**
     * @return Notificacion[]
     */
    public function query(int $id_usuario): array
    {
        $rows = $this->dbQuery(
            $this->sqlSelect(where: "WHERE nu.id_usuario = ?"),
            [$id_usuario]
        )->fetchAll();

        return array_map(
            fn($row) => Tools::map(Notificacion::class, $row),
            $rows
        );
    }

    public function find(int $id_usuario, int $id_notificacion): ?Notificacion
    {
        $row = $this->dbQuery(
            $this->sqlSelect(where: <<<SQL
                WHERE
                    nu.id_usuario = ?
                    AND nu.id_notificacion = ?
            SQL),
            [$id_usuario, $id_notificacion]
        )->fetch();

        return $row
            ? Tools::map(Notificacion::class, $row)
            : null;
    }

    /**
     * Envía una notificación a todos los usuarios del sistema.
     */
    public function sendToAll(Notificacion $notificacion): int
    {
        $notificacion->validateInsert();

        $userIds = $this->dbQuery(
            <<<SQL
                SELECT id_usuario
                FROM {$this->dbSecurity('usuario')}
                WHERE id_usuario IS NOT NULL
            SQL
        )->fetchAll(PDO::FETCH_COLUMN);

        if (!$userIds) {
            return 0;
        }

        $this->sendByUsuarios(array_map('intval', $userIds), $notificacion);
        return count($userIds);
    }

    public function sendByRol(array $id_roles, Notificacion $notificacion): void
    {
        $notificacion->validateInsert();

        if (!$id_roles) {
            return;
        }

        $placeholders = join(',', array_fill(0, count($id_roles), '?'));
        $userIds = $this->dbQuery(
            <<<SQL
                SELECT id_usuario
                FROM {$this->dbSecurity('usuario')}
                WHERE id_rol IN ($placeholders)
            SQL,
            $id_roles
        )->fetchAll(PDO::FETCH_COLUMN);

        if (!$userIds) {
            return;
        }

        $this->sendByUsuarios(array_map('intval', $userIds), $notificacion);
    }

    public function sendByUsuarios(array $id_usuarios, Notificacion $notificacion): void
    {
        $notificacion->validateInsert();

        $idsUnicos = array_values(array_unique(array_filter(array_map('intval', $id_usuarios))));
        if (!$idsUnicos) {
            return;
        }

        $this->dbTransaction(function () use ($idsUnicos, $notificacion) {
            $this->dbInsert($this->table, [
                'titulo' => $notificacion->titulo,
                'contenido' => $notificacion->contenido,
            ]);
            $id_notificacion = (int) $this->pdo->lastInsertId();

            foreach ($idsUnicos as $id) {
                $this->dbInsert(
                    $this->dbSecurity("notificacion_usuario"),
                    [
                        "id_notificacion" => $id_notificacion,
                        "id_usuario" => $id,
                        "leido" => 0,
                    ]
                );
            }
        });
    }

    public function setLeido(int $id_usuario, int $id_notificacion, bool $leido)
    {
        $this->dbUpdate(
            $this->dbSecurity("notificacion_usuario"),
            [
                "leido" => $leido ? 1 : 0,
                "fecha_leido" => $leido ? date('Y-m-d H:i:s') : null,
            ],
            [
                "id_usuario" => $id_usuario,
                "id_notificacion" => $id_notificacion,
            ]
        );
    }

    public function setLeidoTodas(int $id_usuario)
    {
        $this->dbUpdate(
            $this->dbSecurity("notificacion_usuario"),
            [
                "leido" => 1,
                "fecha_leido" => date('Y-m-d H:i:s'),
            ],
            ["id_usuario" => $id_usuario]
        );
    }

    private function sqlSelect(string $where = ""): string
    {
        return <<<SQL
                SELECT
                    notif.*,
                    nu.leido,
                    nu.fecha_leido
                FROM {$this->table} notif
                INNER JOIN {$this->dbSecurity("notificacion_usuario")} nu
                    ON nu.id_notificacion = notif.id_notificacion
                {$where}
                ORDER BY
                    COALESCE(nu.leido, 0) = 0 DESC,
                    notif.fecha_envio DESC
                LIMIT 25
            SQL;
    }
}

// DTO
readonly class Notificacion
{
    public function __construct(
        public ?int $id_notificacion = null,
        public ?string $titulo = null,
        public ?string $contenido = null,
        public ?bool $leido = false,
        public ?DateTimeImmutable $fecha_envio = null,
        public ?DateTimeImmutable $fecha_leido = null,
    ) {}

    public function validateInsert()
    {
        Validator::required($this->titulo, "titulo");
        Validator::required($this->contenido, "contenido");
    }
}