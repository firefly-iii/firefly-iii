import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';


import fs from 'fs';


const host = 'firefly.sd.local';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/assets/v4/sass/app.scss',
                'resources/assets/v4/app.js',
                'resources/assets/v4/index.js'
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
