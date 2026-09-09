<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Learning and Development') }} — Log in</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        .font-display { font-family: 'Playfair Display', serif; }

        /* Pola diagonal halus di panel kiri, terinspirasi dari motif perisai */
        .brand-pattern {
            background-image:
                repeating-linear-gradient(135deg, rgba(255,255,255,0.055) 0px, rgba(255,255,255,0.055) 1px, transparent 1px, transparent 26px),
                radial-gradient(circle at 15% 12%, rgba(255,255,255,0.10), transparent 45%),
                radial-gradient(circle at 88% 82%, rgba(255,255,255,0.08), transparent 40%);
        }

        .brand-glow {
            background: radial-gradient(circle, rgba(255,255,255,0.16) 0%, rgba(255,255,255,0) 70%);
        }
    </style>
</head>
<body class="antialiased bg-[#FAF7F2] text-[#171717]">

    <div class="min-h-screen w-full flex">

        {{-- ================= PANEL KIRI: BRAND (disembunyikan di mobile) ================= --}}
        <div class="hidden lg:flex lg:w-[46%] relative overflow-hidden
                    bg-gradient-to-br from-[#7A1E22] via-[#8B2331] to-[#5C1519]">

            <div class="absolute inset-0 brand-pattern"></div>
            <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full brand-glow"></div>
            <div class="absolute -bottom-32 -right-16 w-[28rem] h-[28rem] rounded-full brand-glow"></div>

            {{-- Garis aksen emas tipis di tepi panel --}}
            <div class="absolute inset-y-0 right-0 w-px bg-gradient-to-b from-transparent via-[#D4AF6A]/50 to-transparent"></div>

            <div class="relative z-10 flex flex-col justify-between w-full px-12 py-14 text-white">

                <div class="flex items-center gap-3">
                    <div class="bg-white rounded-full p-2 shadow-lg shadow-black/20">
                        <img src="{{ asset('images/logo-dagsap.png') }}" alt="Logo Dagsap"
                             class="w-9 h-9 object-contain">
                    </div>
                    <div class="leading-tight">
                        <p class="text-[11px] tracking-[0.2em] text-[#F0D9B5] font-semibold">PT DAGSAP ENDURA EATORE</p>
                        <p class="text-[11px] text-white/60">Rerum Concordia Endura</p>
                    </div>
                </div>

                <div class="my-auto py-16">
                    <p class="uppercase tracking-[0.25em] text-xs text-[#F0D9B5] font-semibold mb-5">
                        Learning &amp; Development
                    </p>
                    <h2 class="font-display text-4xl xl:text-[2.6rem] leading-[1.2] font-semibold mb-6 max-w-md">
                        Menumbuhkan Kinerja,<br>Membangun Karier.
                    </h2>
                    <p class="text-white/75 text-sm leading-relaxed max-w-sm">
                        Satu platform terpadu untuk penilaian kinerja, tanggapan korelasi
                        kerja, dan pengembangan diri seluruh keluarga besar
                        PT Dagsap Endura Eatore.
                    </p>

                    <div class="flex items-center gap-8 mt-12 text-white/85">
                        <div>
                            <p class="font-display text-2xl font-semibold">360°</p>
                            <p class="text-[11px] text-white/55 mt-1 tracking-wide">Penilaian Korelasi</p>
                        </div>
                        <div class="w-px h-8 bg-white/20"></div>
                        <div>
                            <p class="font-display text-2xl font-semibold">Real-time</p>
                            <p class="text-[11px] text-white/55 mt-1 tracking-wide">Notifikasi &amp; Progres</p>
                        </div>
                    </div>
                </div>

                <p class="text-[11px] text-white/45">
                    &copy; {{ date('Y') }} PT Dagsap Endura Eatore &mdash; Divisi HRD &amp; GA
                </p>
            </div>
        </div>

        {{-- ================= PANEL KANAN: FORM LOGIN ================= --}}
        <div class="flex-1 flex items-center justify-center px-6 py-12 sm:px-10 relative">

            {{-- Aksen lembut di background mobile --}}
            <div class="pointer-events-none absolute inset-0 lg:hidden overflow-hidden">
                <div class="absolute -top-20 -right-24 w-72 h-72 bg-[#8B2331]/[0.06] rounded-full blur-3xl"></div>
                <div class="absolute -bottom-24 -left-16 w-72 h-72 bg-[#D4AF6A]/[0.10] rounded-full blur-3xl"></div>
            </div>

            <div class="relative w-full max-w-sm">

                {{-- Header logo untuk mobile / tablet --}}
                <div class="flex lg:hidden flex-col items-center text-center mb-10">
                    <div class="bg-white rounded-full p-2.5 shadow-md shadow-black/10 ring-1 ring-black/5 mb-4">
                        <img src="{{ asset('images/logo-dagsap.png') }}" alt="Logo Dagsap" class="w-12 h-12 object-contain">
                    </div>
                    <p class="text-[11px] tracking-[0.2em] text-[#8B2331] font-bold">PT DAGSAP ENDURA EATORE</p>
                </div>

                <div class="bg-white rounded-2xl border border-[#EDE7DD] shadow-[0_20px_50px_-15px_rgba(139,35,49,0.18)]
                            px-6 py-8 sm:px-9 sm:py-10">

                    <div class="mb-8">
                        <h1 class="text-2xl font-bold tracking-tight text-[#171717]">Selamat Datang</h1>
                        <p class="text-sm text-[#737373] mt-1.5">
                            Masuk ke workspace Learning &amp; Development Anda.
                        </p>
                    </div>

                    <x-auth-session-status class="mb-6" :status="session('status')" />

                    <form method="POST" action="{{ route('login') }}" class="space-y-5">
                        @csrf

                        {{-- Username --}}
                        <div>
                            <label for="username" class="block text-xs font-semibold text-[#525252] mb-1.5">
                                Username
                            </label>
                            <div class="flex items-center gap-3 border border-[#E5E5E5] rounded-xl px-4 py-3
                                        bg-[#FAFAFA] focus-within:bg-white focus-within:border-[#8B2331]
                                        focus-within:ring-2 focus-within:ring-[#8B2331]/15 transition-all">
                                <svg class="w-4.5 h-4.5 w-[18px] h-[18px] text-[#A3A3A3] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <input
                                    id="username"
                                    type="text"
                                    name="username"
                                    value="{{ old('username') }}"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    placeholder="nama_lengkap"
                                    class="w-full bg-transparent border-0 text-[#171717] placeholder-[#A3A3A3] text-sm focus:outline-none focus:ring-0 focus:border-0 p-0"
                                />
                            </div>
                            <x-input-error :messages="$errors->get('username')" class="mt-2" />
                        </div>

                        {{-- Password --}}
                        <div>
                            <label for="password" class="block text-xs font-semibold text-[#525252] mb-1.5">
                                Password
                            </label>
                            <div class="flex items-center gap-3 border border-[#E5E5E5] rounded-xl px-4 py-3
                                        bg-[#FAFAFA] focus-within:bg-white focus-within:border-[#8B2331]
                                        focus-within:ring-2 focus-within:ring-[#8B2331]/15 transition-all" x-data="{ show: false }">
                                <svg class="w-[18px] h-[18px] text-[#A3A3A3] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                                <input
                                    :type="show ? 'text' : 'password'"
                                    id="password"
                                    name="password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="nik kamu"
                                    class="w-full bg-transparent border-0 text-[#171717] placeholder-[#A3A3A3] text-sm focus:outline-none focus:ring-0 focus:border-0 p-0"
                                />
                                <button type="button" @click="show = !show" aria-label="Toggle password visibility"
                                        class="shrink-0 text-[#A3A3A3] hover:text-[#8B2331] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#8B2331] rounded">
                                    <svg x-show="!show" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                    </svg>
                                    <svg x-show="show" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </button>
                            </div>
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        {{-- Ingat saya --}}
                        <div class="flex items-center justify-between pt-1">
                            <label for="remember" class="flex items-center gap-2 cursor-pointer select-none">
                                <input id="remember" type="checkbox" name="remember"
                                       class="w-4 h-4 rounded border-[#D4D4D4] text-[#8B2331] focus:ring-[#8B2331]/30">
                                <span class="text-xs text-[#525252]">Ingat saya</span>
                            </label>
                        </div>

                        {{-- Submit --}}
                        <button type="submit"
                                class="w-full py-3.5 rounded-xl bg-gradient-to-r from-[#8B2331] to-[#6E1B26]
                                       hover:from-[#7A1E2A] hover:to-[#5C1519] text-white text-sm font-semibold
                                       tracking-wide shadow-lg shadow-[#8B2331]/25 transition-all
                                       focus:outline-none focus-visible:ring-2 focus-visible:ring-[#D4AF6A] focus-visible:ring-offset-2">
                            Masuk
                        </button>
                    </form>
                </div>

                <p class="text-center text-xs text-[#A3A3A3] mt-8">
                    &copy; {{ date('Y') }} PT Dagsap Endura Eatore &mdash; Learning &amp; Development
                </p>
            </div>
        </div>
    </div>

</body>
</html>