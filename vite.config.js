import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import manifestSRI from 'vite-plugin-manifest-sri';

const host = '127.0.0.1';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/assets/v2/sass/app.scss',
                'resources/assets/v2/dashboard.js',
            ],
            refresh: true,
        }),
        manifestSRI(),
    ],


    server: {
        usePolling: true,
        allowedHosts: '*.sd.local',
        host: '0.0.0.0',
        hmr: {host},
        cors: true
        // https: {
        //     key: fs.readFileSync(`/Users/sander/Sites/vm/tls-certificates/wildcard.sd.local.key`),
        //     cert: fs.readFileSync(`/Users/sander/Sites/vm/tls-certificates/wildcard.sd.local.crt`),
        // },
    },
});
