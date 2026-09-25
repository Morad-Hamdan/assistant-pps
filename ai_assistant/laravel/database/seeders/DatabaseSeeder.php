<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'ADMIN',
            'email' => 'admin@assistant.com',
            'password' => 'MK2026',
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'MKTANGER',
            'email' => 'employee@assistant.com',
            'password' => 'MK2026',
            'role' => 'employee',
        ]);
    }
}
