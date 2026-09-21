<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Feedback;
use App\Models\KorelasiAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Logika bersama halaman "Atur Korelasi": PENILAI/ATASAN menentukan
 * siapa saja yang menjadi KORELASI satu akun binaannya, yaitu yang
 * MEMBERI TANGGAPAN kepada akun binaan itu
 * (App\Models\KorelasiAssignment: reviewer_id = pemberi tanggapan,
 * target_id = akun binaan yang ditanggapi).
 *
 * Dipakai oleh:
 * - OfficialController::korelasi()/updateKorelasi() - Penilai atur
 *   korelasi PEGAWAI binaannya (rekan sesama pegawai).
 * - SupervisorController::korelasi()/updateKorelasi() - Atasan atur
 *   korelasi PEJABAT binaannya (pejabat lain).
 *
 * $targetRole ('pegawai' | 'pejabat') = role orang yang boleh dipilih
 * sebagai korelasi; selalu sama dengan role akun yang diatur.
 */
trait ManagesKorelasi
{
    /**
     * Jumlah minimal korelasi (pemberi tanggapan) yang harus dipilih:
     * - PEGAWAI  : User::MIN_TANGGAPAN_KORELASI (3), kecuali akun SPG
     *              (korelasi opsional).
     * - PEJABAT  : User::MIN_TANGGAPAN_KORELASI_PEJABAT (3).
     * Sama dengan syarat tanggapan yang harus diterima sebelum boleh
     * dinilai. Dibatasi jumlah kandidat yang ada supaya syarat tidak
     * mustahil dipenuhi.
     */
    protected function minKorelasiFor(User $subject, string $targetRole): int
    {
        if ($targetRole === 'pejabat') {
            $min = User::MIN_TANGGAPAN_KORELASI_PEJABAT;
        } elseif (! $subject->is_spg) {
            $min = User::MIN_TANGGAPAN_KORELASI;
        } else {
            return 0;
        }

        $pool = User::where('role', $targetRole)
            ->where('id', '!=', $subject->id)
            ->count();

        return min($min, $pool);
    }

    protected function renderKorelasiPage(User $subject, string $targetRole, string $formAction)
    {
        // Korelasi yang sudah dipilih = orang-orang yang akan memberi
        // tanggapan KE $subject.
        $selectedIds = KorelasiAssignment::where('target_id', $subject->id)
            ->pluck('reviewer_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        // Yang SUDAH pernah memberi tanggapan ke $subject dikunci di
        // halaman (tidak bisa di-uncheck) supaya daftar tidak
        // bertentangan dengan tanggapan yang sudah terkirim.
        $givenIds = Feedback::where('employee_id', $subject->id)
            ->pluck('reviewer_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        $candidates = User::where('role', $targetRole)
            ->where('id', '!=', $subject->id)
            ->orderBy('name')
            ->get(['id', 'name', 'username', 'jabatan', 'unit_kerja'])
            ->map(fn ($u) => [
                'id'     => (int) $u->id,
                'name'   => $u->name,
                'sub'    => trim(($u->jabatan ?: '') . ($u->jabatan && $u->unit_kerja ? ' · ' : '') . ($u->unit_kerja ?: '')),
                'unit'   => $u->unit_kerja ?: 'Tanpa Unit',
                'locked' => in_array((int) $u->id, $givenIds, true),
            ])
            ->values();

        $units = $candidates->pluck('unit')->unique()->sort()->values();
        $employee = $subject;
        $targetLabel = $targetRole === 'pejabat' ? 'pejabat' : 'pegawai';
        $minKorelasi = $this->minKorelasiFor($subject, $targetRole);

        return view('official.korelasi', compact(
            'employee', 'candidates', 'selectedIds', 'units', 'formAction', 'targetLabel', 'minKorelasi'
        ));
    }

    /**
     * Simpan daftar korelasi (pemberi tanggapan) untuk $subject. Daftar
     * lama DIGANTI dengan yang dikirim, kecuali orang yang sudah pernah
     * memberi tanggapan ke $subject - itu selalu dipertahankan.
     */
    protected function saveKorelasi(Request $request, User $subject, string $targetRole, string $redirectUrl)
    {
        $validated = $request->validate([
            'targets'   => 'nullable|array',
            'targets.*' => 'integer|distinct',
        ]);

        $requested = collect($validated['targets'] ?? [])->map(fn ($v) => (int) $v);

        // Hanya akun ber-role sama yang valid (bukan diri sendiri).
        $validIds = User::where('role', $targetRole)
            ->where('id', '!=', $subject->id)
            ->whereIn('id', $requested)
            ->pluck('id')
            ->map(fn ($v) => (int) $v);

        $lockedIds = Feedback::where('employee_id', $subject->id)
            ->pluck('reviewer_id')
            ->map(fn ($v) => (int) $v);

        $finalIds = $validIds->merge($lockedIds)->unique()->values();

        // Penilai wajib menunjuk minimal 3 korelasi untuk pegawai (kecuali
        // SPG) - lihat minKorelasiFor().
        $minKorelasi = $this->minKorelasiFor($subject, $targetRole);
        if ($finalIds->count() < $minKorelasi) {
            return back()
                ->withErrors([
                    'targets' => 'Korelasi untuk ' . $subject->name . ' minimal ' . $minKorelasi
                        . ' orang. Saat ini baru ' . $finalIds->count() . ' yang dipilih.',
                ])
                ->withInput();
        }

        DB::transaction(function () use ($subject, $finalIds) {
            KorelasiAssignment::where('target_id', $subject->id)
                ->whereNotIn('reviewer_id', $finalIds)
                ->delete();

            $existing = KorelasiAssignment::where('target_id', $subject->id)
                ->pluck('reviewer_id')
                ->map(fn ($v) => (int) $v);

            foreach ($finalIds->diff($existing) as $reviewerId) {
                KorelasiAssignment::create([
                    'reviewer_id' => $reviewerId,
                    'target_id'   => $subject->id,
                    'assigned_by' => Auth::id(),
                ]);
            }
        });

        return redirect($redirectUrl)
            ->with('success', 'Korelasi untuk ' . $subject->name . ' disimpan (' . $finalIds->count() . ' orang).');
    }
}