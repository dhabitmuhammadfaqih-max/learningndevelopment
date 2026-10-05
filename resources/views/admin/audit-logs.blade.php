<x-dashboard-layout title="Audit Log">

@php
    $roleLabels = ['pegawai' => 'Pegawai', 'pejabat' => 'Pejabat', 'hrd' => 'HRD'];

    // Tampilan satu nilai kolom di tabel perubahan.
    $fmt = function ($v) {
        if ($v === null) return '—';
        if (is_bool($v))  return $v ? 'ya' : 'tidak';
        if (is_scalar($v)) return (string) $v;
        return json_encode($v, JSON_UNESCAPED_UNICODE);
    };

    $activeFilters = array_filter($filters);
@endphp

<div class="max-w-6xl mx-auto px-4 py-6">

    <div class="mb-5">
        <h1 class="text-xl font-bold text-slate-800">Audit Log</h1>
        <p class="text-sm text-slate-500 mt-1">
            Catatan siapa melakukan apa, kapan, dan dari mana: perubahan data, login/logout,
            unduh PDF, dan aksi penting HRD. Catatan bersifat <strong>hanya-baca</strong> &mdash;
            tidak bisa diubah atau dihapus dari halaman ini. Kata sandi dan kode pertemuan
            <strong>tidak pernah</strong> ditampilkan.
        </p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 text-red-700 text-sm px-4 py-3">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- ========== FILTER ========== --}}
    <form method="GET" action="{{ route('admin.audit-logs') }}"
          class="rounded-xl border border-slate-200 bg-white p-4 mb-4">

        @if (! empty($filters['actor']))
            <input type="hidden" name="actor" value="{{ $filters['actor'] }}">
        @endif

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <label for="f-q" class="block text-xs font-semibold text-slate-500 mb-1">Cari</label>
                <input id="f-q" type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                       placeholder="Nama pengguna, objek, atau deskripsi"
                       class="w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="f-event" class="block text-xs font-semibold text-slate-500 mb-1">Aksi</label>
                <select id="f-event" name="event"
                        class="w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Semua aksi</option>
                    @foreach ($events as $value => [$label])
                        <option value="{{ $value }}" @selected(($filters['event'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-2">
                <label for="f-type" class="block text-xs font-semibold text-slate-500 mb-1">Jenis objek</label>
                <select id="f-type" name="type"
                        class="w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Semua jenis</option>
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['type'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="f-from" class="block text-xs font-semibold text-slate-500 mb-1">Dari tanggal</label>
                <input id="f-from" type="date" name="from" value="{{ $filters['from'] ?? '' }}"
                       class="w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="f-to" class="block text-xs font-semibold text-slate-500 mb-1">Sampai tanggal</label>
                <input id="f-to" type="date" name="to" value="{{ $filters['to'] ?? '' }}"
                       class="w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 mt-4">
            <button type="submit"
                    class="inline-flex items-center justify-center rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2.5 transition">
                Terapkan
            </button>

            @if ($activeFilters)
                <a href="{{ route('admin.audit-logs') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white hover:border-slate-300 text-slate-600 text-sm font-semibold px-5 py-2.5 transition">
                    Reset
                </a>
            @endif

            <a href="{{ route('admin.audit-logs.export', $activeFilters) }}"
               class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white hover:border-slate-300 text-slate-600 text-sm font-semibold px-5 py-2.5 transition sm:ml-auto">
                Unduh CSV
            </a>
        </div>
    </form>

    @if ($actorFilter)
        <div class="mb-4 flex items-center gap-2 text-sm">
            <span class="text-slate-500">Hanya aktivitas oleh:</span>
            <a href="{{ route('admin.audit-logs', \Illuminate\Support\Arr::except($activeFilters, 'actor')) }}"
               class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 border border-blue-200 text-blue-700 text-xs font-semibold px-3 py-1.5">
                {{ $actorFilter }}
                <span aria-hidden="true">&times;</span>
                <span class="sr-only">hapus filter pengguna</span>
            </a>
        </div>
    @endif

    <p class="text-sm text-slate-600 mb-3">
        <strong>{{ number_format($logs->total(), 0, ',', '.') }}</strong> catatan
        {{ $activeFilters ? 'sesuai filter' : '' }}.
    </p>

    {{-- ========== DAFTAR ========== --}}
    @if ($logs->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-400">
            Belum ada catatan{{ $activeFilters ? ' untuk filter ini' : '' }}.
        </div>
    @else
        <div class="rounded-xl border border-slate-200 bg-white overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-xs font-semibold text-slate-500 text-left">
                    <tr>
                        <th class="px-4 py-2.5 whitespace-nowrap">Waktu</th>
                        <th class="px-4 py-2.5">Pengguna</th>
                        <th class="px-4 py-2.5">Aksi</th>
                        <th class="px-4 py-2.5">Aktivitas</th>
                        <th class="px-4 py-2.5 whitespace-nowrap">IP</th>
                        <th class="px-4 py-2.5"><span class="sr-only">Detail</span></th>
                    </tr>
                </thead>

                @foreach ($logs as $log)
                    @php
                        $hasDetail = $log->old_values || $log->new_values || $log->meta || $log->url;
                        $keys = array_unique(array_merge(
                            array_keys($log->old_values ?? []),
                            array_keys($log->new_values ?? [])
                        ));
                    @endphp

                    <tbody x-data="{ open: false }" class="border-t border-slate-100">
                        <tr class="align-top hover:bg-slate-50/60">
                            <td class="px-4 py-3 whitespace-nowrap text-slate-600">
                                {{ $log->created_at?->translatedFormat('d M Y') }}
                                <span class="block text-xs text-slate-400">{{ $log->created_at?->format('H:i:s') }}</span>
                            </td>

                            <td class="px-4 py-3">
                                @if ($log->actor_id)
                                    <a href="{{ route('admin.audit-logs', array_merge($activeFilters, ['actor' => $log->actor_id])) }}"
                                       class="font-semibold text-slate-700 hover:text-blue-600">{{ $log->actor_name }}</a>
                                @else
                                    <span class="font-semibold text-slate-500">{{ $log->actor_name ?? 'Sistem' }}</span>
                                @endif
                                @if ($log->actor_role)
                                    <span class="block text-xs text-slate-400">{{ $roleLabels[$log->actor_role] ?? $log->actor_role }}</span>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <span class="inline-block rounded-full border text-xs font-semibold px-2.5 py-1 whitespace-nowrap {{ $log->eventBadgeClass() }}">
                                    {{ $log->eventLabel() }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-slate-700">
                                @if ($log->typeLabel())
                                    <span class="block text-xs text-slate-400">{{ $log->typeLabel() }}</span>
                                @endif
                                {{ $log->description }}
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap text-slate-500">{{ $log->ip_address ?? '—' }}</td>

                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                @if ($hasDetail)
                                    <button type="button" @click="open = !open"
                                            :aria-expanded="open.toString()"
                                            class="text-xs font-semibold text-blue-600 hover:text-blue-800">
                                        <span x-text="open ? 'Tutup' : 'Detail'">Detail</span>
                                    </button>
                                @endif
                            </td>
                        </tr>

                        @if ($hasDetail)
                            <tr x-show="open" x-cloak>
                                <td colspan="6" class="px-4 pb-4 pt-0 bg-slate-50/60">
                                    <div class="rounded-lg border border-slate-200 bg-white p-4 space-y-4">

                                        @if ($keys)
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full text-xs">
                                                    <thead class="text-slate-400 text-left">
                                                        <tr>
                                                            <th class="py-1.5 pr-4 font-semibold">Kolom</th>
                                                            @if ($log->old_values)
                                                                <th class="py-1.5 pr-4 font-semibold">Sebelum</th>
                                                            @endif
                                                            @if ($log->new_values)
                                                                <th class="py-1.5 font-semibold">{{ $log->old_values ? 'Sesudah' : 'Nilai' }}</th>
                                                            @endif
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100">
                                                        @foreach ($keys as $key)
                                                            <tr class="align-top">
                                                                <td class="py-1.5 pr-4 font-semibold text-slate-600 whitespace-nowrap">{{ \Illuminate\Support\Str::headline($key) }}</td>
                                                                @if ($log->old_values)
                                                                    <td class="py-1.5 pr-4 text-red-700 break-all">{{ $fmt($log->old_values[$key] ?? null) }}</td>
                                                                @endif
                                                                @if ($log->new_values)
                                                                    <td class="py-1.5 text-green-700 break-all">{{ $fmt($log->new_values[$key] ?? null) }}</td>
                                                                @endif
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif

                                        @if ($log->meta)
                                            <div>
                                                <p class="text-xs font-semibold text-slate-400 mb-1">Info tambahan</p>
                                                <pre class="text-xs text-slate-600 bg-slate-50 rounded-lg p-3 overflow-x-auto whitespace-pre-wrap break-all">{{ json_encode($log->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                            </div>
                                        @endif

                                        @if ($log->url)
                                            <p class="text-xs text-slate-400 break-all">
                                                {{ $log->method }} {{ $log->url }}
                                                @if ($log->user_agent)
                                                    &middot; {{ $log->user_agent }}
                                                @endif
                                            </p>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                @endforeach
            </table>
        </div>

        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    @endif

</div>

</x-dashboard-layout>
