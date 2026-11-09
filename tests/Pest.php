<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Todas las pruebas de tests/Feature usan el TestCase de Laravel, así pueden
| hacer peticiones HTTP ($this->getJson(...)) y usar la base de datos.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Funciones de apoyo
|--------------------------------------------------------------------------
*/

/**
 * Datos válidos de un contacto; cada prueba cambia solo lo que le interesa.
 */
function datosContacto(array $cambios = []): array
{
    return array_merge([
        'first_name' => 'Ana',
        'last_name' => 'Pérez',
        'phone_number' => '3101234567',
        'email' => 'ana.perez@correo.com',
        'address' => 'Calle 10 # 5-20, Bogotá',
        'avatar_url' => 'https://i.pravatar.cc/300?img=5',
    ], $cambios);
}
