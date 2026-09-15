<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('SEED_USER_EMAIL', 'demo@example.com')],
            [
                'name' => 'Demo User',
                'password' => env('SEED_USER_PASSWORD', 'demo12345'),
            ]
        );
    }
}
