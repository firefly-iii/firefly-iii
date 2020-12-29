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

let date = new Date;

// initial state
const state = () => ({
        transactionType: 'any',
        transactions: [],
        sourceAllowedTypes: ['Asset account', 'Revenue account', 'Loan', 'Debt', 'Mortgage'],
        defaultTransaction: {
            // basic
            description: '',
            date: date.toISOString().split('T')[0],
            time: ('0' + date.getHours()).slice(-2) + ':' + ('0' + date.getMinutes()).slice(-2) + ':' + ('0' + date.getSeconds()).slice(-2),

            // accounts:
            source_account: {
                id: 0,
                name: "",
                type: "",
                currency_id: 0,
                currency_name: '',
                currency_code: '',
                currency_decimal_places: 2
            },
            source_allowed_types: ['Asset account', 'Revenue account', 'Loan', 'Debt', 'Mortgage'],

            // meta data
            budget_id: 0
        },
    }
)


// getters
const getters = {
    transactions: state => {
        return state.transactions;
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
        state.transactions.push(state.defaultTransaction);
    },
    deleteTransaction(state, index) {
        this.state.transactions.splice(index, 1);
    },
    updateField(state, payload) {
        console.log('I am update field');
        console.log(payload)
        state.transactions[payload.index][payload.field] = payload.value;
    }
}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}
