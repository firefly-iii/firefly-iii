/*
 * autocomplete-functions.js
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

export function changeCategory(item, ac) {
    const index = parseInt(ac._searchInput.attributes['data-index'].value);
    if (typeof item !== 'undefined' && item.name) {
        document.querySelector('#form')._x_dataStack[0].$data.entries[index].category_name = item.name;
        return;
    }
    document.querySelector('#form')._x_dataStack[0].$data.entries[index].category_name = ac._searchInput.value;
}

export function changeDescription(item, ac) {
    const index = parseInt(ac._searchInput.attributes['data-index'].value);
    if (typeof item !== 'undefined' && item.description) {
        document.querySelector('#form')._x_dataStack[0].$data.entries[index].description = item.description;
        return;
    }
    document.querySelector('#form')._x_dataStack[0].$data.entries[index].description = ac._searchInput.value;
}

export function changeDestinationAccount(item, ac) {
    if (typeof item === 'undefined') {
        const index = parseInt(ac._searchInput.attributes['data-index'].value);
        let destination = document.querySelector('#form')._x_dataStack[0].$data.entries[index].destination_account;

        if (destination.name === ac._searchInput.value) {
            console.warn('Ignore hallucinated destination account name change to "' + ac._searchInput.value + '"');
            return;
        }
        document.querySelector('#form')._x_dataStack[0].$data.entries[index].destination_account = {
            name: ac._searchInput.value, alpine_name: ac._searchInput.value,
        };
        document.querySelector('#form')._x_dataStack[0].changedDestinationAccount();
    }
}

export function selectDestinationAccount(item, ac) {
    const index = parseInt(ac._searchInput.attributes['data-index'].value);
    document.querySelector('#form')._x_dataStack[0].$data.entries[index].destination_account = {
        id: item.id, name: item.name, alpine_name: item.name, type: item.type, currency_code: item.currency_code,
    };
    document.querySelector('#form')._x_dataStack[0].changedDestinationAccount();
}

export function changeSourceAccount(item, ac) {
    if (typeof item === 'undefined') {
        const index = parseInt(ac._searchInput.attributes['data-index'].value);
        let source = document.querySelector('#form')._x_dataStack[0].$data.entries[index].source_account;
        if (source.name === ac._searchInput.value) {
            return;
        }
        document.querySelector('#form')._x_dataStack[0].$data.entries[index].source_account = {
            name: ac._searchInput.value, alpine_name: ac._searchInput.value,
        };

        document.querySelector('#form')._x_dataStack[0].changedSourceAccount();
    }
}

export function selectSourceAccount(item, ac) {
    const index = parseInt(ac._searchInput.attributes['data-index'].value);
    document.querySelector('#form')._x_dataStack[0].$data.entries[index].source_account = {
        id: item.id, name: item.name, alpine_name: item.name, type: item.type, currency_code: item.currency_code,
    };
    document.querySelector('#form')._x_dataStack[0].changedSourceAccount();
}
