/*
 * create.js
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
import {createEmptySplit} from "./shared/create-empty-split.js";
import {parseFromEntries} from "./shared/parse-from-entries.js";
import formatMoney from "../../util/format-money.js";
//import Autocomplete from "bootstrap5-autocomplete";
import Post from "../../api/v2/model/transaction/post.js";

let transactions = function () {
    return {
        count: 0,
        totalAmount: 0,
        entries: [],

        // error and success messages:
        showError: false,
        showSuccess: false,

        init() {
            const opts = {
                onSelectItem: console.log,
            };
            let src = [];
            for (let i = 0; i < 50; i++) {
                src.push({
                    title: "Option " + i,
                    id: "opt" + i,
                    data: {
                        key: i,
                    },
                });
            }

            // for each thing, make autocomplete?


            this.addSplit();
            console.log('Ik ben init hoera');

            // let element = document.getElementById('source_0');
            // new Autocomplete(element, {
            //     items: src,
            //     valueField: "id",
            //     labelField: "title",
            //     highlightTyped: true,
            //     onSelectItem: console.log,
            // });
        },
        submitTransaction() {
            let transactions = parseFromEntries(this.entries);
            let submission = {
                group_title: null,
                fire_webhooks: false,
                apply_rules: false,
                transactions: transactions
            };
            let poster = new Post();
            console.log(submission);
            poster.post(submission).then((response) => {
                console.log(response);
            }).catch((error) => {
                console.error(error);
            });
        },
        addSplit() {
            this.entries.push(createEmptySplit());
        },
        removeSplit(index) {
            this.entries.splice(index, 1);
            // fall back to index 0
            const triggerFirstTabEl = document.querySelector('#split-0-tab')
            triggerFirstTabEl.click();
            //bootstrap.Tab.getInstance(triggerFirstTabEl).show() // Select first tab
        },
        formattedTotalAmount() {
            return formatMoney(this.totalAmount, 'EUR');
        }
    }
}

let comps = {transactions, dates};

function loadPage() {
    Object.keys(comps).forEach(comp => {
        console.log(`Loading page component "${comp}"`);
        let data = comps[comp]();
        Alpine.data(comp, () => data);
    });
    Alpine.start();
}


// wait for load until bootstrapped event is received.
document.addEventListener('firefly-iii-bootstrapped', () => {
    console.log('Loaded through event listener.');
    loadPage();
});
// or is bootstrapped before event is triggered.
if (window.bootstrapped) {
    console.log('Loaded through window variable.');
    loadPage();
}
