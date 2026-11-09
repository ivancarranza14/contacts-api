<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

function datosLogin(array $cambios = []): array
{
    return array_merge([
        'email' => 'ana@contacts.test',
        'password' => 'secreta123',
        'device_name' => 'pruebas',
    ], $cambios);
}

function datosRegistro(array $cambios = []): array
{
    return array_merge([
        'first_name' => 'Luis',
        'last_name' => 'Gómez',
        'email' => 'luis@contacts.test',
        'phone_number' => '3007654321',
        'password' => 'secreta123',
        'password_confirmation' => 'secreta123',
        'device_name' => 'pruebas',
    ], $cambios);
}

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'ana@contacts.test',
        'password' => 'secreta123',
        'phone_number' => '3001234567',
    ]);
});

// ---------- POST /api/v1/auth/register ----------

it('registra un usuario y entrega un token', function () {
    $this->postJson('/api/v1/auth/register', datosRegistro())
        ->assertCreated()
        ->assertJsonStructure(['message', 'token', 'token_type', 'user' => ['id', 'first_name', 'last_name', 'email', 'phone_number']])
        ->assertJsonPath('user.email', 'luis@contacts.test')
        ->assertJsonPath('user.full_name', 'Luis Gómez')
        ->assertJsonMissingPath('user.password');

    $luis = User::firstWhere('email', 'luis@contacts.test');

    expect($luis)->not->toBeNull()
        ->and(Hash::check('secreta123', $luis->password))->toBeTrue()
        ->and($luis->tokens()->count())->toBe(1);
});

it('registra un usuario sin apellido ni teléfono', function () {
    $this->postJson('/api/v1/auth/register', datosRegistro(['last_name' => null, 'phone_number' => null]))
        ->assertCreated()
        ->assertJsonPath('user.last_name', null)
        ->assertJsonPath('user.phone_number', null);
});

it('valida los campos obligatorios del registro', function (string $campo) {
    $this->postJson('/api/v1/auth/register', datosRegistro([$campo => '']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$campo]);
})->with(['first_name', 'email', 'password', 'device_name']);

it('no registra un correo que ya está en uso', function () {
    $this->postJson('/api/v1/auth/register', datosRegistro(['email' => 'ANA@contacts.test']))
        ->assertUnprocessable()
        ->assertJsonPath('errors.email.0', 'Ese correo ya está en uso por otro usuario.');

    expect(User::count())->toBe(1);
});

it('no registra un teléfono que ya está en uso, aunque venga con espacios o guiones', function () {
    $this->postJson('/api/v1/auth/register', datosRegistro(['phone_number' => '300 123-4567']))
        ->assertUnprocessable()
        ->assertJsonPath('errors.phone_number.0', 'Ese teléfono ya está en uso por otro usuario.');
});

it('exige que la confirmación de la contraseña coincida', function () {
    $this->postJson('/api/v1/auth/register', datosRegistro(['password_confirmation' => 'otra-cosa']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

// ---------- POST /api/v1/auth/login ----------

it('entrega un token con credenciales correctas', function () {
    $this->postJson('/api/v1/auth/login', datosLogin())
        ->assertOk()
        ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'first_name', 'email']])
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('user.email', 'ana@contacts.test')
        ->assertJsonMissingPath('user.password');

    expect($this->user->tokens()->count())->toBe(1);
});

it('rechaza una contraseña incorrecta con 422', function () {
    $this->postJson('/api/v1/auth/login', datosLogin(['password' => 'otra']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);

    expect($this->user->tokens()->count())->toBe(0);
});

it('usa el mismo mensaje si el correo no existe', function () {
    $this->postJson('/api/v1/auth/login', datosLogin(['email' => 'nadie@contacts.test']))
        ->assertUnprocessable()
        ->assertJsonPath('errors.email.0', 'Las credenciales no son correctas.');
});

it('valida los campos obligatorios del login', function (string $campo) {
    $this->postJson('/api/v1/auth/login', datosLogin([$campo => '']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$campo]);
})->with(['email', 'password', 'device_name']);

it('limita los intentos de login a 5 por minuto', function () {
    foreach (range(1, 5) as $intento) {
        $this->postJson('/api/v1/auth/login', datosLogin(['password' => 'otra']))
            ->assertUnprocessable();
    }

    $this->postJson('/api/v1/auth/login', datosLogin(['password' => 'otra']))
        ->assertTooManyRequests()
        ->assertJsonPath('message', 'Demasiados intentos de inicio de sesión. Espera un minuto.');
});

// ---------- POST /api/v1/auth/logout ----------

it('cierra sesión y revoca el token', function () {
    $token = $this->user->createToken('pruebas')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/auth/logout')
        ->assertNoContent();

    expect($this->user->tokens()->count())->toBe(0);
});

it('no deja usar el token después de cerrar sesión', function () {
    $token = $this->user->createToken('pruebas')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();

    // En las pruebas la app no se reinicia entre peticiones: se olvida el usuario ya resuelto.
    $this->app['auth']->forgetGuards();

    $this->withToken($token)
        ->getJson('/api/v1/profile')
        ->assertUnauthorized();
});

it('pide token para cerrar sesión y devuelve al login', function () {
    $this->postJson('/api/v1/auth/logout')
        ->assertUnauthorized()
        ->assertJsonPath('message', 'No has iniciado sesión. Inicia sesión para continuar.')
        ->assertJsonPath('login_url', fn (string $url) => str_ends_with($url, '/api/v1/auth/login'));
});
