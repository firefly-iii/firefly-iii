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

let index = function () {
    return {
        webhooks: [],
        triggers: {
        },
        responses: {
        },
        deliveries: {
        },

        init() {
            console.log('init');
            this.getOptions();
        },
        getWebhooks: function () {
            console.log('getWebhooks');
            this.webhooks = [];
            this.downloadWebhooks(1);
        },
        toggleSecret: function (webhook) {
            webhook.show_secret = !webhook.show_secret;
        },
        downloadWebhooks: function (page) {
            console.log('downloadWebhooks');
            axios.get("./api/v1/webhooks?page=" + page).then((response) => {
                for (let i in response.data.data) {
                    if (response.data.data.hasOwnProperty(i)) {
                        let current = response.data.data[i];
                        let webhook = {
                            id: current.id,
                            title: current.attributes.title,
                            url: current.attributes.url,
                            active: current.attributes.active,
                            full_url: current.attributes.url,
                            secret: current.attributes.secret,
                            triggers: current.attributes.triggers,
                            responses: current.attributes.responses,
                            deliveries: current.attributes.deliveries,
                            show_secret: false,
                        };
                        if (current.attributes.url.length > 20) {
                            webhook.url = current.attributes.url.slice(0, 20) + '...';
                        }
                        this.webhooks.push(webhook);
                    }
                }

                if (response.data.meta.pagination.current_page < response.data.meta.pagination.total_pages) {
                    this.downloadWebhooks(response.data.meta.pagination.current_page + 1);
                }
            });
        },
        getOptions: function () {
            console.log('getOptions');
            // get triggers
            axios.get('./api/v1/configuration/webhook.triggers').then((response) => {
                for (let key in response.data.data.value) {
                    if (!response.data.data.value.hasOwnProperty(key)) {
                        continue;
                    }
                    this.triggers[key] = i18next.t('firefly.webhook_trigger_' + key);
                    console.log(key, this.triggers[key]);
                }

                // get responses
                axios.get('./api/v1/configuration/webhook.responses').then((response) => {
                    for (let key in response.data.data.value) {
                        if (!response.data.data.value.hasOwnProperty(key)) {
                            continue;
                        }
                        this.responses[key] = i18next.t('firefly.webhook_response_' + key);
                    }
                    // get deliveries
                    axios.get('./api/v1/configuration/webhook.deliveries').then((response) => {
                        for (let key in response.data.data.value) {
                            if (!response.data.data.value.hasOwnProperty(key)) {
                                continue;
                            }
                            this.deliveries[key] = i18next.t('firefly.webhook_delivery_' + key);
                        }
                        // get webhooks
                        this.getWebhooks();
                    })
                })
            });
        },
    }
};


const comps = {
    index,
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
