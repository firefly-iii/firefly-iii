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

export function addAllAutocompleteToForm() {
    // filters are hard coded.
    // part of the account selection auto-complete
    let filters = {
        // source can never be expense account
        source: ['Asset account', 'Loan', 'Debt', 'Mortgage', 'Revenue account'],
        // destination can never be revenue account
        destination: ['Expense account', 'Loan', 'Debt', 'Mortgage', 'Asset account'],
    };
    // depending on the type of the transaction,
    // the filters are changed. For edit form, this means
    // the available account types may be limited.
    if('edit' === this.formBehaviour.formType) {

        if('withdrawal' === this.groupProperties.transactionType) {
            // filters.destination = ['Expense account'];
        }
        if('deposit' === this.groupProperties.transactionType) {
            // filters.source = ['Revenue account'];
        }
        if('transfer' === this.groupProperties.transactionType) {
            filters.source = [this.entries[0].source_account.type];
            filters.destination = [this.entries[0].source_account.type];
        }
    }

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
            valueField: 'tag',
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
            labelField: 'name',
            onChange: changeDescription,
            onSelectItem: changeDescription,
        });
    }, 150);
}

export function addAutocomplete(options) {
    const params = {
        server: options.serverUrl,
        serverParams: {},
        fixed: true,
        fetchOptions: {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
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
