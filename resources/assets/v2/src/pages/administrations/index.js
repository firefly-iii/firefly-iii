/*
 * show.js
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
import i18next from "i18next";
import {format} from "date-fns";

import '../../css/grid-ff3-theme.css';
import Get from "../../api/v1/model/user-group/get.js";
import Post from "../../api/v1/model/user-group/post.js";

let index = function () {
    return {
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
        editors: {},
        userGroups: [],

        format(date) {
            return format(date, i18next.t('config.date_time_fns'));
        },

        init() {
            this.notifications.wait.show = true;
            this.notifications.wait.text = i18next.t('firefly.wait_loading_data')
            this.loadAdministrations();
        },
        useAdministration(id) {
            let groupId = parseInt(id);
            // try to post "use", then reload administrations.
            (new Post()).use(groupId).then(response => {
               this.loadAdministrations();
            });
        },

        loadAdministrations() {
            this.userGroups = [];
            this.notifications.wait.show = true;
            this.notifications.wait.text = i18next.t('firefly.wait_loading_data')
            this.accounts = [];
            (new Get()).index({page: this.page}).then(response => {
                for (let i = 0; i < response.data.data.length; i++) {
                    if (response.data.data.hasOwnProperty(i)) {
                        let current = response.data.data[i];
                        let group = {
                            id: parseInt(current.id),
                            title: current.attributes.title,
                            in_use: current.attributes.in_use,
                            owner: '',
                            you: '',
                            memberCountExceptYou: 0,
                            isOwner: false,
                            membersVisible: current.attributes.can_see_members,
                            members: [],
                        };
                        let memberships = {};
                        for (let j = 0; j < current.attributes.members.length; j++) {
                            let member = current.attributes.members[j];
                            if ('owner' === member.role) {
                                group.owner = i18next.t('firefly.administration_owner', {email: member.user_email});
                            }
                            if (true === member.you && 'owner' === member.role) {
                                group.isOwner = true;
                            }
                            if (true === member.you) {
                                group.you = i18next.t('firefly.administration_you', {role: i18next.t('firefly.administration_role_' + member.role)});
                                continue;
                            }
                            if (false === member.you) {
                                group.memberCountExceptYou++;
                                const userEmail = member.user_email;
                                if (!memberships.hasOwnProperty(userEmail)) {
                                    memberships[userEmail] = {
                                        email: userEmail,
                                        roles: []
                                    };
                                }
                                memberships[userEmail].roles.push(i18next.t('firefly.administration_role_' + member.role));
                            }
                        }
                        group.members = Object.values(memberships);

                        this.userGroups.push(group);
                    }
                }
                this.notifications.wait.show = false;
                // add click trigger thing.
            });
        },
    }
}

let comps = {index, dates};

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
