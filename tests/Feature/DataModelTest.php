<?php

use App\Models\Contact;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Hash;

it('guarda la contraseña del usuario con hash', function () {
    $user = User::factory()->create(['password' => 'secreta123']);

    expect($user->fresh()->password)->not->toBe('secreta123')
        ->and(Hash::check('secreta123', $user->fresh()->password))->toBeTrue();
});

it('permite usuarios sin apellido ni teléfono', function () {
    $user = User::factory()->withoutPhone()->create(['last_name' => null]);

    expect($user->fresh()->phone_number)->toBeNull()
        ->and($user->fresh()->full_name)->toBe($user->first_name);
});

it('no repite el correo de un usuario', function () {
    User::factory()->create(['email' => 'ana@contacts.test']);
    User::factory()->create(['email' => 'ana@contacts.test']);
})->throws(UniqueConstraintViolationException::class);

it('no repite el teléfono de un usuario', function () {
    User::factory()->create(['phone_number' => '3001234567']);
    User::factory()->create(['phone_number' => '3001234567']);
})->throws(UniqueConstraintViolationException::class);

it('relaciona cada contacto con su usuario', function () {
    $ana = User::factory()->create();
    Contact::factory(3)->for($ana)->create();
    Contact::factory()->create();

    expect($ana->contacts)->toHaveCount(3)
        ->and($ana->contacts->first()->user->is($ana))->toBeTrue();
});

it('no repite el correo de un contacto', function () {
    Contact::factory()->create(['email' => 'luis@correo.com']);
    Contact::factory()->create(['email' => 'luis@correo.com']);
})->throws(UniqueConstraintViolationException::class);

it('no repite el teléfono de un contacto', function () {
    Contact::factory()->create(['phone_number' => '3101234567']);
    Contact::factory()->create(['phone_number' => '3101234567']);
})->throws(UniqueConstraintViolationException::class);

it('permite contactos con el mismo nombre y la misma dirección', function () {
    $datos = ['first_name' => 'Ana', 'last_name' => 'Pérez', 'address' => 'Calle 10 # 5-20'];

    Contact::factory()->create($datos);
    Contact::factory()->create($datos);

    expect(Contact::where($datos)->count())->toBe(2);
});

it('elimina contactos con borrado lógico', function () {
    $contact = Contact::factory()->create();

    $contact->delete();

    $this->assertSoftDeleted($contact);
    expect(Contact::count())->toBe(0)
        ->and(Contact::withTrashed()->count())->toBe(1);
});

it('siembra el usuario demo y los contactos de ejemplo', function () {
    $this->seed(DatabaseSeeder::class);

    $demo = User::firstWhere('email', 'demo@contacts.test');

    expect($demo)->not->toBeNull()
        ->and($demo->contacts()->count())->toBe(10)
        ->and(User::count())->toBe(3)
        ->and(Contact::count())->toBe(20);
});
