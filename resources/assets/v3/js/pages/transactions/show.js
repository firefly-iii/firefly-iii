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
import i18next from "i18next";
import Get from "../../api/model/transaction/get.js";
import { format } from "date-fns";
import Alpine from "@alpinejs/csp";
import "bootstrap";
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import markerIcon from "leaflet/dist/images/marker-icon.png";
import markerRetinaIcon from "leaflet/dist/images/marker-icon-2x.png";
import shadow from "leaflet/dist/images/marker-shadow.png";

window.enableDates = false;

let show = function () {
    return {
        i18next: null,
        group: {
            id: 0,
            group_title: "",
            transactions: [],
        },
        loading: true,
        id: 0,
        init() {
            this.i18next = i18next;
            const page = window.location.href.split("/");
            this.group.id = parseInt(page[page.length - 1]);
            this.downloadTransactionGroup();
            this.renderMaps();
        },
        renderMaps() {
            document.querySelectorAll(".map-box").forEach((container) => {
                const lat = parseFloat(container.dataset.latitude);
                const lng = parseFloat(container.dataset.longitude);
                const zoom = parseFloat(container.dataset.zoomLevel);

                const map = L.map(container).setView([lat, lng], zoom);

                L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
                    maxZoom: 19,
                    referrerPolicy: "origin-when-cross-origin",
                    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                }).addTo(map);

                L.Marker.prototype.setIcon(
                    L.icon({
                        iconUrl: markerIcon,
                        iconRetinaIcon: markerRetinaIcon,
                        shadowUrl: shadow,
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                    }),
                );

                L.marker([lat, lng]).addTo(map);
            });
        },
        downloadTransactionGroup() {
            let locale = window.store.get("locale");

            new Get().show(this.group.id).then((response) => {
                const info = response.data.data;
                this.group.group_title = info.attributes.group_title;
                this.group.transactions = [];
                for (let i = 0; i < info.attributes.transactions.length; i++) {
                    if (Object.hasOwn(info.attributes.transactions, i)) {
                        let current = info.attributes.transactions[i];
                        current.dateObject = new Date(current.date);
                        current.dateFormatted = format(
                            current.dateObject,
                            window.i18next.t("config.date_time_fns", { lng: locale }),
                            locale,
                        );
                        console.log("Date formatted is", current.dateFormatted);
                        this.group.transactions.push(current);
                    }
                }

                this.loading = false;
            });
        },
    };
};

const comps = {
    show,
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
