<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-app-name="{{ config('app.name') }}" data-network-error="{{ __('Could not reach the server. Check your connection and try again.') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" media="(prefers-color-scheme: light)" content="#f7f8fa">
        <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#0f1115">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">

        <title inertia>{{ config('app.name') }}</title>

        {{-- Antes da primeira pintura: sem isto, o tema escuro abriria com um clarão. --}}
        <style>
            html { background: #f7f8fa; }
            html[data-color-scheme="dark"] { background: #0f1115; color-scheme: dark; }
        </style>
        <script>
            (function () {
                var theme = @json($page['props']['auth']['theme'] ?? null);

                try {
                    if (theme) {
                        localStorage.setItem('mui-mode', theme);
                    } else {
                        theme = localStorage.getItem('mui-mode');
                    }
                } catch (error) {}

                if (theme !== 'light' && theme !== 'dark') {
                    theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                }

                document.documentElement.setAttribute('data-color-scheme', theme);
            })();
        </script>

        <link rel="icon" href="{{ config('template.branding.favicon') }}">
        <link rel="manifest" href="{{ route('manifest') }}">
        <link rel="apple-touch-icon" href="{{ config('template.branding.icon') }}-192.png">

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
