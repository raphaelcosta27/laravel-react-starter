<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('Setup required') }}</title>
    </head>
    <body>
        <main>
            <h1>{{ __('Setup required') }}</h1>
            <p>{{ __('The local configuration file was not found or has no application key.') }}</p>
            <p>{{ __('Copy .env.example to .env and run composer setup.') }}</p>
        </main>
    </body>
</html>
