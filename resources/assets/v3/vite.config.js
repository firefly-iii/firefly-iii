/*
 * vite.config.js
 * Copyright (c) 2026 james@firefly-iii.org
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
import fs from "fs";

export default defineConfig(({command, mode, isSsrBuild, isPreview}) => {
    let https = null;
    if (command === 'serve') {
        https = {
            key: fs.readFileSync(`/vagrant/tls-certificates/wildcard.sd.internal.key`),
            cert: fs.readFileSync(`/vagrant/tls-certificates/wildcard.sd.internal.crt`),
        };
    }

    return {
        base: './',
        plugins: [
            laravel({
                input: [
                    // CSS for entire app
                    'sass/app.scss',

                    // auth pages (login etc)
                    'js/pages/auth/auth.js',

                    // dashboard
                    'js/pages/dashboard/boxes.js',
                    'js/pages/dashboard/dashboard.js',

                    // accounts
                    'js/pages/accounts/index.js',
                    'js/pages/accounts/show.js',
                    'js/pages/accounts/reconcile/index.js',

                    // categories
                    'js/pages/categories/index.js',

                    // budgets
                    'js/pages/budgets/index.js',

                    // exchange rates
                    'js/pages/exchange-rates/index.js',

                    // export
                    'js/pages/export/index.js',

                    // object groups
                    'js/pages/object-groups/index.js',

                    // rules
                    'js/pages/rules/index.js',

                    // recurring transactions
                    'js/pages/recurring/index.js',

                    // subscriptions
                    'js/pages/subscriptions/index.js',

                    // transaction currencies
                    'js/pages/currencies/index.js',

                    // transactions
                    'js/pages/transactions/index.js',
                    'js/pages/transactions/create.js',
                    'js/pages/transactions/edit.js',
                    'js/pages/transactions/show.js',

                    // tags
                    'js/pages/tags/index.js',

                    // webhooks
                    'js/pages/webhooks/index.js',

                    // piggy banks
                    'js/pages/piggy-banks/index.js',
                ],
                buildDirectory: '../../../../public/build',
                // publicDirectory: '../../../public',
                refresh: true,
                fonts: [],
            }),
            manifestSRI(),
        ],
        server: {
            watch: {
                ignored: ['**/storage/framework/views/**'],
                usePolling: true,
            },
            cors: true,
            // make sure this IP matches the IP of the dev machine.
            origin: 'https://192.168.96.169:5173',
            port: 5173,
            host: true,
            // hmr: {
            //     protocol: 'wss',
            // },
            https: https,
        },
    }
});
