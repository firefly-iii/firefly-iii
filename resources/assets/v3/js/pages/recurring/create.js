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
import Autocomplete from "bootstrap5-autocomplete";
import 'use-bootstrap-tag/dist/use-bootstrap-tag.css'
import UseBootstrapTag from 'use-bootstrap-tag'


let create = function () {
    return {

        init() {
            this.createTagField();
            this.createAutocomplete();
        },
        createTagField(){
            UseBootstrapTag(document.getElementById('ffInput_tags'));
        },
        createAutocomplete() {
            this.createCategoryAutocomplete();
        },
        createCategoryAutocomplete() {
            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            Autocomplete.init('#ffInput_category', {

                server: './api/v1/autocomplete/categories?_token=' + token,
                labelField: 'name',
                valueField: 'name',
                liveServer: true,
                fetchOptions: {
                                method: 'GET',
                                credentials: 'include',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': token
                                }
                            }
                // onServerResponse: function(data) {
                //     console.log(data.body);
                //     return Promise.resolve(data);
                // },
                //
                // fetchFunction: function (query) {
                //     // Custom data fetching logic
                //     return fetch('api/v1/autocomplete/categories?_token=' + token + '&query=' + encodeURIComponent(query),
                //         {
                //             method: 'GET',
                //             credentials: 'include',
                //             headers: {
                //                 'Content-Type': 'application/json',
                //                 'Accept': 'application/json',
                //                 'X-CSRF-TOKEN': token
                //             },
                //         }
                //     )
                //         .then((response) => response.json())
                //         .then((data) => {
                //             var result = [];
                //             for(var i in data) {
                //                 if(data.hasOwnProperty(i)) {
                //                     result.push(data[i].name);
                //                 }
                //             }
                //             // Process data if needed
                //             return result;
                //         });
                // },
            });
        },
    }
};


const comps = {
    create,
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
