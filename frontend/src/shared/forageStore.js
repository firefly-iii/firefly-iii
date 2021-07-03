/*
 * forageStore.js
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

import localforage from 'localforage'
import memoryDriver from 'localforage-memoryStorageDriver'
import {setup} from 'axios-cache-adapter'

// `async` wrapper to configure `localforage` and instantiate `axios` with `axios-cache-adapter`
export async function configureAxios() {
    // Register the custom `memoryDriver` to `localforage`
    await localforage.defineDriver(memoryDriver)

    // Create `localforage` instance
    const forageStore = localforage.createInstance({
                                                       // List of drivers used
                                                       driver: [
                                                           localforage.INDEXEDDB,
                                                           localforage.LOCALSTORAGE,
                                                           memoryDriver._driver
                                                       ],
                                                       // Prefix all storage keys to prevent conflicts
                                                       name: 'my-cache'
                                                   })

    // Create `axios` instance with pre-configured `axios-cache-adapter` using a `localforage` store
    let token = document.head.querySelector('meta[name="csrf-token"]');
    return setup({
                     // `axios` options
                     baseURL: './',
                     headers: {'X-CSRF-TOKEN': token.content, 'X-James-Rocks': 'oh yes'},
                     cache: {
                         // `axios-cache-adapter` options
                         maxAge: 24 * 60 * 60 * 1000, // one day.
                         readHeaders: false,
                         exclude: {
                             query: false,
                         },
                         debug: true,
                         store: forageStore,
                     }
                 }
    );

}