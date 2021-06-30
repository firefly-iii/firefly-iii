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
        timezone: '',
        cacheKey: {
            age: 0,
            value: 'empty',
        },
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
    cacheKey: state => {
        return state.cacheKey.value;
    },
}

// actions
const actions = {
    initialiseStore(context) {
        // cache key auto refreshes every day
        // console.log('Now in initialize store.')
        if (localStorage.cacheKey) {
            // console.log('Storage has cache key: ');
            // console.log(localStorage.cacheKey);
            let object = JSON.parse(localStorage.cacheKey);
            if (Date.now() - object.age > 86400000) {
                // console.log('Key is here but is old.');
                context.commit('refreshCacheKey');
            } else {
                // console.log('Cache key from local storage: ' + object.value);
                context.commit('setCacheKey', object);
            }
        } else {
            // console.log('No key need new one.');
            context.commit('refreshCacheKey');
        }
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
    refreshCacheKey(state) {
        let age = Date.now();
        let N = 8;
        let cacheKey = Array(N+1).join((Math.random().toString(36)+'00000000000000000').slice(2, 18)).slice(0, N);
        let object = {age: age, value: cacheKey};
        // console.log('Store new key in string JSON');
        // console.log(JSON.stringify(object));
        localStorage.cacheKey = JSON.stringify(object);
        state.cacheKey = {age: age, value: cacheKey};
        // console.log('Refresh: cachekey is now ' + cacheKey);
    },
    setCacheKey(state, payload) {
        // console.log('Stored cache key in localstorage.');
        // console.log(payload);
        // console.log(JSON.stringify(payload));
        localStorage.cacheKey = JSON.stringify(payload);
        state.cacheKey = payload;
    },
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
