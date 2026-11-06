<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class ManifestController extends Controller
{
    private const DEFAULT_COLOR = '#3949ab';

    private const BACKGROUND = '#f7f8fa';

    /**
     * Nome, cores e ícones mudam por projeto, então o arquivo não pode ser fixo.
     */
    public function __invoke(): JsonResponse
    {
        $name = config('app.name');
        $icon = config('template.branding.icon');
        $color = config('template.branding.primary_color');

        return response()->json([
            'name' => $name,
            'short_name' => $name,
            'start_url' => route('dashboard', absolute: false),
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => self::BACKGROUND,
            'theme_color' => is_string($color) && preg_match('/^#[0-9a-fA-F]{6}$/', $color) === 1 ? $color : self::DEFAULT_COLOR,
            'icons' => [
                ['src' => "{$icon}-192.png", 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => "{$icon}-512.png", 'sizes' => '512x512', 'type' => 'image/png'],
            ],
        ], headers: ['Content-Type' => 'application/manifest+json']);
    }
}
