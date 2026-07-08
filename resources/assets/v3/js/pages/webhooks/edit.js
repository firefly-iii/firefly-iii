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
import {loadTriggers} from "./shared/load-triggers.js";
import {loadResponses} from "./shared/load-responses.js";
import {loadDeliveries} from "./shared/load-deliveries.js";

let edit = function () {
    return {
        init() {
            this.i18next = i18next;

            loadTriggers().then((result) => {
                this.options.triggers = result;
                this.form.triggers.loading = false;
            });
            loadResponses().then((result) => {
                this.options.responses = result;
                this.form.responses.loading = false;
            });
            loadDeliveries().then((result) => {
                this.options.deliveries = result;
                this.form.deliveries.loading = false;
            });

            this.getWebhook();
        },
        error_message: '',
        success_message: '',
        title: '',
        i18next: null,
        triggers: ["STORE_TRANSACTION"],
        responses: "RELEVANT",
        deliveries: "JSON",
        id: 0,
        active: false,
        url: '',
        options: {
            triggers: [],
            responses: [],
            deliveries: [],
        },
        form: {
            triggers: {
                loading: true
            },
            responses: {
                loading: true
            },
            deliveries: {
                loading: true
            },
        },
        errors: {
            title: [],
            triggers: [],
            responses: [],
            deliveries: [],
            url: [],
            active: []
        },
        getWebhook: function () {
            const page = window.location.href.split('/');
            const webhookId = parseInt(page[page.length - 1]);
            this.downloadWebhook(webhookId);
        },
        downloadWebhook: function (id) {
            axios.get('./api/v1/webhooks/' + id).then(response => {
                // console.log(response.data.data.attributes);
                this.title = response.data.data.attributes.title;
                this.id = parseInt(response.data.data.id);
                this.triggers = response.data.data.attributes.triggers;
                this.responses = response.data.data.attributes.responses[0];
                this.deliveries = response.data.data.attributes.deliveries[0];
                this.active = response.data.data.attributes.active;
                this.url = response.data.data.attributes.url;
            }).catch(error => {
                this.error_message = error.response.data.message;
            });
        },
        submit: function (e) {
            // reset messages
            this.error_message = '';
            this.success_message = '';
            this.errors = {
                title: [],
                triggers: [],
                responses: [],
                deliveries: [],
                url: [],
                active: [],
            };

            // disable button
            $('#submitButton').prop("disabled", true);

            // collect data
            let data = {
                title: this.title,
                triggers: this.triggers,
                responses: [this.responses],
                deliveries: [this.deliveries],
                url: this.url,
                active: this.active,
            };

            // post!
            axios.put('./api/v1/webhooks/' + this.id, data).then((response) => {
                let webhookId = parseInt(response.data.data.id);
                window.location.href = window.previousUrl + '?webhook_id=' + webhookId + '&message=updated';
            }).catch((error) => {

                this.error_message = error.response.data.message;
                this.errors.title = error.response.data.errors.title;
                this.errors.triggers = error.response.data.errors.trigger;
                this.errors.responses = error.response.data.errors.response;
                this.errors.deliveries = error.response.data.errors.deliveries;
                this.errors.url = error.response.data.errors.url;

                // enable button again
                $('#submitButton').prop("disabled", false);

            });
            if (e) {
                e.preventDefault();
            }
        }




    }
};


const comps = {
    edit,
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
