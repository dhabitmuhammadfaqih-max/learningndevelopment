<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Akun karyawan: login pakai username, password = NIK.
        // Kolom "email" tetap wajib & unik di database, jadi diisi placeholder
        // dari username (karyawan tidak pernah pakai email ini).
        User::create([
            'name'     => 'Amin',
            'username' => 'amin',
            'nik'      => '1000001',
            'email'    => 'amin@karyawan.local',
            'password' => Hash::make('1000001'),
            'role'     => 'karyawan',
        ]);

        User::create([
            'name'     => 'Mieke',
            'username' => 'mieke',
            'nik'      => '1000002',
            'email'    => 'mieke@karyawan.local',
            'password' => Hash::make('1000002'),
            'role'     => 'karyawan',
        ]);

        User::create([
            'name'     => 'Herman',
            'username' => 'herman',
            'nik'      => '1000003',
            'email'    => 'herman@karyawan.local',
            'password' => Hash::make('1000003'),
            'role'     => 'karyawan',
        ]);

        User::create([
            'name'     => 'Udin',
            'username' => 'udin',
            'nik'      => '1000004',
            'email'    => 'udin@karyawan.local',
            'password' => Hash::make('1000004'),
            'role'     => 'karyawan',
        ]);

        // Akun non-karyawan: tetap punya username supaya bisa login juga
        // (login sekarang pakai username, bukan email lagi).
        User::create([
            'name'     => 'Ravi',
            'username' => 'ravi',
            'email'    => 'ravi@example.com',
            'password' => Hash::make('password'),
            'role'     => 'pejabat',
        ]);

        User::create([
            'name'     => 'Dhabit',
            'username' => 'dhabit',
            'email'    => 'dhabit@example.com',
            'password' => Hash::make('password'),
            'role'     => 'atasan_pejabat',
        ]);

        User::create([
            'name'     => 'Admin',
            'username' => 'admin',
            'email'    => 'admin@example.com',
            'password' => Hash::make('password'),
            'role'     => 'admin',
        ]);
    }
}
