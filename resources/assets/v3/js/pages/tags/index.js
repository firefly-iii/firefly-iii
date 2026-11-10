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

import "../../boot/bootstrap.js";
import sidebar from "../../pages/shared/sidebar.js";
import dates from "../shared/dates.js";
import Alpine from "@alpinejs/csp";
import i18next from "i18next";

window.enableDates = false;

function selectOrUnselect(e) {
    e.preventDefault();
    let button = e.target;
    let holder = document.querySelector('div[data-period="' + button.dataset.period + '"]');
    console.log("holder", holder.dataset.period);
    let first = holder.querySelector('input[type="checkbox"]');
    if (first) {
        let check = true;
        if (true === first.checked) {
            // uncheck all:
            check = false;
        }
        holder.querySelectorAll("input[type=checkbox]").forEach((el) => (el.checked = check));
        // update text on button.
        if (check) {
            holder.querySelectorAll(".checkbox-icon").forEach((elx) => {
                elx.classList.remove("bi-check-square");
                elx.classList.add("bi-check-square-fill");
            });
            holder.querySelectorAll(".select-all-text").forEach((elx) => {
                elx.textContent = i18next.t("firefly.multi_select_select_none");
            });
        }
        if (!check) {
            holder.querySelectorAll(".checkbox-icon").forEach((elx) => {
                elx.classList.add("bi-check-square");
                elx.classList.remove("bi-check-square-fill");
            });

            holder.querySelectorAll(".select-all-text").forEach((elx) => {
                elx.textContent = i18next.t("firefly.multi_select_select_all");
            });
        }
    }
}

let index = function () {
    return {
        addSelectAllTrigger() {
            // select-all
            let list = document.querySelectorAll(".select-all");
            console.log("List", list);
            if (list.length > 0) {
                list.forEach((e) => {
                    e.addEventListener("click", selectOrUnselect);
                });
            }
        },
        init() {
            this.addSelectAllTrigger();
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
