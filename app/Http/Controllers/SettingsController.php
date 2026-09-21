<?php

namespace App\Http\Controllers;

use App\Support\ActivePeriod;
use Illuminate\Http\Request;

/**
 * Halaman "Pengaturan Periode" - satu-satunya tempat HRD mengubah tahun
 * penilaian yang sedang aktif (lihat App\Support\ActivePeriod untuk
 * alasan kenapa ini tidak lagi otomatis ikut kalender).
 *
 * Sengaja dibuat controller terpisah (bukan ditumpuk ke HrdController
 * yang sudah sangat besar) karena ini "pengaturan aplikasi", bukan
 * bagian dari alur penilaian itu sendiri.
 */
class SettingsController extends Controller
{
    public function periode()
    {
        $activeYear = ActivePeriod::year();
        $calendarYear = now()->year;

        // Tahun-tahun LAMA (sebelum periode aktif) yang masih punya sisa
        // foto bukti checklist - ditampilkan supaya HRD tahu ada apa aja
        // yang bisa dibersihkan, tanpa perlu nebak-nebak tahun mana saja.
        $yearsWithEvidence = collect();

        foreach (self::CHECKLIST_EVIDENCE_COLUMNS as [$tahunCol, $selfieCol]) {
            \DB::table('users')
                ->select($tahunCol . ' as tahun', \DB::raw('COUNT(*) as jumlah'))
                ->whereNotNull($selfieCol)
                ->whereNotNull($tahunCol)
                ->where($tahunCol, '<', $activeYear)
                ->groupBy($tahunCol)
                ->get()
                ->each(function ($row) use (&$yearsWithEvidence) {
                    $yearsWithEvidence[$row->tahun] = ($yearsWithEvidence[$row->tahun] ?? 0) + $row->jumlah;
                });
        }

        $yearsWithEvidence = $yearsWithEvidence->sortKeysDesc();

        return view('admin.settings-periode', compact('activeYear', 'calendarYear', 'yearsWithEvidence'));
    }

    public function updatePeriode(Request $request)
    {
        $validated = $request->validate([
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
        ], [
            'tahun.required' => 'Tahun periode wajib diisi.',
            'tahun.integer'  => 'Tahun periode harus berupa angka.',
        ]);

        ActivePeriod::setYear($validated['tahun']);

        return back()->with('success', "Periode penilaian aktif berhasil diubah ke tahun {$validated['tahun']}.");
    }

    /**
     * Kolom checklist pertemuan yang punya pasangan (tahun, path foto
     * bukti) - 4 role yang bisa jadi pihak yang konfirmasi checklist.
     * Lihat migration add_selfie_to_checklist_pertemuan_columns &
     * add_tahun_to_checklist_pertemuan_columns untuk asal kolom ini.
     *
     * SENGAJA hanya nge-null-kan kolom *_selfie (path foto), BUKAN kolom
     * *_at / *_tahun - supaya status "sudah checklist" untuk periode
     * lama itu TETAP tercatat true selamanya (lihat
     * User::pegawaiSudahKonfirmasiPertemuan() dkk, yang cuma cek _at &
     * _tahun, tidak cek keberadaan foto). Yang dihapus murni file foto
     * buktinya untuk hemat storage, bukan riwayat bahwa checklist itu
     * pernah dilakukan.
     */
    private const CHECKLIST_EVIDENCE_COLUMNS = [
        ['pegawai_konfirmasi_pertemuan_tahun', 'pegawai_konfirmasi_pertemuan_selfie'],
        ['penilai_konfirmasi_pertemuan_tahun', 'penilai_konfirmasi_pertemuan_selfie'],
        ['pejabat_konfirmasi_pertemuan_tahun', 'pejabat_konfirmasi_pertemuan_selfie'],
        ['atasan_konfirmasi_pertemuan_tahun', 'atasan_konfirmasi_pertemuan_selfie'],
    ];

    /**
     * Hapus SEMUA foto bukti checklist pertemuan untuk satu tahun
     * tertentu, sekaligus dari disk & dari database (kolom *_selfie
     * di-null-kan). Dipakai HRD untuk bebasin storage dari foto
     * periode lama yang sudah pasti tidak akan dibuka-buka lagi.
     *
     * PROTEKSI: hanya boleh untuk tahun yang SUDAH LEWAT dari periode
     * aktif saat ini (lihat ActivePeriod) - tidak bisa hapus foto
     * periode yang masih berjalan, walaupun secara kalender sudah
     * ganti tahun. Ini sengaja lebih ketat daripada sekadar "< tahun
     * kalender", karena periode aktif itu sendiri yang jadi acuan
     * "sudah selesai belum", bukan tanggal semata (lihat diskusi di
     * App\Support\ActivePeriod).
     */
    public function purgeChecklistEvidence(Request $request)
    {
        $validated = $request->validate([
            'tahun'      => ['required', 'integer', 'min:2000', 'max:2100'],
            'konfirmasi' => ['required', 'in:HAPUS'],
        ], [
            'tahun.required'      => 'Tahun yang mau dibersihkan wajib diisi.',
            'konfirmasi.required' => 'Ketik "HAPUS" untuk konfirmasi.',
            'konfirmasi.in'       => 'Konfirmasi tidak sesuai - ketik persis "HAPUS" (huruf besar semua).',
        ]);

        $tahun = $validated['tahun'];

        if ($tahun >= ActivePeriod::year()) {
            return back()->withErrors([
                'tahun' => 'Hanya bisa membersihkan foto untuk periode yang sudah lewat dari periode aktif saat ini (' . ActivePeriod::year() . '). Tutup dulu periode ini lewat form di atas kalau memang sudah selesai.',
            ])->withInput();
        }

        $deletedCount = 0;
        $freedBytes = 0;

        foreach (self::CHECKLIST_EVIDENCE_COLUMNS as [$tahunCol, $selfieCol]) {
            $paths = \DB::table('users')
                ->where($tahunCol, $tahun)
                ->whereNotNull($selfieCol)
                ->pluck($selfieCol);

            foreach ($paths as $path) {
                if (\Storage::disk('public')->exists($path)) {
                    $freedBytes += \Storage::disk('public')->size($path);
                    \Storage::disk('public')->delete($path);
                }
                $deletedCount++;
            }

            \DB::table('users')
                ->where($tahunCol, $tahun)
                ->whereNotNull($selfieCol)
                ->update([$selfieCol => null]);
        }

        if ($deletedCount === 0) {
            return back()->with('success', "Tidak ada foto bukti checklist yang ditemukan untuk periode {$tahun} - mungkin sudah pernah dibersihkan sebelumnya.");
        }

        $freedFormatted = $this->formatBytes($freedBytes);

        return back()->with('success', "Berhasil menghapus {$deletedCount} foto bukti checklist periode {$tahun}, membebaskan {$freedFormatted} storage.");
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }
}
