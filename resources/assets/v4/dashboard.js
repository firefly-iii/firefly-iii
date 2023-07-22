/*
 * dashboard.js
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

import './bootstrap.js';
import Alpine from "alpinejs";

// move to bootstrap later on?
window.Alpine = Alpine

import dashboard from './pages/dashboard.js';

const comps = {dashboard};

//import * as comps from '/dist/demo/index.js';
Object.keys(comps).forEach(comp => {
    //let data = new comps[comp]();
    console.log('Loaded component ' + comp);
    let data = comps[comp]();
    Alpine.data(comp, () => data);
});
Alpine.start();
