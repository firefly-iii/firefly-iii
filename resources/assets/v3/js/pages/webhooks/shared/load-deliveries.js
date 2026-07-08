/*
 * load-deliveries.js
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

import i18next from "i18next";

function loadDeliveries() {
    let result = [];
    return axios.get('./api/v1/configuration/webhook.deliveries').then((response) => {
        for (let key in response.data.data.value) {
            if (!response.data.data.value.hasOwnProperty(key)) {
                continue;
            }
            result.push(
                {
                    id: key,
                    name: i18next.t('firefly.webhook_delivery_' + key),
                }
            );
        }
        return result;
    }).catch((error) => {
        console.error(error);
        return []
    });
}

export {loadDeliveries};
