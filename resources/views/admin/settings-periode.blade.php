<x-dashboard-layout title="Pengaturan Periode">

    <div class="max-w-xl mx-auto">

        @if (session('success'))
            <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 text-sm px-4 py-3">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-2xl border border-slate-100 p-6 sm:p-8">
            <h2 class="text-lg font-bold text-slate-800 mb-1">Periode Penilaian Aktif</h2>
            <p class="text-sm text-slate-500 mb-6 leading-relaxed">
                Tahun ini yang dipakai sebagai default untuk semua pegawai & pejabat saat membuka
                dashboard mereka (bukan sekadar mengikuti tahun kalender). Ubah manual di sini
                <strong>hanya setelah</strong> seluruh proses penilaian &amp; tanggapan untuk tahun
                berjalan benar-benar selesai — misalnya kalau siklus penilaian baru kelar Februari
                tahun berikutnya, biarkan periode tetap di tahun sebelumnya sampai memang siap ditutup.
            </p>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <p class="text-xs text-slate-400 mb-1">Periode Aktif Saat Ini</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $activeYear }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <p class="text-xs text-slate-400 mb-1">Tahun Kalender</p>
                    <p class="text-2xl font-bold text-slate-400">{{ $calendarYear }}</p>
                </div>
            </div>

            @if ($activeYear !== $calendarYear)
                <div class="mb-6 rounded-xl bg-amber-50 border border-amber-100 text-amber-700 text-xs px-4 py-3">
                    Periode aktif berbeda dari tahun kalender — ini normal kalau siklus penilaian
                    tahun {{ $activeYear }} masih berjalan sampai sekarang.
                </div>
            @endif

            <form method="POST" action="{{ route('admin.settings.periode.update') }}" class="flex items-end gap-3">
                @csrf
                <div class="flex-1">
                    <label for="tahun" class="block text-xs font-semibold text-slate-600 mb-1.5">
                        Ubah Periode Aktif Ke
                    </label>
                    <input type="number" name="tahun" id="tahun" min="2000" max="2100"
                           value="{{ old('tahun', $activeYear) }}"
                           class="w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('tahun')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit"
                        onclick="return confirmDialog(event, 'Yakin ubah periode aktif? Semua pegawai/pejabat akan langsung melihat tahun ini sebagai default begitu simpan.')"
                        class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition">
                    Simpan
                </button>
            </form>
        </div>

        {{-- Bersihkan foto bukti checklist periode lama --}}
        <div class="bg-white rounded-2xl border border-slate-100 p-6 sm:p-8 mt-6">
            <h2 class="text-lg font-bold text-slate-800 mb-1">Bersihkan Foto Bukti Checklist Periode Lama</h2>
            <p class="text-sm text-slate-500 mb-6 leading-relaxed">
                Hapus foto bukti checklist pertemuan (selfie/upload) untuk periode yang <strong>sudah lewat</strong>
                dari periode aktif, supaya storage server tidak terus bertambah. Status "sudah checklist" untuk
                periode tersebut <strong>tetap tersimpan selamanya</strong> di riwayat - yang dihapus murni foto
                buktinya, bukan datanya. <strong>Tindakan ini tidak bisa dibatalkan.</strong>
            </p>

            @if ($yearsWithEvidence->isEmpty())
                <p class="text-sm text-slate-400 bg-slate-50 rounded-xl px-4 py-6 text-center">
                    Tidak ada foto dari periode lama yang perlu dibersihkan saat ini.
                </p>
            @else
                <div class="mb-5 space-y-2">
                    @foreach ($yearsWithEvidence as $tahun => $jumlah)
                        <div class="flex items-center justify-between text-sm bg-slate-50 rounded-xl px-4 py-2.5">
                            <span class="font-medium text-slate-700">Periode {{ $tahun }}</span>
                            <span class="text-slate-400">{{ $jumlah }} foto</span>
                        </div>
                    @endforeach
                </div>

                <form method="POST" action="{{ route('admin.settings.periode.purge-evidence') }}"
                      onsubmit="return confirmDialog(event, 'Yakin? Foto yang sudah dihapus TIDAK BISA dikembalikan lagi.');"
                      class="border-t border-slate-100 pt-5 space-y-4">
                    @csrf

                    <div>
                        <label for="tahun_purge" class="block text-xs font-semibold text-slate-600 mb-1.5">
                            Pilih Periode yang Mau Dibersihkan
                        </label>
                        <select name="tahun" id="tahun_purge" required
                                class="w-full rounded-xl border-slate-200 text-sm focus:border-red-500 focus:ring-red-500">
                            <option value="">-- Pilih tahun --</option>
                            @foreach ($yearsWithEvidence as $tahun => $jumlah)
                                <option value="{{ $tahun }}">{{ $tahun }} ({{ $jumlah }} foto)</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="konfirmasi" class="block text-xs font-semibold text-slate-600 mb-1.5">
                            Ketik <span class="font-mono bg-slate-100 px-1.5 py-0.5 rounded">HAPUS</span> untuk konfirmasi
                        </label>
                        <input type="text" name="konfirmasi" id="konfirmasi" required autocomplete="off"
                               placeholder="HAPUS"
                               class="w-full rounded-xl border-slate-200 text-sm focus:border-red-500 focus:ring-red-500">
                        @error('konfirmasi')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="w-full px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-semibold transition">
                        Hapus Foto Periode Terpilih
                    </button>
                </form>
            @endif
        </div>
    </div>

</x-dashboard-layout>
