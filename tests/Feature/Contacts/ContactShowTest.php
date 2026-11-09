<?php

use App\Models\Contact;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otro = User::factory()->create();
});

// ---------- GET /api/v1/contacts/{contact} ----------

it('pide token para ver un contacto aunque exista', function () {
    $contact = Contact::factory()->for($this->user)->create();

    $this->getJson("/api/v1/contacts/{$contact->id}")
        ->assertUnauthorized()
        ->assertJsonMissingPath('data');
});

it('muestra el perfil completo de un contacto propio', function () {
    $contact = Contact::factory()->for($this->user)->create(datosContacto());
    Sanctum::actingAs($this->user);

    $this->getJson("/api/v1/contacts/{$contact->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $contact->id)
        ->assertJsonPath('data.first_name', 'Ana')
        ->assertJsonPath('data.last_name', 'Pérez')
        ->assertJsonPath('data.full_name', 'Ana Pérez')
        ->assertJsonPath('data.phone_number', '3101234567')
        ->assertJsonPath('data.email', 'ana.perez@correo.com')
        ->assertJsonPath('data.address', 'Calle 10 # 5-20, Bogotá')
        ->assertJsonPath('data.avatar_url', 'https://i.pravatar.cc/300?img=5')
        ->assertJsonStructure(['data' => ['created_at', 'updated_at']]);
});

it('no deja ver el contacto de otro usuario', function () {
    $ajeno = Contact::factory()->for($this->otro)->create();
    Sanctum::actingAs($this->user);

    $this->getJson("/api/v1/contacts/{$ajeno->id}")
        ->assertForbidden()
        ->assertJsonPath('message', 'Este contacto no te pertenece.')
        ->assertJsonMissingPath('data');
});

it('responde 404 en JSON si el contacto no existe', function () {
    Sanctum::actingAs($this->user);

    $this->get('/api/v1/contacts/9999')
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonPath('message', 'El contacto solicitado no existe.');
});

it('responde 404 si el contacto fue eliminado', function () {
    $contact = Contact::factory()->for($this->user)->create();
    $contact->delete();
    Sanctum::actingAs($this->user);

    $this->getJson("/api/v1/contacts/{$contact->id}")
        ->assertNotFound();
});

it('responde 405 en JSON si el método no existe para la ruta', function () {
    $contact = Contact::factory()->for($this->user)->create();
    Sanctum::actingAs($this->user);

    $this->postJson("/api/v1/contacts/{$contact->id}")
        ->assertMethodNotAllowed()
        ->assertJsonPath('message', 'El método POST no está permitido en esta ruta.');
});
