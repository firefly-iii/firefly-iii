/*
 * template.js
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

import '../../boot/bootstrap.js';
import dates from "../shared/dates.js";
import Post from "../../api/v1/model/user-group/post.js";
import i18next from "i18next";
import Get from "../../api/v1/model/user-group/get.js";
import Put from "../../api/v1/model/user-group/put.js";


let administrations = function () {
    return {
        title: '',
        id: 0,
        errors: {
            title: []
        },

        // notifications
        notifications: {
            error: {
                show: false, text: '', url: '',
            }, success: {
                show: false, text: '', url: '',
            }, wait: {
                show: false, text: '',

            }
        },
        // state of the form is stored in formState:
        formStates: {
            isSubmitting: false,
            returnHereButton: false,
            saveAsNewButton: false, // edit form only
            resetButton: false,
        },

        // form behaviour
        formBehaviour: {
            formType: 'update', // or 'update'
        },
        changedTitle() {

        },

        pageProperties: {},
        submitForm() {
            (new Put()).put({title: this.title}, {id: this.id}).then(response => {
                if (this.formStates.returnHereButton) {
                    this.notifications.success.show = true;
                    this.notifications.success.text = i18next.t('firefly.updated_administration', {title: response.data.data.attributes.title});
                    // TODO needs a better redirect.
                    this.notifications.success.url = './administrations';
                }
                if (this.formStates.resetButton) {
                    this.title = '';
                }
                if (!this.formStates.returnHereButton) {
                    // TODO needs a better redirect.
                    window.location.href = './administrations?user_group_id=' + parseInt(response.data.data.id) + '&message=updated';
                }
            }).catch(error => {
                this.errors.title = error.response.data.errors.title;
            });

        },
        cancelForm() {
            window.location.href = './administrations';
        },
        init() {
            const page = window.location.href.split('/');
            const groupId = parseInt(page[page.length - 1]);
            (new Get()).show(groupId, {}).then(response => {
                this.title = response.data.data.attributes.title;
                this.id = parseInt(response.data.data.id);
            });
        }
    }
}


let comps = {administrations, dates};

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
