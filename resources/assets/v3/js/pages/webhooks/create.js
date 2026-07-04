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

let create = function () {
    return {
        error_message: '',
        success_message: '',
        title: '',
        triggers: ["STORE_TRANSACTION"],
        responses: "RELEVANT",
        deliveries: "JSON",
        active: true,
        url: '',
        errors: {
            title: [],
            triggers: [],
            responses: [],
            deliveries: [],
            url: [],
            active: []
        },
        hasError: function (field) {
            return this.errors[field].length > 0;
        },
        clearTitle: function () {
            this.title = '';
        },
        handleInput() {
            // this.$emit('input', this.administration.title);
        },
        init() {
            console.log('Create webhook page.');
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
            document.getElementById('submitButton').disabled = true;

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
            axios.post('./api/v1/webhooks', data).then((response) => {
                //this.success_message = $.text(response.data.message);
                // console.log('Will now go to redirectUser()');
                let webhookId = response.data.data.id;
                window.location.href = window.previousUrl + '?webhook_id=' + webhookId + '&message=created';
            }).catch((error) => {
                //console.log(error.response.data);
                this.error_message = error.response.data.message;
                this.errors.title = error.response.data.errors.title;
                this.errors.triggers = error.response.data.errors.triggers;
                this.errors.responses = error.response.data.errors.responses;
                this.errors.deliveries = error.response.data.errors.deliveries;
                this.errors.url = error.response.data.errors.url;

                // enable button again
                document.getElementById('submitButton').disabled = false;

            });
            if (e) {
                e.preventDefault();
            }
        }
    }
};


const comps = {
    create,
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
