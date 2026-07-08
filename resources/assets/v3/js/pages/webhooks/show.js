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

import '../../boot/bootstrap.js';
import sidebar from '../../pages/shared/sidebar.js';
import dates from '../shared/dates.js';
import format from "date-fns/format";
import i18next from "i18next";

let show = function () {
    return {
        i18next: null,
        loading: true,

        // webhook data
        id: 0,
        active: false,
        show_secret: false,

        title: '',
        url: '',
        secret: '',

        triggers: [],
        responses: [],
        deliveries: [],
        message_attempts: [],
        message_content: '',
        messages: [],
        edit_url: '#',
        delete_url: '#',
        success_message: '',
        disabledTrigger: false,
        init() {
            this.i18next = i18next;
            this.getWebhook();
        },
        getWebhook() {
            this.loading = true;
            const page = window.location.href.split('/');
            this.id = parseInt(page[page.length - 1]);
            this.downloadWebhook();
            this.downloadWebhookMessages();
        },
        toggleSecret: function () {
            this.show_secret = !this.show_secret;
        },
        submitTest: function (e) {
            if (e) {
                e.preventDefault();
            }
            let journalId = parseInt(prompt('Enter a transaction ID'));
            if (journalId !== null && journalId > 0 && journalId <= 16777216) {
                // console.log('OK 1');
                this.disabledTrigger = true;
                // disable button. Add informative message.
                //let button = $('#triggerButton');
                //button.prop('disabled', true).addClass('disabled');

                this.success_message = this.$t('firefly.webhook_was_triggered');
                // TODO actually trigger the webhook.
                axios.post('./api/v1/webhooks/' + this.id + '/trigger-transaction/' + journalId, {});
                //button.prop('disabled', false).removeClass('disabled');
                // console.log('OK 2');

                // set a time-outs.
                this.loading = true;
                setTimeout(() => {
                    this.getWebhook();
                    this.disabledTrigger = false;
                }, 2000);
                // console.log('OK 3');
            }


            return false;
        },

        downloadWebhook: function () {
            axios.get('./api/v1/webhooks/' + this.id).then(response => {
                // console.log(response.data.data.attributes);
                this.edit_url = './webhooks/edit/' + this.id;
                this.delete_url = './webhooks/delete/' + this.id;
                this.title = response.data.data.attributes.title;
                this.url = response.data.data.attributes.url;
                this.secret = response.data.data.attributes.secret;
                this.triggers = response.data.data.attributes.triggers;
                this.responses = response.data.data.attributes.responses;
                this.deliveries = response.data.data.attributes.deliveries;

                console.log(response.data.data);

                this.active = response.data.data.attributes.active;
                this.url = response.data.data.attributes.url;
            }).catch(error => {
                this.error_message = error.response.data.message;
            });
        },
        downloadWebhookMessages: function () {
            this.messages = [];
            axios.get('./api/v1/webhooks/' + this.id + '/messages').then(response => {
                for (let i in response.data.data) {
                    if (response.data.data.hasOwnProperty(i)) {
                        let current = response.data.data[i];
                        this.messages.push({
                            id: current.id,
                            created_at: format(new Date(current.attributes.created_at), this.$t('config.date_time_fns')),
                            uuid: current.attributes.uuid,
                            success: current.attributes.sent && !current.attributes.errored,
                            message: current.attributes.message,
                        });
                    }
                }
                this.loading = false;
            });
        },
        resetSecret: function () {
            axios.put('./api/v1/webhooks/' + this.id, {secret: 'anything'}).then(() => {
                this.downloadWebhook();
            });
        },

        showWebhookMessage: function (id) {
            axios.get('./api/v1/webhooks/' + this.id + '/messages/' + id).then(response => {
                $('#messageModal').modal('show');
                this.message_content = response.data.data.attributes.message;
            });
        },
        showWebhookAttempts: function (id) {
            this.message_attempts = [];
            axios.get('./api/v1/webhooks/' + this.id + '/messages/' + id + '/attempts').then(response => {
                $('#attemptModal').modal('show');
                for (let i in response.data.data) {
                    if (response.data.data.hasOwnProperty(i)) {
                        let current = response.data.data[i];
                        this.message_attempts.push({
                            id: current.id,
                            created_at: format(new Date(current.attributes.created_at), this.$t('config.date_time_fns')),
                            logs: current.attributes.logs,
                            status_code: current.attributes.status_code,
                            response: current.attributes.response,
                        });
                    }
                }
            });
        },

    }
};


const comps = {
    show,
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
