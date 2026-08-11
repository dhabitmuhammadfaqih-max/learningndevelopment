<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Amin',
            'email' => 'amin@example.com',
            'password' => Hash::make('password'),
            'role' => 'karyawan',
        ]);

        User::create([
            'name' => 'Mieke',
            'email' => 'mieke@example.com',
            'password' => Hash::make('password'),
            'role' => 'karyawan',
        ]);

        User::create([
            'name' => 'Herman',
            'email' => 'herman@example.com',
            'password' => Hash::make('password'),
            'role' => 'karyawan',
        ]);

        User::create([
            'name' => 'Udin',
            'email' => 'udin@example.com',
            'password' => Hash::make('password'),
            'role' => 'karyawan',
        ]);

        User::create([
            'name' => 'Ravi',
            'email' => 'ravi@example.com',
            'password' => Hash::make('password'),
            'role' => 'pejabat',
        ]);

        User::create([
            'name' => 'Dhabit',
            'email' => 'dhabit@example.com',
            'password' => Hash::make('password'),
            'role' => 'atasan_pejabat',
        ]);

        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
    }
}