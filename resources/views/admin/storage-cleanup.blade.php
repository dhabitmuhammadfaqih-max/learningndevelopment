<x-dashboard-layout title="Bersihkan File Lama">

@php
    $filters = [
        null       => 'Semua',
        '1_bulan'  => '> 1 bulan lalu',
        '3_bulan'  => '> 3 bulan lalu',
        '6_bulan'  => '> 6 bulan lalu',
        '1_tahun'  => '> 1 tahun lalu',
    ];
@endphp

<div class="max-w-4xl mx-auto px-4 py-6">

    <div class="mb-5">
        <h1 class="text-xl font-bold text-slate-800">Bersihkan File Lama</h1>
        <p class="text-sm text-slate-500 mt-1">
            Daftar file selfie/tanda tangan yang <strong>tidak lagi dirujuk data manapun</strong>
            (sudah dihapus/diganti recordnya) - aman dihapus. File yang masih dipakai
            TIDAK PERNAH muncul di sini, walau umurnya sudah lama.
        </p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-xl border border-green-200 bg-green-50 text-green-700 text-sm px-4 py-3">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-wrap items-center gap-2 mb-5">
        @foreach ($filters as $value => $label)
            <a
                href="{{ route('admin.storage-cleanup', $value ? ['older_than' => $value] : []) }}"
                class="text-xs font-semibold px-3.5 py-2 rounded-full border transition
                    {{ $olderThan === $value ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300' }}"
            >
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="flex items-center justify-between mb-3">
        <p class="text-sm text-slate-600">
            <strong>{{ $orphans->count() }}</strong> file orphan ditemukan,
            total <strong>{{ $totalSizeFormatted }}</strong>.
        </p>
    </div>

    @if ($orphans->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-400">
            Tidak ada file orphan {{ $olderThan ? 'untuk filter ini' : '' }}. 👍
        </div>
    @else
        <form method="POST" action="{{ route('admin.storage-cleanup.destroy') }}" id="form-storage-cleanup">
            @csrf

            <div class="rounded-xl border border-slate-200 divide-y divide-slate-100 overflow-hidden mb-4">
                <div class="flex items-center gap-3 px-4 py-2.5 bg-slate-50 text-xs font-semibold text-slate-500">
                    <input type="checkbox" id="select-all">
                    <label for="select-all" class="cursor-pointer">Pilih Semua</label>
                </div>

                @foreach ($orphans as $file)
                    <label class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-slate-50 cursor-pointer">
                        <input type="checkbox" name="paths[]" value="{{ $file['path'] }}" class="row-checkbox shrink-0">
                        <span class="flex-1 min-w-0">
                            <span class="block truncate text-slate-700">{{ $file['path'] }}</span>
                            <span class="block text-xs text-slate-400 mt-0.5">
                                {{ $file['size_formatted'] }}
                                &middot;
                                terakhir diubah {{ $file['modified_at']->translatedFormat('d M Y H:i') }}
                                ({{ $file['modified_at']->diffForHumans() }})
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>

            <button
                type="submit"
                id="btn-delete-selected"
                disabled
                onclick="return confirm('Yakin hapus file yang dipilih? Aksi ini tidak bisa dibatalkan.');"
                class="inline-flex items-center justify-center rounded-xl bg-red-600 hover:bg-red-700 disabled:bg-slate-300 disabled:cursor-not-allowed text-white text-sm font-semibold px-5 py-2.5 transition"
            >
                Hapus File Terpilih
            </button>
        </form>

        <script>
            (function () {
                const selectAll = document.getElementById('select-all');
                const rowCheckboxes = Array.from(document.querySelectorAll('.row-checkbox'));
                const deleteBtn = document.getElementById('btn-delete-selected');

                function updateDeleteBtn() {
                    deleteBtn.disabled = ! rowCheckboxes.some((cb) => cb.checked);
                }

                selectAll.addEventListener('change', () => {
                    rowCheckboxes.forEach((cb) => { cb.checked = selectAll.checked; });
                    updateDeleteBtn();
                });

                rowCheckboxes.forEach((cb) => cb.addEventListener('change', updateDeleteBtn));
            })();
        </script>
    @endif

</div>

</x-dashboard-layout>
