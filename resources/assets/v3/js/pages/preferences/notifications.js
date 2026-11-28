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
import sidebar from "../shared/sidebar.js";
import dates from "../shared/dates.js";
import Alpine from "@alpinejs/csp";
import Get from '../../api/configuration/get.js';
import i18next from 'i18next';
import {getVariables} from '../../store/get-variables.js';

window.enableDates = false;

let index = function () {
    return {
        i18next: null,
        loadingChannels: true,
        channels: {},
        init() {
            this.i18next = i18next;
            this.loadChannels();
        },
        loadChannels() {
            (new Get).getByName('notifications.channels').then((response) => {
                this.channels = response.data.data.value;
                console.log(this.channels);
            }).then(() => {
                this.getPreferences();
            });
        },
        getPreferences() {
            // secretly we know the available channels are email, slack and pushover, and we can grab the preferences for them.
            const prefs = ['pushover_app_token','pushover_user_token','slack_webhook_url'];
            getVariables(prefs).then((values) => {
                this.channels.pushover.app_token = values.pushover_app_token;
                this.channels.pushover.user_token = values.pushover_user_token;
                this.channels.pushover.enabled = (this.channels.pushover.app_token !== '' && this.channels.pushover.user_token !== '');
                this.channels.slack.webhook_url = values.slack_webhook_url;
                this.channels.slack.enabled = (this.channels.slack.webhook_url !== '');
                this.loadingChannels = false;
            });
        }
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
