/*
 * serviceworker.js
 * Copyright (c) 2021 james@firefly-iii.org
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

let staticCachePrefix = "firefly-III-"
let staticCacheName = staticCachePrefix + new Date().getTime();
let cachedFiles = [
    '/offline',
    '/v2/plugins/local-fonts/gf-source.css',
    '/v2/css/app.css',
];

// Create cache on install
self.addEventListener("install", event => {
    this.skipWaiting();
    event.waitUntil(
        caches.open(staticCacheName).then(cache => cache.addAll(cachedFiles))
    )
});

// Clear cache on activate
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames
                    .filter(cacheName => (cacheName.startsWith(staticCachePrefix)))
                    .filter(cacheName => (cacheName !== staticCacheName))
                    .map(cacheName => caches.delete(cacheName))
            );
        })
    );
});

// Serve from Cache or return the offline page
self.addEventListener("fetch", event => {
    event.respondWith(
        caches.match(event.request)
            .then(response => (response || fetch(event.request)))
            .catch(() => caches.match('offline'))
    )
});
