@props([
    'title' => 'Dashboard',
])

<?php
    $role = auth()->user()->role ?? null;

    $roleLabel = match ($role) {
        'pegawai' => 'Pegawai',
        'pejabat' => 'Pejabat',
        'hrd'     => 'HRD',
        default   => 'Pengguna',
    };

    $dashboardRoute = match ($role) {
        'pegawai' => 'employee.dashboard',
        'pejabat' => 'official.dashboard',
        'hrd'     => 'admin.dashboard',
        default   => null,
    };

    $navItems = collect([
        [
            'route' => $dashboardRoute,
            'label' => $role === 'hrd' ? 'Ringkasan' : 'Dashboard Saya',
            'icon'  => 'home',
        ],
        $role === 'pejabat' ? [
            'route' => 'official.my-evaluations',
            'label' => 'Nilai Saya',
            'icon'  => 'star',
        ] : null,
        // HRD dulu punya semua ini sebagai tab di dalam satu halaman
        // dashboard (lihat riwayat admin.dashboard) - sekarang dipisah
        // jadi menu sidebar sendiri-sendiri supaya tidak numpuk 1 halaman.
        $role === 'hrd' ? [
            'route' => 'admin.employees',
            'label' => 'Lihat Pegawai',
            'icon'  => 'users',
        ] : null,
        $role === 'hrd' ? [
            'route' => 'admin.officials',
            'label' => 'Lihat Pejabat',
            'icon'  => 'briefcase',
        ] : null,
        $role === 'hrd' ? [
            'route' => 'admin.accounts',
            'label' => 'Semua Akun',
            'icon'  => 'grid',
        ] : null,
    ])->filter()->values();

    // Badge notifikasi "bisa ditanggapi/dinilai" per menu - lihat
    // App\Support\PendingActions untuk definisi lengkap tiap angkanya.
    $pendingActions = \App\Support\PendingActions::forUser(auth()->user());

    $initials = collect(explode(' ', auth()->user()->name ?? '?'))
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->take(2)
        ->implode('');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="autosave-user-id" content="{{ auth()->id() }}">

    <title>{{ $title }} · {{ config('app.name', 'Learning & Development') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-[#eef3fb] text-slate-700">

    <div class="min-h-screen lg:flex">

        <!-- Sidebar -->
        <aside x-data="{ open: false }" class="lg:w-72 lg:flex-shrink-0">
            <!-- Mobile top bar with toggle -->
            <div class="lg:hidden flex items-center justify-between bg-white px-4 py-3 shadow-sm">
                <a href="{{ $dashboardRoute ? route($dashboardRoute) : '/' }}" class="flex items-center gap-2">
                    <img src="{{ asset('images/logo-dagsap.png') }}" alt="PT Dagsap Endura Eatore" class="h-9 w-auto shrink-0">
                    <span class="font-extrabold text-base text-slate-800 leading-tight">Learning &amp; Development</span>
                </a>
                <button @click="open = !open" class="p-2 rounded-lg text-slate-500 hover:bg-slate-100">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>

            <div :class="{ 'block': open, 'hidden': !open }" class="hidden lg:block bg-white lg:min-h-screen lg:sticky lg:top-0 px-6 py-8">

                <!-- Logo -->
                <a href="{{ $dashboardRoute ? route($dashboardRoute) : '/' }}" class="hidden lg:flex items-center gap-3 mb-10">
                    <img src="{{ asset('images/logo-dagsap.png') }}" alt="PT Dagsap Endura Eatore" class="h-12 w-auto shrink-0">
                    <span class="font-extrabold text-lg text-slate-800 leading-tight">Learning &amp;<br>Development</span>
                </a>

                <!-- Nav -->
                <nav class="space-y-1">
                    @foreach ($navItems as $item)
                        @php
                            $active = $item['route'] && request()->routeIs($item['route']);
                            $pendingCount = $item['route'] ? ($pendingActions[$item['route']] ?? 0) : 0;
                        @endphp
                        <a href="{{ $item['route'] ? route($item['route']) : '#' }}"
                           class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition
                                  {{ $active ? 'bg-blue-50 text-blue-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-700' }}">
                            <span class="relative {{ $active ? 'text-blue-600' : 'text-slate-400' }}">
                                @if ($pendingCount > 0)
                                    <span class="absolute -top-1.5 -right-1.5 min-w-[16px] h-4 px-1 rounded-full bg-red-500 text-white text-[10px] leading-4 font-bold text-center">
                                        {{ $pendingCount > 99 ? '99+' : $pendingCount }}
                                    </span>
                                @endif
                                @switch($item['icon'])
                                    @case('home')
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 11.5 12 4l9 7.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M5 10v9a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1v-9"/></svg>
                                        @break
                                    @case('star')
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="m12 3 2.6 5.9 6.4.6-4.8 4.3 1.4 6.3L12 17l-5.6 3.1 1.4-6.3-4.8-4.3 6.4-.6L12 3Z"/></svg>
                                        @break
                                    @case('users')
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M22 21v-2a4 4 0 0 0-3-3.87"/><path stroke-linecap="round" stroke-linejoin="round" d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                        @break
                                    @case('briefcase')
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><rect x="2" y="7" width="20" height="14" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                                        @break
                                    @case('grid')
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                                        @break
                                    @default
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                                @endswitch
                            </span>
                            {{ $item['label'] }}
                        </a>
                    @endforeach

                    
                </nav>

                <div class="mt-10 pt-6 border-t border-slate-100">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-red-50 hover:text-red-600 transition w-full">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path stroke-linecap="round" stroke-linejoin="round" d="M16 17l5-5-5-5"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12H9"/></svg>
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex-1 min-w-0">

            <!-- Topbar -->
            <header class="bg-white/70 backdrop-blur border-b border-slate-100 px-5 sm:px-8 py-5 flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-800">{{ $title }}</h1>
                </div>

                <div class="flex items-center gap-3 sm:gap-4">
                    @if (session('success'))
                        <span class="hidden sm:inline-flex items-center gap-1.5 text-xs font-medium text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                            Tersimpan
                        </span>
                    @endif

                    <div class="flex items-center gap-3 pl-3 sm:pl-4 border-l border-slate-200">
                        <div class="text-right hidden sm:block">
                            <p class="text-sm font-bold text-slate-800 leading-tight">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-slate-400 leading-tight">{{ $roleLabel }}</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-blue-600 text-white grid place-items-center font-bold text-sm shrink-0">
                            {{ $initials ?: '?' }}
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="p-5 sm:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    @include('partials.fcm-scripts')
</body>
</html>