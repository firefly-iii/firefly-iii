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

// CSS
import '../../boot/bootstrap.js';
import sidebar from '../../pages/shared/sidebar.js';
import dates from '../shared/dates.js';
import i18next from "i18next";

let edit = function () {
    return {
        i18next: null,
        pageTitle: '',
        administration: {
            title: '',
            currency_id: 0,
        },
        errors: {
            title: [],
            currency_id: [],
        },
        error_message: '',
        success_message: '',

        init() {
            this.i18next = i18next;
            const page = window.location.href.split('/');
            const administrationId = parseInt(page[page.length - 1]);
            this.downloadAdministration(administrationId);
        },
        downloadAdministration: function (id) {
            axios.get("./api/v1/user-groups/" + id).then((response) => {
                let current = response.data.data;
                this.administration = {
                    id: current.id,
                    title: current.attributes.title,
                    currency_id: parseInt(current.attributes.primary_currency_id),
                    currency_code: current.attributes.primary_currency_code,
                    currency_name: current.attributes.primary_currency_name,
                };
                this.pageTitle = this.administration.title;
            });
        },
        submit: function (e) {
            // reset messages
            this.error_message = '';
            this.success_message = '';
            this.errors = {
                title: [],
                currency_id: [],
            };

            // disable button
            $('#submitButton').prop("disabled", true);

            // collect data
            let data = {
                title: this.administration.title,
                primary_currency_id: parseInt(this.administration.currency_id),
            };

            // post!
            axios.put('./api/v1/user-groups/' + this.administration.id, data).then((response) => {
                let administrationId = parseInt(response.data.data.id);
                window.location.href = './administrations?user_group_id=' + administrationId + '&message=updated';
            }).catch((error) => {

                this.error_message = error.response.data.message;
                this.errors.title = error.response.data.errors.title;
                this.errors.primary_currency_id = error.response.data.errors.primary_currency_id;

                // enable button again
                $('#submitButton').prop("disabled", false);

            });
            if (e) {
                e.preventDefault();
            }
        },
        hasError: function () {
            return this.errors.title.length > 0;
        },
        clearTitle: function () {
            this.administration.title = '';
        },
        handleInput() {
            // this.$emit('input', this.administration.title);
        },
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
