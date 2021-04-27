/*
 * index.js
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
import startOfDay from "date-fns/startOfDay";
import endOfDay from 'date-fns/endOfDay'
import startOfWeek from 'date-fns/startOfWeek'
import endOfWeek from 'date-fns/endOfWeek'
import startOfQuarter from 'date-fns/startOfQuarter';
import endOfQuarter from 'date-fns/endOfQuarter';
import endOfMonth from "date-fns/endOfMonth";
import startOfMonth from 'date-fns/startOfMonth';

const state = () => (
    {
        viewRange: 'default',
        start: null,
        end: null,
        defaultStart: null,
        defaultEnd: null,
    }
)


// getters
const getters = {
    start: state => {
        return state.start;
    },
    end: state => {
        return state.end;
    },
    defaultStart: state => {
        return state.defaultStart;
    },
    defaultEnd: state => {
        return state.defaultEnd;
    },
    viewRange: state => {
        return state.viewRange;
    }
}

// actions
const actions = {
    initialiseStore(context) {
        // console.log('initialiseStore');

        // restore from local storage:
        context.dispatch('restoreViewRange');

        axios.get('./api/v1/preferences/viewRange')
            .then(response => {
                      let viewRange = response.data.data.attributes.data;
                      let oldViewRange = context.getters.viewRange;
                      context.commit('setViewRange', viewRange);
                      if (viewRange !== oldViewRange) {
                          // console.log('View range changed from "' + oldViewRange + '" to "' + viewRange + '"');
                          context.dispatch('setDatesFromViewRange');
                      }
                      if (viewRange === oldViewRange) {
                          // console.log('Restore view range dates');
                          context.dispatch('restoreViewRangeDates');
                      }
                  }
            ).catch(() => {
            context.commit('setViewRange', '1M');
            context.dispatch('setDatesFromViewRange');
        });

    },
    restoreViewRangeDates: function (context) {
        // check local storage first?
        if (localStorage.viewRangeStart) {
            // console.log('view range start set from local storage.');
            context.commit('setStart', new Date(localStorage.viewRangeStart));
        }
        if (localStorage.viewRangeEnd) {
            // console.log('view range end set from local storage.');
            context.commit('setEnd', new Date(localStorage.viewRangeEnd));
        }
        // also set default:
        if (localStorage.viewRangeDefaultStart) {
            // console.log('view range default start set from local storage.');
            // console.log(localStorage.viewRangeDefaultStart);
            context.commit('setDefaultStart', new Date(localStorage.viewRangeDefaultStart));
        }
        if (localStorage.viewRangeDefaultEnd) {
            // console.log('view range default end set from local storage.');
            // console.log(localStorage.viewRangeDefaultEnd);
            context.commit('setDefaultEnd', new Date(localStorage.viewRangeDefaultEnd));
        }
    },
    restoreViewRange: function (context) {
        // console.log('restoreViewRange');
        let viewRange = localStorage.getItem('viewRange');
        if (null !== viewRange) {
            // console.log('restored restoreViewRange ' + viewRange );
            context.commit('setViewRange', viewRange);
        }
    },
    setDatesFromViewRange(context) {
        let start;
        let end;
        let viewRange = context.getters.viewRange;
        let today = new Date;
        // console.log('Will recreate view range on ' + viewRange);
        switch (viewRange) {
            case '1D':
                // today:
                start = startOfDay(today);
                end = endOfDay(today);
                break;
            case '1W':
                // this week:
                start = startOfDay(startOfWeek(today, {weekStartsOn: 1}));
                end = endOfDay(endOfWeek(today, {weekStartsOn: 1}));
                break;
            case '1M':
                // this month:
                start = startOfDay(startOfMonth(today));
                end = endOfDay(endOfMonth(today));
                break;
            case '3M':
                // this quarter
                start = startOfDay(startOfQuarter(today));
                end = endOfDay(endOfQuarter(today));
                break;
            case '6M':
                // this half-year
                if (today.getMonth() <= 5) {
                    start = new Date(today);
                    start.setMonth(0);
                    start.setDate(1);
                    start = startOfDay(start);
                    end = new Date(today);
                    end.setMonth(5);
                    end.setDate(30);
                    end = endOfDay(start);
                }
                if (today.getMonth() > 5) {
                    start = new Date(today);
                    start.setMonth(6);
                    start.setDate(1);
                    start = startOfDay(start);
                    end = new Date(today);
                    end.setMonth(11);
                    end.setDate(31);
                    end = endOfDay(start);
                }
                break;
            case '1Y':
                // this year
                start = new Date(today);
                start.setMonth(0);
                start.setDate(1);
                start = startOfDay(start);

                end = new Date(today);
                end.setMonth(11);
                end.setDate(31);
                end = endOfDay(end);
                break;
        }
        // console.log('Range is ' + viewRange);
        // console.log('Start is ' + start);
        // console.log('End is   ' + end);
        context.commit('setStart', start);
        context.commit('setEnd', end);
        context.commit('setDefaultStart', start);
        context.commit('setDefaultEnd', end);
    }
}

// mutations
const mutations = {
    setStart(state, value) {
        state.start = value;
        window.localStorage.setItem('viewRangeStart', value);
    },
    setEnd(state, value) {
        state.end = value;
        window.localStorage.setItem('viewRangeEnd', value);
    },
    setDefaultStart(state, value) {
        state.defaultStart = value;
        window.localStorage.setItem('viewRangeDefaultStart', value);
    },
    setDefaultEnd(state, value) {
        state.defaultEnd = value;
        window.localStorage.setItem('viewRangeDefaultEnd', value);
    },
    setViewRange(state, range) {
        state.viewRange = range;
        window.localStorage.setItem('viewRange', range);
    }
}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}
