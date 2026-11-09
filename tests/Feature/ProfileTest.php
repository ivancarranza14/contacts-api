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
