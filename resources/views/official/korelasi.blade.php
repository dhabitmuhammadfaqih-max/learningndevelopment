<x-dashboard-layout title="Atur Korelasi - {{ $employee->name }}">

    @php
        // Dashboard tiap role penilai berbeda (pegawai / hrd / pejabat).
        $backUrl = match (auth()->user()->role) {
            'pegawai' => route('employee.dashboard'),
            'hrd'     => route('admin.dashboard'),
            default   => route('official.dashboard'),
        };
    @endphp

    <a href="{{ $backUrl }}"
       class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-slate-800 mb-5 transition">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
        Kembali ke Dashboard
    </a>

    @if (session('success'))
        <div class="mb-6 rounded-2xl bg-emerald-50 text-emerald-700 px-5 py-4 text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-2xl bg-red-50 text-red-700 px-5 py-4 text-sm">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-7 mb-6">
        <h1 class="text-lg font-bold text-slate-800">Atur Korelasi</h1>
        <p class="text-sm text-slate-500 mt-1">
            Pilih {{ $targetLabel === 'pejabat' ? 'pejabat lain' : 'rekan kerja' }} yang menjadi korelasi
            <span class="font-semibold text-slate-700">{{ $employee->name }}</span>,
            yaitu yang akan <span class="font-semibold text-slate-700">memberi tanggapan</span> kepada {{ $employee->name }}.
            Nama yang dicentang akan melihat {{ $employee->name }} di dashboard-nya untuk ditanggapi;
            yang tidak dicentang tidak bisa menanggapi {{ $employee->name }}.
        </p>
        @if ($minKorelasi > 0)
            <div class="mt-3 rounded-xl bg-amber-50 border border-amber-100 text-amber-800 px-4 py-3 text-sm">
                <span class="font-semibold">Minimal {{ $minKorelasi }} korelasi.</span>
                Pilih sedikitnya {{ $minKorelasi }} {{ $targetLabel }} agar {{ $employee->name }} bisa menerima
                {{ $minKorelasi }} tanggapan dan siap dinilai. Simpan tidak bisa dilakukan kalau kurang dari itu.
            </div>
        @elseif ($employee->is_spg)
            <p class="text-xs text-slate-400 mt-3">Akun SPG: jumlah korelasi bersifat opsional (tidak ada minimal).</p>
        @endif
        <p class="text-xs text-slate-400 mt-1">
            {{ $employee->username }}
            @if ($employee->jabatan) &middot; {{ $employee->jabatan }} @endif
            @if ($employee->unit_kerja) &middot; {{ $employee->unit_kerja }} @endif
        </p>
    </div>

    {{-- Seluruh state (pencarian, filter unit, centang) hidup di Alpine.
         Data lewat Js::from supaya aman dari karakter khusus di nama. --}}
    <form method="POST"
          action="{{ $formAction }}"
          x-data="{
              items: {{ \Illuminate\Support\Js::from($candidates) }},
              selected: {{ \Illuminate\Support\Js::from($selectedIds) }},
              min: {{ (int) $minKorelasi }},
              q: '',
              unit: 'all',
              get filtered() {
                  const q = this.q.trim().toLowerCase();
                  return this.items.filter(i =>
                      (this.unit === 'all' || i.unit === this.unit) &&
                      (! q || i.name.toLowerCase().includes(q) || i.sub.toLowerCase().includes(q))
                  );
              },
              get kurang() {
                  return Math.max(0, this.min - this.selected.length);
              },
              get chosen() {
                  return this.items.filter(i => this.selected.includes(i.id));
              },
              isOn(id) { return this.selected.includes(id); },
              toggle(item) {
                  if (item.locked) { return; }
                  this.selected = this.isOn(item.id)
                      ? this.selected.filter(x => x !== item.id)
                      : [...this.selected, item.id];
              },
              selectVisible() {
                  const ids = this.filtered.map(i => i.id);
                  this.selected = [...new Set([...this.selected, ...ids])];
              },
              clearAll() {
                  // Yang terkunci (sudah pernah ditanggapi) tetap tercentang.
                  this.selected = this.items.filter(i => i.locked && this.selected.includes(i.id)).map(i => i.id);
              }
          }"
          class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        @csrf
        @method('PUT')

        {{-- Nilai yang dikirim ke server --}}
        <template x-for="id in selected" :key="id">
            <input type="hidden" name="targets[]" :value="id">
        </template>

        {{-- Kiri: pencarian + checklist --}}
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-7">
            <h3 class="text-sm font-bold text-slate-800 mb-3">Pilih Korelasi (Pemberi Tanggapan)</h3>

            <input type="text"
                   x-model="q"
                   placeholder="Cari nama, jabatan, atau unit..."
                   autocomplete="off"
                   class="w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">

            <select x-model="unit"
                    class="w-full mt-2 rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="all">Semua Unit</option>
                @foreach ($units as $unit)
                    <option value="{{ $unit }}">{{ $unit }}</option>
                @endforeach
            </select>

            <div class="flex flex-wrap items-center gap-2 mt-3">
                <button type="button" x-on:click="selectVisible()"
                        class="text-xs font-semibold px-3 py-1.5 rounded-lg border border-blue-200 text-blue-600 hover:bg-blue-50 transition">
                    Centang semua yang tampil
                </button>
                <button type="button" x-on:click="clearAll()"
                        class="text-xs font-semibold px-3 py-1.5 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 transition">
                    Kosongkan centang
                </button>
                <span class="text-xs text-slate-400 ml-auto">
                    Menampilkan <span x-text="filtered.length"></span> dari <span x-text="items.length"></span> {{ $targetLabel }}
                </span>
            </div>

            <div class="mt-3 max-h-[28rem] overflow-y-auto rounded-xl border border-slate-200 divide-y divide-slate-50">
                <template x-for="item in filtered" :key="item.id">
                    <label class="flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-slate-50"
                           :class="item.locked ? 'cursor-not-allowed' : 'cursor-pointer'">
                        <input type="checkbox"
                               class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                               :checked="isOn(item.id)"
                               :disabled="item.locked"
                               x-on:change="toggle(item)">
                        <span class="flex-1 min-w-0">
                            <span class="block font-medium text-slate-800 truncate" x-text="item.name"></span>
                            <span class="block text-xs text-slate-400 truncate" x-text="item.sub || 'Tanpa jabatan/unit'"></span>
                        </span>
                        <span x-show="item.locked" x-cloak
                              class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600 whitespace-nowrap">
                            Sudah menanggapi
                        </span>
                    </label>
                </template>

                <p x-show="filtered.length === 0" x-cloak class="px-4 py-3 text-sm text-slate-400">
                    Nama tidak ditemukan.
                </p>
            </div>
        </div>

        {{-- Kanan: nama yang sudah dicentang --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 self-start lg:sticky lg:top-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold text-slate-800">Sudah Dicentang</h3>
                <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-blue-50 text-blue-600">
                    <span x-text="chosen.length"></span><template x-if="min > 0"><span> / <span x-text="min"></span> minimal</span></template><template x-if="min === 0"><span> orang</span></template>
                </span>
            </div>

            <p x-show="min > 0 && kurang > 0" x-cloak
               class="mb-3 rounded-lg bg-amber-50 text-amber-700 text-xs font-medium px-3 py-2">
                Kurang <span x-text="kurang"></span> korelasi lagi (minimal <span x-text="min"></span>).
            </p>
            <p x-show="min > 0 && kurang === 0" x-cloak
               class="mb-3 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-medium px-3 py-2">
                Syarat minimal <span x-text="min"></span> korelasi terpenuhi.
            </p>

            <p x-show="chosen.length === 0" x-cloak class="text-sm text-slate-400">
                Belum ada yang dicentang. Belum ada yang bisa memberi tanggapan kepada {{ $employee->name }}.
            </p>

            <ul class="space-y-1.5 max-h-72 overflow-y-auto">
                <template x-for="item in chosen" :key="item.id">
                    <li class="flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-2 text-sm">
                        <span class="flex-1 min-w-0">
                            <span class="block font-medium text-slate-700 truncate" x-text="item.name"></span>
                            <span class="block text-[11px] text-slate-400 truncate" x-text="item.unit"></span>
                        </span>
                        <span x-show="item.locked" x-cloak class="text-[11px] font-semibold text-emerald-600 whitespace-nowrap">&#10003; menanggapi</span>
                        <button type="button"
                                x-show="! item.locked" x-cloak
                                x-on:click="toggle(item)"
                                title="Hapus centang"
                                class="shrink-0 text-slate-400 hover:text-red-500 transition text-base leading-none">&times;</button>
                    </li>
                </template>
            </ul>

            <button type="submit"
                    :disabled="kurang > 0"
                    :class="kurang > 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-700'"
                    class="mt-4 w-full inline-flex items-center justify-center rounded-xl bg-blue-600 text-white text-sm font-semibold px-5 py-2.5 transition">
                Simpan Korelasi
            </button>
            <p class="text-[11px] text-slate-400 mt-2 leading-relaxed">
                Tanggapan yang sudah dikirim tidak bisa dicabut, jadi nama yang sudah menanggapi {{ $employee->name }} selalu tetap tercentang.
            </p>
        </div>
    </form>

</x-dashboard-layout>