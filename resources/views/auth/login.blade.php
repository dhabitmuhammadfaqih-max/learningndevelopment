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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
    </style>
</head>
<body class="antialiased bg-white text-[#171717]">

    <div class="min-h-screen w-full flex items-center justify-center px-6 py-16 relative overflow-hidden">

        {{-- Ambient glow behind the card --}}
        <div class="pointer-events-none absolute w-[28rem] h-[28rem] bg-[#171717]/[0.04] rounded-full blur-3xl"></div>

        <div class="relative w-full max-w-sm border border-[#E5E5E5] px-8 py-10 sm:px-10 sm:py-12
                    shadow-[0_8px_30px_rgba(0,0,0,0.06)]">

            {{-- Wordmark --}}
            <div class="mb-14">
                <div class="w-8 h-8 bg-[#171717] mb-6"></div>
                <h1 class="text-xl font-semibold tracking-tight">Learning and Development</h1>
                <p class="text-sm text-[#737373] mt-1">Log in to your workspace</p>
            </div>

            <x-auth-session-status class="mb-6" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}" class="space-y-8">
                @csrf

                {{-- Username --}}
                <div>
                    <label for="username" class="block text-xs font-medium text-[#737373] mb-2">
                        Username
                    </label>
                    <div class="flex items-center gap-3 border-b border-[#D4D4D4] pb-3
                                focus-within:border-[#171717] transition-colors">
                        <svg class="w-4 h-4 text-[#A3A3A3] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
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
                    <label for="password" class="block text-xs font-medium text-[#737373] mb-2">
                        Password
                    </label>
                    <div class="flex items-center gap-3 bg-[#FAFAFA] rounded-md px-4 py-3
                                focus-within:ring-1 focus-within:ring-[#171717] transition-shadow" x-data="{ show: false }">
                        <button type="button" @click="show = !show" aria-label="Toggle password visibility"
                                class="shrink-0 text-[#A3A3A3] hover:text-[#171717] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#171717] rounded">
                            <svg x-show="!show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                            <svg x-show="show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                        <input
                            :type="show ? 'text' : 'password'"
                            id="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="nik.kamu"
                            class="w-full bg-transparent border-0 text-[#171717] placeholder-[#A3A3A3] text-sm focus:outline-none focus:ring-0 focus:border-0 p-0"
                        />
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                {{-- Submit --}}
                <button type="submit"
                        class="w-full py-3 bg-[#171717] hover:bg-[#292929] text-white text-sm font-medium
                               tracking-wide transition-colors
                               focus:outline-none focus-visible:ring-2 focus-visible:ring-[#A16207] focus-visible:ring-offset-2">
                    Log in
                </button>
            </form>

            <p class="text-xs text-[#A3A3A3] mt-12">
                &copy; {{ date('Y') }} Learning and Development
            </p>
        </div>
    </div>

</body>
</html>