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

import '../../boot/bootstrap.js';
import dates from '../../pages/shared/dates.js';
import boxes from './boxes.js';
import accounts from './accounts.js';
import budgets from './budgets.js';
import categories from './categories.js';
import sankey from './sankey.js';
import subscriptions from './subscriptions.js';
import piggies from './piggies.js';
import {
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    Colors,
    Filler,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PieController,
    PointElement,
    TimeScale,
    Tooltip
} from "chart.js";
import 'chartjs-adapter-date-fns';
import {showInternalsButton} from "../../support/page-settings/show-internals-button.js";
import {setVariable} from "../../store/set-variable.js";
import {getVariable} from "../../store/get-variable.js";

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

let index = function () {
    return {
        convertToPrimary: false,
        savePrimarySettings(event) {
            let target = event.currentTarget || event.target;
            setVariable('convert_to_primary',target.checked).then(() => {
                console.log('Set convert to primary to: ', target.checked);
                this.$dispatch('convert-to-primary', target.checked);
            });
        },
        init() {
            Promise.all([getVariable('convert_to_primary', false)]).then((values) => {
                this.convertToPrimary = values[0];
            });
        }
    }
};

const comps = {
    index,
    dates,
    boxes,
    accounts,
    budgets,
    categories,
    sankey,
    subscriptions,
    piggies
};

showInternalsButton();

//let i18n;

function loadPage(comps) {
    Object.keys(comps).forEach(comp => {
        let data = comps[comp]();
        Alpine.data(comp, () => data);
    });
    Alpine.start();
}

// wait for load until bootstrapped event is received.
document.addEventListener('firefly-iii-bootstrapped', () => {
    console.log('Loaded through event listener.');
    loadPage(comps);
});
// or is bootstrapped before event is triggered.
if (window.bootstrapped) {
    console.log('Loaded through window variable.');
    loadPage(comps);
}
