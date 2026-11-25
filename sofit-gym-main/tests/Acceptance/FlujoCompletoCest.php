<?php

use Tests\Support\AcceptanceTester;

class FlujoCompletoCest
{
    /**
     * 1. PRUEBA DE HUMO (Smoke Test)
     * Verifica que el sistema responde HTTP 200 y no muestra errores fatales de PHP.
     */
    public function pruebaDeHumoSistemaActivo(AcceptanceTester $I): void
    {
        $I->amOnPage('index.php');
        $I->seeResponseCodeIs(200);
        $I->dontSee('Fatal error');
        $I->dontSee('Parse error');
        $I->dontSee('Uncaught Error');
        $I->dontSee('Warning:');
    }

    /**
     * 2. PRUEBA DE INTERFAZ DE USUARIO (UI)
     * Verifica que los elementos visuales del formulario de Login estén presentes en el DOM.
     */
    public function pruebaInterfazFormularioLogin(AcceptanceTester $I): void
    {
        $I->amOnPage('index.php');
        $I->seeResponseCodeIs(200);
        $I->seeElement('form');
        $I->seeElement('input');
        $I->seeElement('button, input[type="submit"]');
    }

    /**
     * 3. PRUEBA DE ACEPTACIÓN (CP01-01 - Control de acceso / Autenticación)
     * Valida que el sistema procese la petición del módulo de Login sin caerse.
     */
    public function cp01ValidacionFlujoAceptacionLogin(AcceptanceTester $I): void
    {
        $I->amOnPage('index.php');
        $I->sendAjaxPostRequest('index.php', [
            'usuario' => 'admin@sofitgym.com',
            'password' => '123456',
        ]);
        $I->dontSeeResponseCodeIs(500);
        $I->dontSee('Fatal error');
    }
}