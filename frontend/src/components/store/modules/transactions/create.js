/*
 * create.js
 * Copyright (c) 2020 james@firefly-iii.org
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

const lodashClonedeep = require('lodash.clonedeep');

import {getDefaultTransaction, getDefaultErrors} from '../../../../shared/transactions';

// initial state
const state = () => ({
        transactionType: 'any',
        groupTitle: '',
        transactions: [],
        customDateFields: {
            interest_date: false,
            book_date: false,
            process_date: false,
            due_date: false,
            payment_date: false,
            invoice_date: false,
        },
        defaultTransaction: getDefaultTransaction(),
        defaultErrors: getDefaultErrors()
    }
)


// getters
const getters = {
    transactions: state => {
        return state.transactions;
    },
    groupTitle: state => {
        return state.groupTitle;
    },
    transactionType: state => {
        return state.transactionType;
    },
    accountToTransaction: state => {
        // TODO better architecture here, does not need the store.
        // possible API point!!
        return state.accountToTransaction;
    },
    defaultTransaction: state => {
        return state.defaultTransaction;
    },
    sourceAllowedTypes: state => {
        return state.sourceAllowedTypes;
    },
    destinationAllowedTypes: state => {
        return state.destinationAllowedTypes;
    },
    allowedOpposingTypes: state => {
        return state.allowedOpposingTypes;
    },
    customDateFields: state => {
        return state.customDateFields;
    }
    // // `getters` is localized to this module's getters
    // // you can use rootGetters via 4th argument of getters
    // someGetter (state, getters, rootState, rootGetters) {
    //     getters.someOtherGetter // -> 'foo/someOtherGetter'
    //     rootGetters.someOtherGetter // -> 'someOtherGetter'
    //     rootGetters['bar/someOtherGetter'] // -> 'bar/someOtherGetter'
    // },

}

// actions
const actions = {}

// mutations
const mutations = {
    addTransaction(state) {
        let newTransaction = lodashClonedeep(state.defaultTransaction);
        newTransaction.errors = lodashClonedeep(state.defaultErrors);
        state.transactions.push(newTransaction);
    },
    resetErrors(state, payload) {
        //console.log('resetErrors for index ' + payload.index);
        state.transactions[payload.index].errors = lodashClonedeep(state.defaultErrors);
    },
    resetTransactions(state) {
        state.transactions = [];
    },
    setGroupTitle(state, payload) {
        state.groupTitle = payload.groupTitle;
    },
    setCustomDateFields(state, payload) {
        state.customDateFields = payload;
    },
    deleteTransaction(state, payload) {
        state.transactions.splice(payload.index, 1);
        // console.log('Deleted transaction ' + payload.index);
        // console.log(state.transactions);
        if (0 === state.transactions.length) {
            // console.log('array is empty!');
        }
    },
    setTransactionType(state, transactionType) {
        state.transactionType = transactionType;
    },
    setAllowedOpposingTypes(state, allowedOpposingTypes) {
        state.allowedOpposingTypes = allowedOpposingTypes;
    },
    setAccountToTransaction(state, payload) {
        state.accountToTransaction = payload;
    },
    updateField(state, payload) {
        state.transactions[payload.index][payload.field] = payload.value;
    },
    setTransactionError(state, payload) {
        //console.log('Will set transactions[' + payload.index + '][errors][' + payload.field + '] to ');
        //console.log(payload.errors);
        state.transactions[payload.index].errors[payload.field] = payload.errors;
    },
    setDestinationAllowedTypes(state, payload) {
        // console.log('Destination allowed types was changed!');
        state.destinationAllowedTypes = payload;
    },
    setSourceAllowedTypes(state, payload) {
        state.sourceAllowedTypes = payload;
    }
}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}
