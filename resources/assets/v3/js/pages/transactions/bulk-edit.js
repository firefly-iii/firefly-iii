/*
 * bulk-edit.js
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
import Tags from "bootstrap5-tags";
import i18next from 'i18next';
import {addAutocomplete} from "./shared/add-autocomplete.js";

let edit = function () {
    return {
        detectCategoryChange(e) {
            if('' !== e.target.value) {
                // tags_action_do_nothing
                document.querySelector('input[name="ignore_category"]').removeAttribute('checked');
            }
        },
        detectBudgetChange(e) {
            if(0 !== parseInt(e.target.value)) {
                // tags_action_do_nothing
                document.querySelector('input[name="ignore_budget"]').removeAttribute('checked');
            }
        },
        detectTagChange(e) {
            let count = 0;
            let options = document.querySelector('select[name="tags"]').options;
            for(let i = 0; i < options.length; i ++){
                if (true === options[i].selected){
                    count++;
                }
            }
            if(count > 0 && true === document.getElementById('tags_action_do_nothing').checked ) {
                document.getElementById('tags_action_do_nothing').checked = false;
                document.getElementById('tags_action_do_replace').checked = true;
            }
        },
        init() {

            addAutocomplete({
                selector: 'input.ac-category',
                serverUrl: '/api/v1/autocomplete/categories',
                valueField: 'name',
                labelField: 'name',
            });


            Tags.init('select.ac-tags', {
                allowClear: true,
                server: '/api/v1/autocomplete/tags',
                liveServer: true,
                clearEnd: true,
                labelField: 'tag',
                valueField: 'tag',
                queryParam: 'query',
                allowNew: true,
                //serverDataKey: 'data',
                notFoundMessage: i18next.t('firefly.nothing_found'),
                noCache: true,
                fetchOptions: {
                    headers: {
                        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
                    }
                }
            });
        }
    }
};


const comps = {
    edit,
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
