<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Production-safe: creates the first administrator when ADMIN_EMAIL and
     * ADMIN_PASSWORD are set in .env. No sample data.
     */
    public function run(): void
    {
        $email = config('allinson.admin.email');
        $password = config('allinson.admin.password');

        if ($email && $password) {
            User::firstOrCreate(['email' => strtolower($email)], [
                'name' => config('allinson.admin.name'),
                'password' => Hash::make($password),
                'role' => Role::Admin,
                'active' => true,
            ]);
        }
    }
}
