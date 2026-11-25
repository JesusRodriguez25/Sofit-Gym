<?php
use PHPUnit\Framework\TestCase;

class ValidacionesSofitTest extends TestCase
{
    public function testCalculoStockInventario()
    {
        $stockInicial = 25;
        $cantidadVendida = 4;
        $stockRestante = $stockInicial - $cantidadVendida;

        $this->assertEquals(21, $stockRestante, "El descuento de inventario debe ser exacto");
        $this->assertGreaterThanOrEqual(0, $stockRestante, "El stock nunca puede ser negativo");
    }

    public function testValidacionFormatoCorreoUsuario()
    {
        $correoValido = "admin@sofitgym.com";
        $correoInvalido = "admin-sofitgym.com";

        $this->assertNotFalse(filter_var($correoValido, FILTER_VALIDATE_EMAIL));
        $this->assertFalse(filter_var($correoInvalido, FILTER_VALIDATE_EMAIL));
    }

    public function testCalculoMontoPagoMensualidad()
    {
        $tarifaBase = 30.00;
        $descuento = 5.00;
        $total = $tarifaBase - $descuento;

        $this->assertSame(25.00, $total);
    }
}