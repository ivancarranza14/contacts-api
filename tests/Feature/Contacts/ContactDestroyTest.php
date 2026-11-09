<?php

use App\Models\Contact;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otro = User::factory()->create();
});

// ---------- DELETE /api/v1/contacts/{contact} ----------

it('pide token para eliminar un contacto', function () {
    $contact = Contact::factory()->for($this->user)->create();

    $this->deleteJson("/api/v1/contacts/{$contact->id}")
        ->assertUnauthorized();

    $this->assertNotSoftDeleted($contact);
});

it('elimina un contacto propio con borrado lógico', function () {
    $contact = Contact::factory()->for($this->user)->create();
    Sanctum::actingAs($this->user);

    $this->deleteJson("/api/v1/contacts/{$contact->id}")
        ->assertNoContent();

    $this->assertSoftDeleted($contact);
    expect(Contact::withTrashed()->find($contact->id))->not->toBeNull();
});

it('no deja eliminar el contacto de otro usuario', function () {
    $ajeno = Contact::factory()->for($this->otro)->create();
    Sanctum::actingAs($this->user);

    $this->deleteJson("/api/v1/contacts/{$ajeno->id}")
        ->assertForbidden()
        ->assertJsonPath('message', 'Este contacto no te pertenece.');

    $this->assertNotSoftDeleted($ajeno);
});

it('responde 404 al eliminar un contacto que ya fue eliminado', function () {
    $contact = Contact::factory()->for($this->user)->create();
    Sanctum::actingAs($this->user);

    $this->deleteJson("/api/v1/contacts/{$contact->id}")->assertNoContent();

    $this->deleteJson("/api/v1/contacts/{$contact->id}")
        ->assertNotFound()
        ->assertJsonPath('message', 'El contacto solicitado no existe.');
});
