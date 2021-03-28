/*
 * root.js
 * Copyright (c) 2021 james@firefly-iii.org
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
const state = () => (
    {
        listPageSize: 33,
    }
)


// getters
const getters = {
    listPageSize: state => {
        // console.log('Wil return ' + state.listPageSize);
        return state.listPageSize;
    },
}

// actions
const actions = {
    initialiseStore(context) {
        // console.log('Now in root initialiseStore');
        // if list length in local storage:
        if (localStorage.listPageSize) {
            // console.log('listPageSize is in localStorage')
            // console.log('Init list page size with value ');
            // console.log(localStorage.listPageSize);
            state.listPageSize = localStorage.listPageSize;
            context.commit('setListPageSize', {length: localStorage.listPageSize});
        }
        if (!localStorage.listPageSize) {
            axios.get('./api/v1/preferences/listPageSize')
                .then(response => {
                          // console.log('from API: listPageSize is ' + parseInt(response.data.data.attributes.data));
                          context.commit('setListPageSize', {length: parseInt(response.data.data.attributes.data)});
                      }
                );
        }
    }
}

// mutations
const mutations = {
    setListPageSize(state, payload) {
        // console.log('Got a payload in setListPageSize');
        // console.log(payload);
        let number = parseInt(payload.length);
        if (0 !== number) {
            state.listPageSize = number;

        }
    },
}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}
