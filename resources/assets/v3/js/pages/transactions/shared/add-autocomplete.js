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
import i18next from "i18next";
import {
    changeCategory, changeDescription,
    changeDestinationAccount,
    changeSourceAccount,
    selectDestinationAccount,
    selectSourceAccount
} from "./autocomplete-functions.js";
import Tags from "bootstrap5-tags";

export function getUrls() {
    return {
        description: '/api/v1/autocomplete/transactions',
        account: '/api/v1/autocomplete/accounts',
        category: '/api/v1/autocomplete/categories',
        tag: '/api/v1/autocomplete/tags',
    }
}

export function addAllAutocompleteToForm(filters) {
    const urls = getUrls();
    setTimeout(() => {
        // addedSplit, is called from the HTML
        // for source account
        const renderAccount = function (item, b, c) {
            return item.name_with_balance + '<br><small class="text-muted">' + i18next.t('firefly.account_type_' + item.type) + '</small>';
        };

        // render tags:
        Tags.init('select.ac-tags', {
            allowClear: true,
            server: urls.tag,
            liveServer: true,
            clearEnd: true,
            labelField: 'tag',
            valueField: 'id',
            queryParam: 'query',
            allowNew: true,
            //serverDataKey: 'data',
            notFoundMessage: i18next.t('firefly.nothing_found'),
            noCache: true,
            fetchOptions: {
                headers: {
                    'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
                }
            }
        });
        addAutocomplete({
            selector: 'input.ac-source',
            serverUrl: urls.account,
            account_types: filters.source,
            onRenderItem: renderAccount,
            valueField: 'id',
            labelField: 'name',
            onChange: changeSourceAccount,
            onSelectItem: selectSourceAccount
        });
        addAutocomplete({
            selector: 'input.ac-dest',
            serverUrl: urls.account,
            valueField: 'id',
            labelField: 'name',
            account_types: filters.destination,
            onRenderItem: renderAccount,
            onChange: changeDestinationAccount,
            onSelectItem: selectDestinationAccount
        });
        addAutocomplete({
            selector: 'input.ac-category',
            serverUrl: urls.category,
            valueField: 'id',
            labelField: 'name',
            onChange: changeCategory,
            onSelectItem: changeCategory
        });
        addAutocomplete({
            selector: 'input.ac-description',
            serverUrl: urls.description,
            valueField: 'id',
            labelField: 'title',
            onChange: changeDescription,
            onSelectItem: changeDescription,
        });
    }, 150);
}

function addAutocomplete(options) {
    const params = {
        server: options.serverUrl,
        serverParams: {},
        fetchOptions: {
            headers: {
                'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
            }
        },
        queryParam: 'query',
        hiddenInput: true,
        // preventBrowserAutocomplete: true,
        highlightTyped: true,
        liveServer: true,
    };
    if (typeof options.account_types !== 'undefined' && options.account_types.length > 0) {
        params.serverParams['types'] = options.account_types;
    }
    if (typeof options.onRenderItem !== 'undefined' && null !== options.onRenderItem) {
        console.log('overrule onRenderItem.');
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
