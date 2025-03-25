/*
 * load-currencies.js
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


import Get from "../../../api/v1/model/currency/get.js";

export function loadCurrencies() {
    let params = {
        page: 1, limit: 1337
    };
    let getter = new Get();
    return getter.list(params).then((response) => {
        let returnData = {
            defaultCurrency: {},
            nativeCurrencies: [],
            foreignCurrencies: [],
            enabledCurrencies: [],
        };

        returnData.foreignCurrencies.push({
            id: 0, name: '(no foreign currency)', code: '', default: false, symbol: '', decimal_places: 2,
        });
        for (let i in response.data.data) {
            if (response.data.data.hasOwnProperty(i)) {
                let current = response.data.data[i];
                if (current.attributes.enabled) {
                    let obj =
                        {
                            id: current.id,
                            name: current.attributes.name,
                            code: current.attributes.code,
                            default: current.attributes.default,
                            symbol: current.attributes.symbol,
                            decimal_places: current.attributes.decimal_places,

                        };
                    if (obj.default) {
                        returnData.defaultCurrency = obj;
                    }
                    returnData.enabledCurrencies.push(obj);
                    returnData.nativeCurrencies.push(obj);
                    returnData.foreignCurrencies.push(obj);
                }
            }
        }
        return returnData;
    });
}
