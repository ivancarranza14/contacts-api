<?php

use App\Models\Contact;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otro = User::factory()->create();
    $this->contact = Contact::factory()->for($this->user)->create(datosContacto());
});

// ---------- PUT / PATCH /api/v1/contacts/{contact} ----------

it('pide token para actualizar un contacto', function () {
    $this->patchJson("/api/v1/contacts/{$this->contact->id}", ['first_name' => 'Otra'])
        ->assertUnauthorized();

    expect($this->contact->fresh()->first_name)->toBe('Ana');
});

it('actualiza solo los campos enviados con PATCH', function () {
    Sanctum::actingAs($this->user);

    $this->patchJson("/api/v1/contacts/{$this->contact->id}", ['address' => 'Carrera 7 # 12-34, Bogotá'])
        ->assertOk()
        ->assertJsonPath('message', 'Contacto actualizado correctamente.')
        ->assertJsonPath('data.address', 'Carrera 7 # 12-34, Bogotá')
        ->assertJsonPath('data.email', 'ana.perez@correo.com');

    expect($this->contact->fresh()->address)->toBe('Carrera 7 # 12-34, Bogotá')
        ->and($this->contact->fresh()->first_name)->toBe('Ana');
});

it('reemplaza el contacto con PUT', function () {
    Sanctum::actingAs($this->user);

    $this->putJson("/api/v1/contacts/{$this->contact->id}", datosContacto([
        'first_name' => 'Ana María',
        'phone_number' => '3209876543',
        'email' => 'anamaria@correo.com',
        'avatar_url' => null,
    ]))
        ->assertOk()
        ->assertJsonPath('data.full_name', 'Ana María Pérez')
        ->assertJsonPath('data.phone_number', '3209876543')
        ->assertJsonPath('data.email', 'anamaria@correo.com')
        ->assertJsonPath('data.avatar_url', null);
});

it('exige los campos obligatorios al usar PUT', function (string $campo) {
    Sanctum::actingAs($this->user);

    $datos = datosContacto();
    unset($datos[$campo]);

    $this->putJson("/api/v1/contacts/{$this->contact->id}", $datos)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$campo]);
})->with(['first_name', 'phone_number', 'email', 'address']);

it('permite conservar su propio correo y teléfono', function () {
    Sanctum::actingAs($this->user);

    $this->putJson("/api/v1/contacts/{$this->contact->id}", datosContacto(['last_name' => 'Rojas']))
        ->assertOk()
        ->assertJsonPath('data.last_name', 'Rojas');
});

it('no deja usar el correo de otro contacto', function () {
    Contact::factory()->for($this->otro)->create(['email' => 'luis@correo.com']);
    Sanctum::actingAs($this->user);

    $this->patchJson("/api/v1/contacts/{$this->contact->id}", ['email' => 'luis@correo.com'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.email.0', 'Ese correo ya está registrado en otro contacto.');
});

it('no deja usar el teléfono de otro contacto', function () {
    Contact::factory()->for($this->user)->create(['phone_number' => '3155550000']);
    Sanctum::actingAs($this->user);

    $this->patchJson("/api/v1/contacts/{$this->contact->id}", ['phone_number' => '315 555 0000'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.phone_number.0', 'Ese teléfono ya está registrado en otro contacto.');
});

it('no deja actualizar el contacto de otro usuario', function () {
    $ajeno = Contact::factory()->for($this->otro)->create(['first_name' => 'Luis']);
    Sanctum::actingAs($this->user);

    $this->patchJson("/api/v1/contacts/{$ajeno->id}", ['first_name' => 'Cambiado'])
        ->assertForbidden()
        ->assertJsonPath('message', 'Este contacto no te pertenece.');

    expect($ajeno->fresh()->first_name)->toBe('Luis');
});

it('revisa el dueño antes de validar los datos', function () {
    $ajeno = Contact::factory()->for($this->otro)->create();
    Sanctum::actingAs($this->user);

    $this->putJson("/api/v1/contacts/{$ajeno->id}", ['email' => 'no-es-correo'])
        ->assertForbidden();
});

it('responde 404 al actualizar un contacto que no existe', function () {
    Sanctum::actingAs($this->user);

    $this->patchJson('/api/v1/contacts/9999', ['first_name' => 'Nadie'])
        ->assertNotFound()
        ->assertJsonPath('message', 'El contacto solicitado no existe.');
});
