<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Todas las pruebas de tests/Feature usan el TestCase de Laravel, así pueden
| hacer peticiones HTTP ($this->getJson(...)) y usar la base de datos.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');
