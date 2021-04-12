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
        timezone: ''
    }
)


// getters
const getters = {
    listPageSize: state => {
        return state.listPageSize;
    },
    timezone: state => {
        // console.log('Wil return ' + state.listPageSize);
        return state.timezone;
    },
}

// actions
const actions = {
    initialiseStore(context) {
        if (localStorage.listPageSize) {
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
        if (localStorage.timezone) {
            state.timezone = localStorage.timezone;
            context.commit('setTimezone', {timezone: localStorage.timezone});
        }
        if (!localStorage.timezone) {
            axios.get('./api/v1/configuration/app.timezone')
                .then(response => {
                          context.commit('setTimezone', {timezone: response.data.data.value});
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
            localStorage.listPageSize = number;

        }
    },
    setTimezone(state, payload) {

        if ('' !== payload.timezone) {
            state.timezone = payload.timezone;
            localStorage.timezone = payload.timezone;
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
