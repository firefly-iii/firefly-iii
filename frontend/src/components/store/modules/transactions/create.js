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


// initial state
const state = () => ({
    transactionType: 'any',
    transactions: []
})


// getters
const getters = {
    transactions: state => {
        return state.transactions;
    },
    transactionType: state => {
        return state.transactionType;
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
        state.transactions.push(
            {
                description: '',
                date: new Date
            }
        );
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
