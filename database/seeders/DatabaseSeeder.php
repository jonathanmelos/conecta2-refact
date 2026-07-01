<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
{
    $this->call([
        MigrateFromOldDatabaseSeeder::class,
    ]);

    User::updateOrCreate(
        ['email' => 'jonathanmelo0001@gmail.com'],
        [
            'name' => 'Jonathan Melo',
            'password' => 'Admin123',
            'role' => 'admin',
        ]
    );
}
}
