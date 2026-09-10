<?php

namespace App\Http\Concerns;

use App\Models\Evaluation;
use App\Models\OfficialEvaluation;
use App\Models\SupervisorFeedback;
use Illuminate\Http\Request;

/**
 * Selector Tahun (bisa dipakai ulang tiap tahun).
 *
 * Semua data penilaian (Evaluation, OfficialEvaluation, SupervisorFeedback)
 * sudah disimpan per tahun lewat kolom `tahun` (lihat migration
 * 2026_08_24_000000_add_tahun_to_evaluation_tables). Trait ini menyediakan
 * dua hal yang dipakai bareng oleh controller mana pun yang perlu
 * menampilkan/memfilter data berdasarkan tahun:
 *
 * - selectedTahun(): baca tahun yang sedang dipilih user dari query string
 *   (?tahun=2025), default ke tahun berjalan kalau tidak diisi/tidak valid.
 * - availableTahunOptions(): daftar tahun yang muncul di dropdown selector,
 *   diambil dari tahun-tahun yang benar-benar ada datanya + tahun berjalan
 *   (supaya tetap muncul walau belum ada data sama sekali, mis. di awal
 *   tahun baru sebelum ada penilaian pertama).
 *
 * Dengan begini, fitur "selector tahun" otomatis reusable tiap tahun:
 * begitu kalender berganti tahun, tahun baru otomatis jadi default & masuk
 * daftar pilihan tanpa perlu ubah kode apa pun.
 */
trait FiltersByTahun
{
    /**
     * Tahun yang sedang aktif dipilih di halaman (dari ?tahun=... di URL).
     * Selalu di-clamp ke rentang wajar supaya tidak bisa dipakai untuk
     * query aneh-aneh (mis. ?tahun=abc atau ?tahun=99999).
     */
    protected function selectedTahun(Request $request): int
    {
        // Pakai input() (bukan query()) supaya bisa dibaca juga dari body
        // request POST (mis. form tanda tangan HRD di admin.detail yang
        // mengirim <input type="hidden" name="tahun">), bukan cuma dari
        // query string ?tahun=... di URL GET seperti pemakaian lain trait
        // ini. Untuk request GET, hasilnya sama persis dengan query().
        //
        // Default (kalau tidak ada ?tahun= sama sekali) pakai periode
        // aktif yang di-set HRD (App\Support\ActivePeriod), BUKAN
        // now()->year - supaya begitu HRD buka halaman tanpa pilih
        // tahun, yang muncul otomatis periode yang masih berjalan
        // (mis. Januari-Februari masih menampilkan tahun lalu kalau
        // periode itu belum ditutup), bukan tahun kalender yang mungkin
        // belum ada datanya sama sekali.
        $tahun = (int) $request->input('tahun', \App\Support\ActivePeriod::year());

        if ($tahun < 2000 || $tahun > 2100) {
            $tahun = \App\Support\ActivePeriod::year();
        }

        return $tahun;
    }

    /**
     * Daftar tahun untuk dropdown selector, urut dari terbaru ke terlama.
     * Diambil dari gabungan tahun yang ada di ketiga tabel penilaian, lalu
     * dipastikan tahun berjalan selalu ikut ada meskipun belum ada data
     * sama sekali untuk tahun itu.
     */
    protected function availableTahunOptions(): array
    {
        $tahunList = Evaluation::query()->distinct()->pluck('tahun')
            ->merge(OfficialEvaluation::query()->distinct()->pluck('tahun'))
            ->merge(SupervisorFeedback::query()->distinct()->pluck('tahun'))
            ->push(now()->year)
            ->push(\App\Support\ActivePeriod::year())
            ->map(fn ($tahun) => (int) $tahun)
            ->unique()
            ->sortDesc()
            ->values();

        return $tahunList->all();
    }
}
