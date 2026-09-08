<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Akun pegawai: login pakai username, password = NIK.
        // Kolom "email" tetap wajib & unik di database, jadi diisi placeholder
        // dari username (pegawai tidak pernah pakai email ini).
        User::create([
            'name'     => 'Amin',
            'username' => 'amin',
            'nik'      => '1000001',
            'email'    => 'amin@pegawai.local',
            'password' => Hash::make('1000001'),
            'role'     => 'pegawai',
        ]);

        User::create([
            'name'     => 'Mieke',
            'username' => 'mieke',
            'nik'      => '1000002',
            'email'    => 'mieke@pegawai.local',
            'password' => Hash::make('1000002'),
            'role'     => 'pegawai',
        ]);

        User::create([
            'name'     => 'Herman',
            'username' => 'herman',
            'nik'      => '1000003',
            'email'    => 'herman@pegawai.local',
            'password' => Hash::make('1000003'),
            'role'     => 'pegawai',
        ]);

        User::create([
            'name'     => 'Udin',
            'username' => 'udin',
            'nik'      => '1000004',
            'email'    => 'udin@pegawai.local',
            'password' => Hash::make('1000004'),
            'role'     => 'pegawai',
        ]);

        // Akun non-pegawai: tetap punya username supaya bisa login juga
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
            // Role "atasan_pejabat" sudah digabung ke "pejabat". Dhabit
            // berperan sebagai atasan dari Ravi lewat users.supervisor_id
            // (ditugaskan lewat panel HRD), bukan lewat role terpisah lagi.
            'role'     => 'pejabat',
        ]);

        User::create([
            'name'     => 'HRD',
            'username' => 'hrd',
            'email'    => 'hrd@example.com',
            'password' => Hash::make('password'),
            'role'     => 'hrd',
        ]);

        // Contoh rantai atasan 3 tingkat: Media -> Siti -> Didi.
        // Semua ber-role "pejabat" & akses dashboard yang sama
        // (/pejabat/dashboard, lihat OfficialController::index()), tapi
        // haknya beda karena dua mekanisme yang dipakai independen:
        //
        // 1) "Nilai" (skor) dikontrol lewat users.supervisor_id, dicek di
        //    SupervisorController::evaluateOfficial(). Media.supervisor_id
        //    = Siti->id, jadi HANYA Siti yang bisa menilai Media (lewat
        //    link "Pejabat yang Anda Bina" di dashboard Siti, ke route
        //    supervisor.official). Siti.supervisor_id = Didi->id mengikuti
        //    pola yang sama satu tingkat di atasnya.
        //
        // 2) "Tanggapan" (korelasi antar pejabat) dikontrol lewat
        //    users.unit_kerja, dicek di OfficialController::feedback() &
        //    $peerOfficials (index()) — HANYA pejabat lain di unit_kerja
        //    yang SAMA yang muncul sebagai pilihan tanggapan. Media &
        //    Didi sengaja disatukan di unit_kerja "Direksi", sedangkan
        //    Siti dibedakan ("Sekretariat") supaya Siti TIDAK muncul di
        //    pilihan tanggapan Media — hasilnya Media cuma bisa memberi
        //    tanggapan ke Didi, persis seperti yang diminta.
        $didi = User::create([
            'name'       => 'Didi',
            'username'   => 'didi',
            'email'      => 'didi@example.com',
            'password'   => Hash::make('password'),
            'role'       => 'pejabat',
            'unit_kerja' => 'Direksi',
        ]);

        $siti = User::create([
            'name'          => 'Siti',
            'username'      => 'siti',
            'email'         => 'siti@example.com',
            'password'      => Hash::make('password'),
            'role'          => 'pejabat',
            'unit_kerja'    => 'Sekretariat',
            'supervisor_id' => $didi->id,
        ]);

        User::create([
            'name'          => 'Media',
            'username'      => 'media',
            'email'         => 'media@example.com',
            'password'      => Hash::make('password'),
            'role'          => 'pejabat',
            'unit_kerja'    => 'Direksi',
            'supervisor_id' => $siti->id,
        ]);
    }
}
