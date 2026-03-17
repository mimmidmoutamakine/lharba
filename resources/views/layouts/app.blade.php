@php
    $isHubLayout = request()->routeIs(
        'dashboard',
        'setup.*',
        'hub.*',
        'training.*',
        'challenge.*',
        'progress.*'
    );
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="{{ $isHubLayout ? 'app-shell' : 'min-h-screen bg-slate-100' }}">
            @include('layouts.navigation', ['isHubLayout' => $isHubLayout])

            @isset($header)
                @if($isHubLayout)
                    <header class="app-page-header">
                        <div class="app-content">
                            <div class="app-header-card">
                                {{ $header }}
                            </div>
                        </div>
                    </header>
                @else
                    <header class="bg-white shadow">
                        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endif
            @endisset

            <main class="{{ $isHubLayout ? 'relative z-10' : '' }}">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
