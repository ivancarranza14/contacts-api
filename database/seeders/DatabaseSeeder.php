<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Usuario conocido para iniciar sesión en Insomnia: demo@contacts.test / password
        $demo = User::factory()->create([
            'first_name' => 'Usuario',
            'last_name' => 'Demo',
            'email' => 'demo@contacts.test',
            'phone_number' => '3000000001',
        ]);

        Contact::factory(10)->for($demo)->create();

        // Otros dos usuarios con sus propios contactos, para probar que nadie ve los ajenos.
        User::factory(2)
            ->create()
            ->each(fn (User $user) => Contact::factory(5)->for($user)->create());
    }
}
