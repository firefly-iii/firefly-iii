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
import dates from './pages/shared/dates.js';
import boxes from './pages/dashboard/boxes.js';
import accounts from './pages/dashboard/accounts.js';
import budgets from './pages/dashboard/budgets.js';
import categories from './pages/dashboard/categories.js';
import sankey from './pages/dashboard/sankey.js';
import subscriptions from './pages/dashboard/subscriptions.js';
import piggies from './pages/dashboard/piggies.js';


import {
    Chart,
    LineController,
    LineElement,
    PieController,
    BarController,
    BarElement,
    TimeScale,
    ArcElement,
    LinearScale,
    Legend,
    Filler,
    Colors,
    CategoryScale,
    PointElement,
    Tooltip
} from "chart.js";
import 'chartjs-adapter-date-fns';

// register things
Chart.register({
    LineController,
    LineElement,
    ArcElement,
    BarController,
    TimeScale,
    PieController,
    BarElement,
    Filler,
    Colors,
    LinearScale,
    CategoryScale,
    PointElement,
    Tooltip,
    Legend
});

const comps = {
    dates,
    boxes,
    accounts,
    budgets,
    categories,
    sankey,
    subscriptions,
    piggies
};

function loadPage(comps) {
    Object.keys(comps).forEach(comp => {
        console.log(`Loading page component "${comp}"`);
        let data = comps[comp]();
        Alpine.data(comp, () => data);
    });
    Alpine.start();
}

// wait for load until bootstrapped event is received.
document.addEventListener('firefly-iii-bootstrapped', () => {
    //console.log('Loaded through event listener.');
    loadPage(comps);
});
// or is bootstrapped before event is triggered.
if (window.bootstrapped) {
    //console.log('Loaded through window variable.');
    loadPage(comps);
}
