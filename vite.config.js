/*
 * vite.config.js
 * Copyright (c) 2023 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import manifestSRI from 'vite-plugin-manifest-sri';

const host = '127.0.0.1';

function manualChunks(id) {
    if (id.includes('node_modules')) {
        return 'vendor';
    }
};

export default defineConfig({
    build: {
        rollupOptions: {
            output: {
                manualChunks,
            },
        }
    },
    plugins: [
        laravel({
            input: [
                'resources/assets/v2/sass/app.scss',
                'resources/assets/v2/pages/dashboard/dashboard.js',

                // transactions
                'resources/assets/v2/pages/transactions/create.js',
                'resources/assets/v2/pages/transactions/edit.js',
                'resources/assets/v2/pages/transactions/show.js',
                'resources/assets/v2/pages/transactions/index.js',
            ],
            refresh: true,
        }),
        manifestSRI(),

    ],


    server: {
        usePolling: true,
        allowedHosts: '*.sd.internal',
        host: '0.0.0.0',
        hmr: {host},
        cors: true
        // https: {
        //     key: fs.readFileSync(`/Users/sander/Sites/vm/tls-certificates/wildcard.sd.local.key`),
        //     cert: fs.readFileSync(`/Users/sander/Sites/vm/tls-certificates/wildcard.sd.local.crt`),
        // },
    },
});
