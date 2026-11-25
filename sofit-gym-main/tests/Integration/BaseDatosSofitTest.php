<?php

use PHPUnit\Framework\TestCase;

class BaseDatosSofitTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO(
            'mysql:host=127.0.0.1;dbname=sofit_gym;charset=utf8mb4',
            'root',
            '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }

    public function testConexionYTablasDisponibles(): void
    {
        $stmt = $this->pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        $tablas = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $this->assertNotEmpty($tablas, 'La base de datos sofit_gym debe tener al menos una tabla base');
    }

    public function testIntegridadLecturaPorTablaConTransaccion(): void
    {
        $stmt = $this->pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        $tablas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $this->assertNotEmpty($tablas);

        $this->pdo->beginTransaction();

        foreach ($tablas as $tabla) {
            $tablaEscapada = str_replace('`', '``', $tabla);
            $consulta = $this->pdo->query("SELECT COUNT(*) AS total FROM `{$tablaEscapada}`");
            $fila = $consulta->fetch();

            $this->assertArrayHasKey('total', $fila);
            $this->assertIsNumeric($fila['total']);
            $this->assertGreaterThanOrEqual(0, (int) $fila['total']);
        }

        $this->pdo->rollBack();
    }

    public function testMetadataColumnasEnInformationSchema(): void
    {
        $stmt = $this->pdo->prepare(
            "SELECT TABLE_NAME, COUNT(COLUMN_NAME) AS total_columnas
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = :bd
             GROUP BY TABLE_NAME"
        );
        $stmt->execute(['bd' => 'sofit_gym']);
        $resumen = $stmt->fetchAll();

        $this->assertNotEmpty($resumen, 'Debe existir metadata de columnas para sofit_gym');

        foreach ($resumen as $item) {
            $this->assertGreaterThan(0, (int) $item['total_columnas']);
        }
    }
}