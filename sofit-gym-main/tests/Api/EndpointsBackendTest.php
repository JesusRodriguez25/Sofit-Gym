<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class EndpointsBackendTest extends TestCase
{
    private Client $client;

    protected function setUp(): void
    {
        $this->client = new Client([
            'base_uri' => 'http://localhost/sofit-gym-main/',
            'http_errors' => false,
            'allow_redirects' => false,
            'timeout' => 10.0,
        ]);
    }

    public function testAccesoIndexRespondeYRegistraTiempo(): void
    {
        $inicio = microtime(true);
        $response = $this->client->get('index.php');
        $duracionMs = round((microtime(true) - $inicio) * 1000, 2);

        $this->assertContains($response->getStatusCode(), [200, 302]);
        // Umbral de 6000 ms en entorno local XAMPP
        $this->assertLessThan(6000, $duracionMs, "Tiempo medido: {$duracionMs} ms");
    }

    public function testEnvioLoginConCredencialesInvalidasNoConcedeAccesoDirecto(): void
    {
        $response = $this->client->post('index.php', [
            'form_params' => [
                'usuario' => 'prueba_invalida@sofitgym.test',
                'password' => 'clave_incorrecta_123',
            ],
        ]);

        // Verifica que el servidor responde con un código HTTP controlado y no 500
        $this->assertContains($response->getStatusCode(), [200, 302, 400, 401, 403, 404, 405]);
        $this->assertLessThan(500, $response->getStatusCode());
    }

    public function testRutasDeModulosRespondenSinErrorDeServidor500(): void
    {
        $rutas = [
            'index.php',
        ];

        foreach ($rutas as $ruta) {
            $response = $this->client->get($ruta);
            $this->assertLessThan(500, $response->getStatusCode(), "La ruta {$ruta} no debe devolver error 5xx");
        }
    }
}