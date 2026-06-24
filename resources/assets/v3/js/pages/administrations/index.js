/*
 * dashboard.js
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

// CSS
import '../../boot/bootstrap.js';
import sidebar from '../../pages/shared/sidebar.js';
import dates from '../shared/dates.js';

let index = function () {
    return {
        administrations: [],
        init() {
            this.getAdministrations();
        },
        getAdministrations: function () {
            this.administrations = [];
            this.downloadAdministrations(1);
        },

        downloadAdministrations: function (page) {
            axios.get("./api/v1/user-groups?page=" + page).then((response) => {
                for (let i in response.data.data) {
                    if (response.data.data.hasOwnProperty(i)) {
                        let current = response.data.data[i];
                        let administration = {
                            id: current.id,
                            title: current.attributes.title,
                            currency_code: current.attributes.primary_currency_code,
                            currency_name: current.attributes.primary_currency_name,
                        };
                        this.administrations.push(administration);
                    }
                }

                if (response.data.meta.pagination.current_page < response.data.meta.pagination.total_pages) {
                    this.downloadAdministrations(response.data.meta.pagination.current_page + 1);
                }
            });
        },
    }
};


const comps = {
    index,
    sidebar,
    dates
};

function loadPage(comps) {
    console.log('loadPage');
    Object.keys(comps).forEach(comp => {
        let data = comps[comp]();
        Alpine.data(comp, () => data);
        console.log(comp);
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
