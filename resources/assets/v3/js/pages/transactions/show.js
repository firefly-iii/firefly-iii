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

import '../../boot/bootstrap.js';
import sidebar from '../../pages/shared/sidebar.js';
import dates from '../shared/dates.js';
import i18next from "i18next";
import Get from '../../api/model/transaction/get.js';

let show = function () {
    return {
        i18next: null,
        group: {
            id: 0,
            group_title: '',
            transactions: [],

        },
        loading: true,
        id: 0,
        init() {
            this.i18next = i18next;
            const page = window.location.href.split('/');
            this.group.id = parseInt(page[page.length - 1]);
            this.downloadTransactionGroup();
            // console.log('Generic JS for page with few features.');
        },
        downloadTransactionGroup() {
            (new Get()).show(this.group.id).then((response) => {
                const info = response.data.data;
                this.group.transactions = info.attributes.transactions;
                this.loading = false;
            });
        }
    }

};


const comps = {
    show,
    sidebar,
    dates
};

function loadPage(comps) {
    // console.log('loadPage');
    Object.keys(comps).forEach(comp => {
        let data = comps[comp]();
        Alpine.data(comp, () => data);
        // console.log(comp);
    });
    Alpine.start();
}

// wait for load until bootstrapped event is received.
document.addEventListener('firefly-iii-bootstrapped', () => {
    // console.log('Loaded through event listener.');
    loadPage(comps);
});
// or is bootstrapped before event is triggered.
if (window.bootstrapped) {
    // console.log('Loaded through window variable.');
    loadPage(comps);
}
