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

import Summary from "../../api/summary/index.js";
import {format} from "date-fns";
import {getVariable} from "../../store/get-variable.js";


export default () => ({
    balanceBox: {amounts: [], subtitles: []},
    billBox: {paid: [], unpaid: []},
    leftBox: {left: [], perDay: []},
    netBox: {net: []},
    loadBoxes() {
        console.log('loadboxes');

        // get stuff
        let getter = new Summary();
        let start = new Date(window.store.get('start'));
        let end = new Date(window.store.get('end'));

        getter.get(format(start, 'yyyy-MM-dd'), format(end, 'yyyy-MM-dd'), null).then((response) => {
            // reset boxes:
            this.balanceBox = {amounts: [], subtitles: []};
            this.billBox = {paid: [], unpaid: []};
            this.leftBox = {left: [], perDay: []};
            this.netBox = {net: []};

            // process new content:
            for (const i in response.data) {
                if (response.data.hasOwnProperty(i)) {
                    const current = response.data[i];
                    if (i.startsWith('balance-in-')) {
                        this.balanceBox.amounts.push(current.value_parsed);
                        this.balanceBox.subtitles.push(current.sub_title);
                        continue;
                    }
                    if (i.startsWith('bills-unpaid-in-')) {
                        this.billBox.unpaid.push(current.value_parsed);
                        continue;
                    }
                    if (i.startsWith('bills-paid-in-')) {
                        this.billBox.paid.push(current.value_parsed);
                        continue;
                    }
                    if (i.startsWith('spent-in-')) {
                        this.leftBox.left.push(current.value_parsed);
                        continue;
                    }
                    if (i.startsWith('left-to-spend-in-')) { // per day
                        this.leftBox.perDay.push(current.sub_title);
                        continue;
                    }
                    if (i.startsWith('net-worth-in-')) {
                        this.netBox.net.push(current.value_parsed);

                    }
                    //console.log('Next up: ', current);
                }
            }
        });

    },

    // Getter
    init() {
        console.log('Now in boxes');
        Promise.all([
            getVariable('viewRange'),
        ]).then((values) => {
            this.loadBoxes();
        });
        window.store.observe('start', (newValue, oldValue) => {
            // this.loadBoxes();
        });
        window.store.observe('end', (newValue, oldValue) => {
            this.loadBoxes();
        });
    },
});
