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

// initial state
const state = () => ({
        transactionType: 'any',
        date: new Date,
        transactions: [],
        allowedOpposingTypes: {},
        accountToTransaction: {},
        sourceAllowedTypes: ['Asset account', 'Loan', 'Debt', 'Mortgage', 'Revenue account'],
        destinationAllowedTypes: ['Asset account', 'Loan', 'Debt', 'Mortgage', 'Expense account'],
        customDateFields: {
            interest_date: false,
            book_date: false,
            process_date: false,
            due_date: false,
            payment_date: false,
            invoice_date: false,
        },
        defaultTransaction: {
            // basic
            description: '',

            // accounts:
            source_account: {
                id: 0,
                name: "",
                name_with_balance: "",
                type: "",
                currency_id: 0,
                currency_name: '',
                currency_code: '',
                currency_decimal_places: 2
            },
            destination_account: {
                id: 0,
                name: "",
                type: "",
                currency_id: 0,
                currency_name: '',
                currency_code: '',
                currency_decimal_places: 2
            },

            // amount:
            amount: '',
            currency_id: 0,
            foreign_amount: '',
            foreign_currency_id: 0,

            // meta data
            budget_id: 0,
            bill_id: 0,
            piggy_bank_id: 0,
            tags: [],

            // optional date fields (6x):
            interest_date: null,
            book_date: null,
            process_date: null,
            due_date: null,
            payment_date: null,
            invoice_date: null,

            // optional other fields:
            internal_reference: null,
            external_url: null,
            notes: null,

            // transaction links:
            links: [],
            attachments: []
        },
    }
)


// getters
const getters = {
    transactions: state => {
        return state.transactions;
    },
    date: state => {
        return state.date;
    },
    transactionType: state => {
        return state.transactionType;
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
const actions = {
    calcTransactionType(context) {
        let source = context.state.transactions[0].source_account;
        let dest = context.state.transactions[0].destination_account;
        if (null === source || null === dest) {
            // console.log('transactionType any');
            context.commit('setTransactionType', 'any');
            return;
        }
        if ('' === source.type || '' === dest.type) {
            // console.log('transactionType any');
            context.commit('setTransactionType', 'any');
            return;
        }

        // ok so type is set on both:
        let expectedDestinationTypes = context.state.accountToTransaction[source.type];
        if ('undefined' !== typeof expectedDestinationTypes) {
            let transactionType = expectedDestinationTypes[dest.type];
            if ('undefined' !== typeof expectedDestinationTypes[dest.type]) {
                // console.log('Found a type: ' + transactionType);
                context.commit('setTransactionType', transactionType);
                return;
            }
        }
        // console.log('Found no type for ' + source.type + ' --> ' + dest.type);
        if ('Asset account' !== source.type) {
            console.log('Drop ID from source. TODO');

            // source.id =null
            // context.commit('updateField', {field: 'source_account',index: })
            // context.state.transactions[0].source_account.id = null;
        }
        if ('Asset account' !== dest.type) {
            console.log('Drop ID from destination. TODO');
            //context.state.transactions[0].destination_account.id = null;
        }

        context.commit('setTransactionType', 'any');
    }
}

// mutations
const mutations = {
    addTransaction(state) {
        let newTransaction = lodashClonedeep(state.defaultTransaction);
        state.transactions.push(newTransaction);
    },
    setDate(state, payload) {
        state.date = payload.date;
    },
    setCustomDateFields(state, payload) {
        state.customDateFields = payload;
    },
    deleteTransaction(state, payload) {
        state.transactions.splice(payload.index, 1);
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
