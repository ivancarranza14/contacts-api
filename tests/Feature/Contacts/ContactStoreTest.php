<?php

use App\Models\Contact;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otro = User::factory()->create();
});

// ---------- POST /api/v1/contacts ----------

it('pide token para crear un contacto', function () {
    $this->postJson('/api/v1/contacts', datosContacto())
        ->assertUnauthorized();

    expect(Contact::count())->toBe(0);
});

it('crea un contacto para el usuario autenticado', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/api/v1/contacts', datosContacto())
        ->assertCreated()
        ->assertJsonPath('message', 'Contacto creado correctamente.')
        ->assertJsonPath('data.full_name', 'Ana Pérez')
        ->assertJsonPath('data.phone_number', '3101234567')
        ->assertJsonPath('data.email', 'ana.perez@correo.com')
        ->assertJsonPath('data.address', 'Calle 10 # 5-20, Bogotá')
        ->assertJsonPath('data.avatar_url', 'https://i.pravatar.cc/300?img=5');

    $this->assertDatabaseHas('contacts', [
        'user_id' => $this->user->id,
        'email' => 'ana.perez@correo.com',
    ]);
});

it('ignora el user_id que venga en la petición', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/api/v1/contacts', datosContacto(['user_id' => $this->otro->id]))
        ->assertCreated();

    expect(Contact::first()->user_id)->toBe($this->user->id);
});

it('crea un contacto sin apellido ni foto, que son opcionales', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/api/v1/contacts', datosContacto(['last_name' => null, 'avatar_url' => null]))
        ->assertCreated()
        ->assertJsonPath('data.last_name', null)
        ->assertJsonPath('data.avatar_url', null)
        ->assertJsonPath('data.full_name', 'Ana');
});

it('valida los campos obligatorios del contacto', function (string $campo) {
    Sanctum::actingAs($this->user);

    $this->postJson('/api/v1/contacts', datosContacto([$campo => '']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$campo]);
})->with(['first_name', 'phone_number', 'email', 'address']);

it('valida el formato del correo, el teléfono y la foto', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/api/v1/contacts', datosContacto([
        'email' => 'ana',
        'phone_number' => 'abc123',
        'avatar_url' => 'no-es-una-url',
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'phone_number', 'avatar_url'])
        ->assertJsonPath('errors.email.0', 'El correo no tiene un formato válido.');
});

it('no deja repetir el correo de otro contacto', function () {
    Contact::factory()->for($this->otro)->create(['email' => 'ana.perez@correo.com']);
    Sanctum::actingAs($this->user);

    $this->postJson('/api/v1/contacts', datosContacto(['email' => 'Ana.Perez@Correo.com']))
        ->assertUnprocessable()
        ->assertJsonPath('errors.email.0', 'Ese correo ya está registrado en otro contacto.');

    expect(Contact::count())->toBe(1);
});

it('no deja repetir el teléfono de otro contacto, aunque venga con espacios o guiones', function () {
    Contact::factory()->for($this->user)->create(['phone_number' => '3101234567']);
    Sanctum::actingAs($this->user);

    $this->postJson('/api/v1/contacts', datosContacto(['phone_number' => '310 123-4567']))
        ->assertUnprocessable()
        ->assertJsonPath('errors.phone_number.0', 'Ese teléfono ya está registrado en otro contacto.');
});

it('no deja reutilizar el correo ni el teléfono de un contacto eliminado', function () {
    Contact::factory()->for($this->user)->create([
        'email' => 'ana.perez@correo.com',
        'phone_number' => '3101234567',
    ])->delete();
    Sanctum::actingAs($this->user);

    $this->postJson('/api/v1/contacts', datosContacto())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'phone_number']);
});

it('permite repetir el nombre y la dirección en otro contacto', function () {
    Contact::factory()->for($this->user)->create([
        'first_name' => 'Ana',
        'last_name' => 'Pérez',
        'address' => 'Calle 10 # 5-20, Bogotá',
    ]);
    Sanctum::actingAs($this->user);

    $this->postJson('/api/v1/contacts', datosContacto())
        ->assertCreated();

    expect(Contact::where('first_name', 'Ana')->where('address', 'Calle 10 # 5-20, Bogotá')->count())->toBe(2);
});
