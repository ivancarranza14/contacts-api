<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * La contraseña de todos los usuarios de prueba es "password".
     */
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'phone_number' => fake()->unique()->numerify('300#######'),
        ];
    }

    /**
     * Usuario sin teléfono (el campo es opcional).
     */
    public function withoutPhone(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone_number' => null,
        ]);
    }
}
