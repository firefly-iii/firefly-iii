/*
 * load-piggy-banks.js
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

import Get from "../../../api/v1/model/piggy-bank/get.js";

export function loadPiggyBanks() {
    let params = {
        page: 1, limit: 1337
    };
    let getter = new Get();
    return getter.list(params).then((response) => {
        let piggyBanks = {
            '0': {
                id: 0, name: '(no group)', order: 0, piggyBanks: [{
                    id: 0, name: '(no piggy bank)', order: 0,
                }]
            }
        };

        for (let i in response.data.data) {
            if (response.data.data.hasOwnProperty(i)) {
                let current = response.data.data[i];
                let objectGroupId = current.attributes.object_group_id ?? '0';
                let objectGroupTitle = current.attributes.object_group_title ?? '(no group)';
                let piggyBank = {
                    id: current.id, name: current.attributes.name, order: current.attributes.order,
                };
                if (!piggyBanks.hasOwnProperty(objectGroupId)) {
                    piggyBanks[objectGroupId] = {
                        id: objectGroupId,
                        name: objectGroupTitle,
                        order: current.attributes.object_group_order ?? 0,
                        piggyBanks: []
                    };
                }
                piggyBanks[objectGroupId].piggyBanks.push(piggyBank);
                piggyBanks[objectGroupId].piggyBanks.sort((a, b) => a.order - b.order);
            }
        }
        //tempObject.sort((a,b) => a.order - b.order);
        return Object.keys(piggyBanks).sort().reduce((obj, key) => {
            obj[key] = piggyBanks[key];
            return obj;
        }, {});
    });
}
