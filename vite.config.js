import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.jsx',
            refresh: true,
        }),
        react(),
    ],
    resolve: {
        // Pacotes instalados pelo Composer com repositório "path" ficam em
        // vendor/ como link. Mantendo o caminho do link, as páginas deles
        // usam o node_modules e as bibliotecas deste projeto.
        preserveSymlinks: true,
    },
});
