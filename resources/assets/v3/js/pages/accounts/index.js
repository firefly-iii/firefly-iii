/*
 * show.js
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

import "../../boot/bootstrap.js";
import sidebar from "../shared/sidebar.js";
import dates from "../shared/dates.js";
import { addDrag } from "../shared/drag-and-droppable-rows.js";
import Alpine from "alpinejs";
import { getVariable } from "../../store/get-variable.js";
import Put from "../../api/model/account/put.js";

window.enableDates = false;

let index = function () {
    return {
        listPageSize: 50,
        objectType: 'invalid',
        init() {
            const page = window.location.href.split("/");
            this.objectType = page[page.length - 1].substring(0, 15);


            getVariable("listPageSize").then((listPageSize) => {
                console.log(listPageSize);
                this.listPageSize = listPageSize;
                addDrag();
                document.addEventListener("firefly-iii-drag-complete", (e) => {
                    for (let i = 0; i < e.detail.length; i++) {
                        if (Object.hasOwn(e.detail, i)) {
                            let item = e.detail[i];
                            if (item.order !== item.currentOrder) {
                                // PUT new order to system.
                                new Put().put({ order: item.order }, { id: item.id });
                                // save new order as current order in the row.
                                document
                                    .querySelector(`tr[data-id="${item.id}"]`)
                                    .setAttribute("data-current-order", item.order);
                            }
                        }
                    }
                    console.log(e.detail);
                });
            });
        },
    };
};

const comps = {
    index,
    sidebar,
    dates,
};

function loadPage(comps) {
    // console.log('loadPage');
    Object.keys(comps).forEach((comp) => {
        let data = comps[comp]();
        Alpine.data(comp, () => data);
        // console.log(comp);
    });
    Alpine.start();
}

// wait for load until bootstrapped event is received.
document.addEventListener("firefly-iii-bootstrapped", () => {
    // console.log('Loaded through event listener.');
    loadPage(comps);
});
// or is bootstrapped before event is triggered.
if (window.bootstrapped) {
    // console.log('Loaded through window variable.');
    loadPage(comps);
}
