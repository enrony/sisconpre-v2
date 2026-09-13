<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{--
            Herramienta interna de back-office detrás de login: no debe indexarse
            ni aparecer en buscadores.
        --}}
        <meta name="robots" content="noindex, nofollow">

        @php
            $metaTitle = config('app.name', 'Laravel');
            $metaDescription = 'Sistema de gestión de préstamos de GilenSoft: registro de préstamos, cronograma de cuotas, informes de pago y administración de cartera.';
        @endphp

        <meta name="description" content="{{ $metaDescription }}">
        <meta name="author" content="GilenSoft">

        {{-- Open Graph: usado por WhatsApp, Slack, Facebook, LinkedIn, etc. al compartir un enlace --}}
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="GilenSoft">
        <meta property="og:title" content="{{ $metaTitle }}">
        <meta property="og:description" content="{{ $metaDescription }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:locale" content="es_ES">

        {{-- Twitter/X Card --}}
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ $metaTitle }}">
        <meta name="twitter:description" content="{{ $metaDescription }}">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/favicon.svg">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
