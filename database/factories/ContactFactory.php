<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone_number' => fake()->unique()->numerify('31########'),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->streetAddress().', '.fake()->city(),
            'avatar_url' => 'https://i.pravatar.cc/300?img='.fake()->numberBetween(1, 70),
        ];
    }
}
