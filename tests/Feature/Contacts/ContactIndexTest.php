<?php

use App\Models\Contact;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otro = User::factory()->create();
});

// ---------- GET /api/v1/contacts ----------

it('pide token para listar contactos y devuelve al login', function () {
    Contact::factory(2)->for($this->user)->create();

    $this->getJson('/api/v1/contacts')
        ->assertUnauthorized()
        ->assertJsonMissingPath('data')
        ->assertJsonPath('login_url', fn (string $url) => str_ends_with($url, '/api/v1/auth/login'));
});

it('lista solo los contactos del usuario autenticado', function () {
    $propios = Contact::factory(3)->for($this->user)->create();
    Contact::factory(2)->for($this->otro)->create();
    Sanctum::actingAs($this->user);

    $respuesta = $this->getJson('/api/v1/contacts')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [['id', 'first_name', 'last_name', 'full_name', 'phone_number', 'email', 'address', 'avatar_url']],
            'links',
            'meta' => ['current_page', 'per_page', 'total', 'last_page'],
        ]);

    expect(collect($respuesta->json('data'))->pluck('id')->sort()->values()->all())
        ->toBe($propios->pluck('id')->sort()->values()->all());
});

it('pagina los contactos', function () {
    Contact::factory(12)->for($this->user)->create();
    Sanctum::actingAs($this->user);

    $this->getJson('/api/v1/contacts?per_page=5')
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('meta.total', 12)
        ->assertJsonPath('meta.per_page', 5)
        ->assertJsonPath('meta.last_page', 3);
});

it('busca contactos por nombre, correo o teléfono', function () {
    Contact::factory()->for($this->user)->create([
        'first_name' => 'Carolina',
        'last_name' => 'Ríos',
        'email' => 'caro@correo.com',
        'phone_number' => '3111111111',
    ]);
    Contact::factory()->for($this->user)->create([
        'first_name' => 'Luis',
        'last_name' => 'Gómez',
        'email' => 'luis@correo.com',
        'phone_number' => '3155550000',
    ]);
    Sanctum::actingAs($this->user);

    $this->getJson('/api/v1/contacts?search=carol')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.first_name', 'Carolina');

    $this->getJson('/api/v1/contacts?search=5555')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.first_name', 'Luis');
});

it('no lista los contactos eliminados', function () {
    Contact::factory()->for($this->user)->create();
    Contact::factory()->for($this->user)->create()->delete();
    Sanctum::actingAs($this->user);

    $this->getJson('/api/v1/contacts')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
