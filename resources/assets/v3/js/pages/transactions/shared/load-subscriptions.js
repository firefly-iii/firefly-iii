/*
 * load-subscriptions.js
 * Copyright (c) 2024 james@firefly-iii.org
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

import SubscriptionGet from "../../../api/model/subscription/get.js";
import i18next from "i18next";

export function loadSubscriptions(includeInactive) {
    let params = {
        page: 1, limit: 1337
    };
    let getter = new SubscriptionGet();
    return getter.list(params).then((response) => {
        let subscriptions = {
            '0': {
                id: 0, name: '(no group)', order: 0, subscriptions: [{
                    id: 0, name: '(no subscription)', order: 0,
                }]
            }
        };
        for (let i in response.data.data) {
            if (response.data.data.hasOwnProperty(i)) {
                let current = response.data.data[i];
                let objectGroupId = current.attributes.object_group_id ?? '0';
                let objectGroupTitle = current.attributes.object_group_title ?? '(no group)';
                let subscription = {
                    id: current.id,
                    name: current.attributes.active ? current.attributes.name : current.attributes.name + ' ('+i18next.t('firefly.inactive').toLowerCase()+')',
                    order: current.attributes.order,
                };
                if(!includeInactive && false === current.attributes.active) {
                    // skip over inactive items.
                    continue;
                }
                if (!subscriptions.hasOwnProperty(objectGroupId)) {
                    subscriptions[objectGroupId] = {
                        id: objectGroupId,
                        name: objectGroupTitle,
                        order: current.attributes.object_group_order ?? 0,
                        subscriptions: []
                    };
                }
                subscriptions[objectGroupId].subscriptions.push(subscription);
                subscriptions[objectGroupId].subscriptions.sort((a, b) => a.order - b.order);
            }
        }
        return Object.keys(subscriptions).sort().reduce((obj, key) => {
            obj[key] = subscriptions[key];
            return obj;
        }, {});
    });
}
