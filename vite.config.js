import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';


import fs from 'fs';


const host = 'firefly.sd.local';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/assets/v2/sass/app.scss',
                'resources/assets/v2/dashboard.js',
            ],
            refresh: true,

        }),
    ],


    server: {
        usePolling: true,
        allowedHosts: '*.sd.local',
        host: '0.0.0.0',
        hmr: {host},
        cors: true,
        https: {
            key: fs.readFileSync(`/vagrant/tls-certificates/wildcard.sd.local.key`),
            cert: fs.readFileSync(`/vagrant/tls-certificates/wildcard.sd.local.crt`),
        },
    },
});
