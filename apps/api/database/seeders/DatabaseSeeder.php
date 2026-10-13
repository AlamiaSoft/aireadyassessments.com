<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('INITIAL_ADMIN_EMAIL', '');
        $password = (string) env('INITIAL_ADMIN_PASSWORD', '');
        $name = (string) env('INITIAL_ADMIN_NAME', 'AIReadyAssessment Admin');

        if ($email === '' && $password === '') {
            return;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 16) {
            throw new RuntimeException('INITIAL_ADMIN_EMAIL must be valid and INITIAL_ADMIN_PASSWORD must be at least 16 characters.');
        }

        User::query()->updateOrCreate(
            ['email' => mb_strtolower($email)],
            ['name' => $name, 'password' => Hash::make($password), 'is_admin' => true],
        );
    }
}
