<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="autosave-user-id" content="{{ auth()->id() }}">

        <title>{{ config('app.name', 'Learning & Development') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('images/logo-dagsap.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/logo-dagsap.png') }}">

        <!-- Open Graph / Link Sharing Preview -->
        <meta property="og:title" content="{{ config('app.name', 'Learning & Development') }}">
        <meta property="og:site_name" content="{{ config('app.name', 'Learning & Development') }}">
        <meta property="og:description" content="{{ config('app.name', 'Learning & Development') }} - Platform Pembelajaran dan Pengembangan">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ config('app.name', 'Learning & Development') }}">
        <meta name="twitter:description" content="{{ config('app.name', 'Learning & Development') }} - Platform Pembelajaran dan Pengembangan">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
