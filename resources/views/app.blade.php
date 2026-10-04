<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-portal="{{ $page['props']['portal'] ?? 'guest' }}">
    <head>
        <meta charset="utf-8">
        @isset($contentSecurityPolicy)
            {{-- Must come before any script or style. See ContentSecurityPolicy middleware. --}}
            <meta http-equiv="Content-Security-Policy" content="{{ $contentSecurityPolicy }}">
        @endisset
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Mission Control') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
