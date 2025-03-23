/*
 * add-autocomplete.js
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

import Autocomplete from "bootstrap5-autocomplete";

export function getUrls() {
    return {
        description: '/api/v1/autocomplete/transaction-descriptions',
        account: '/api/v1/autocomplete/accounts',
        category: '/api/v1/autocomplete/categories',
        tag: '/api/v1/autocomplete/tags',
    }
}

export function addAutocomplete(options) {
    const params = {
        server: options.serverUrl,
        serverParams: {},
        fetchOptions: {
            headers: {
                'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
            }
        },
        queryParam: 'filter[query]',
        hiddenInput: true,
        // preventBrowserAutocomplete: true,
        highlightTyped: true,
        liveServer: true,
    };
    if (typeof options.account_types !== 'undefined' && options.account_types.length > 0) {
        params.serverParams['filter[account_types]'] = options.account_types;
    }
    if (typeof options.onRenderItem !== 'undefined' && null !== options.onRenderItem) {
        params.onRenderItem = options.onRenderItem;
    }
    if (options.valueField) {
        params.valueField = options.valueField;
    }
    if (options.labelField) {
        params.labelField = options.labelField;
    }
    if (options.onSelectItem) {
        params.onSelectItem = options.onSelectItem;
    }
    if (options.onChange) {
        params.onChange = options.onChange;
    }
    if(options.hiddenValue) {
        params.hiddenValue = options.hiddenValue;
    }

    Autocomplete.init(options.selector, params);
}
