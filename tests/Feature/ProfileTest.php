<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create([
        'first_name' => 'Ana',
        'last_name' => 'Pérez',
        'email' => 'ana@contacts.test',
        'phone_number' => '3001234567',
    ]);
});

// ---------- GET /api/v1/profile ----------

it('pide token para ver el perfil y devuelve al login', function () {
    $this->getJson('/api/v1/profile')
        ->assertUnauthorized()
        ->assertJsonPath('login_url', fn (string $url) => str_ends_with($url, '/api/v1/auth/login'));
});

it('responde 401 en JSON aunque falte la cabecera Accept', function () {
    $this->get('/api/v1/profile')
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/json');
});

it('devuelve el perfil del usuario autenticado', function () {
    Sanctum::actingAs($this->user);

    $this->getJson('/api/v1/profile')
        ->assertOk()
        ->assertJsonPath('data.first_name', 'Ana')
        ->assertJsonPath('data.last_name', 'Pérez')
        ->assertJsonPath('data.full_name', 'Ana Pérez')
        ->assertJsonPath('data.email', 'ana@contacts.test')
        ->assertJsonPath('data.phone_number', '3001234567')
        ->assertJsonMissingPath('data.password');
});

// ---------- PUT / PATCH /api/v1/profile ----------

it('pide token para actualizar el perfil', function () {
    $this->patchJson('/api/v1/profile', ['first_name' => 'Otra'])
        ->assertUnauthorized();

    expect($this->user->fresh()->first_name)->toBe('Ana');
});

it('actualiza solo los campos enviados con PATCH', function () {
    Sanctum::actingAs($this->user);

    $this->patchJson('/api/v1/profile', ['last_name' => 'Gómez'])
        ->assertOk()
        ->assertJsonPath('message', 'Perfil actualizado correctamente.')
        ->assertJsonPath('data.last_name', 'Gómez')
        ->assertJsonPath('data.first_name', 'Ana')
        ->assertJsonPath('data.email', 'ana@contacts.test');

    expect($this->user->fresh()->last_name)->toBe('Gómez');
});

it('reemplaza el perfil con PUT', function () {
    Sanctum::actingAs($this->user);

    $this->putJson('/api/v1/profile', [
        'first_name' => 'Ana María',
        'last_name' => 'Rojas',
        'email' => 'ana.rojas@contacts.test',
        'phone_number' => '3019998877',
    ])
        ->assertOk()
        ->assertJsonPath('data.full_name', 'Ana María Rojas')
        ->assertJsonPath('data.email', 'ana.rojas@contacts.test')
        ->assertJsonPath('data.phone_number', '3019998877');
});

it('exige los campos obligatorios al usar PUT', function (string $campo) {
    Sanctum::actingAs($this->user);

    $datos = ['first_name' => 'Ana', 'email' => 'ana@contacts.test'];
    unset($datos[$campo]);

    $this->putJson('/api/v1/profile', $datos)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$campo]);
})->with(['first_name', 'email']);

it('no deja usar el correo de otro usuario', function () {
    User::factory()->create(['email' => 'luis@contacts.test']);
    Sanctum::actingAs($this->user);

    $this->patchJson('/api/v1/profile', ['email' => 'Luis@Contacts.test'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.email.0', 'Ese correo ya está en uso por otro usuario.');

    expect($this->user->fresh()->email)->toBe('ana@contacts.test');
});

it('no deja usar el teléfono de otro usuario', function () {
    User::factory()->create(['phone_number' => '3007654321']);
    Sanctum::actingAs($this->user);

    $this->patchJson('/api/v1/profile', ['phone_number' => '300-765-4321'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.phone_number.0', 'Ese teléfono ya está en uso por otro usuario.');
});

it('permite conservar su propio correo y teléfono', function () {
    Sanctum::actingAs($this->user);

    $this->putJson('/api/v1/profile', [
        'first_name' => 'Ana',
        'email' => 'ana@contacts.test',
        'phone_number' => '3001234567',
    ])->assertOk();
});

it('permite quitar el teléfono, que es opcional', function () {
    Sanctum::actingAs($this->user);

    $this->patchJson('/api/v1/profile', ['phone_number' => null])
        ->assertOk()
        ->assertJsonPath('data.phone_number', null);
});

it('valida el formato del correo y del teléfono', function () {
    Sanctum::actingAs($this->user);

    $this->patchJson('/api/v1/profile', ['email' => 'ana', 'phone_number' => 'abc'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'phone_number']);
});

it('ignora los campos que no son del perfil', function () {
    Sanctum::actingAs($this->user);
    $hashAnterior = $this->user->password;

    $this->patchJson('/api/v1/profile', ['id' => 999, 'password' => 'nueva12345', 'first_name' => 'Ana'])
        ->assertOk()
        ->assertJsonPath('data.id', $this->user->id);

    expect($this->user->fresh()->password)->toBe($hashAnterior);
});
