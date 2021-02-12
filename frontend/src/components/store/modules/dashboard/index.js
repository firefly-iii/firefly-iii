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
const state = () => (
    {
        start: null,
        end: null,
        viewRange: 'default'
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
    viewRange: state => {
        return state.viewRange;
    }
}

// actions
const actions = {
    initialiseStore(context) {
        if ('default' === context.state.viewRange) {
            axios.get('./api/v1/preferences/viewRange')
                .then(response => {
                          let viewRange = response.data.data.attributes.data;
                          context.commit('setViewRange', viewRange);
                          // call another action:
                          context.dispatch('setDatesFromViewRange');
                      }
                ).catch(error => {
                console.log(error);
                context.commit('setViewRange', '1M');
                // call another action:
                context.dispatch('setDatesFromViewRange');
            });
        }
    },
    setDatesFromViewRange(context) {
        console.log('Must set dates from viewRange "' + context.state.viewRange + '"');
        // check local storage first?
        if (localStorage.viewRangeStart) {
            console.log('view range set from local storage.');
            context.commit('setStart', localStorage.viewRangeStart);
        }
        if (localStorage.viewRangeEnd) {
            console.log('view range set from local storage.');
            context.commit('setEnd', localStorage.viewRangeEnd);
        }
        if (null !== context.getters.end && null !== context.getters.start) {
            return;
        }
        console.log('view range must be calculated.');
        let start;
        let end;
        let viewRange = context.getters.viewRange;
        viewRange = '1Y';
        switch (viewRange) {
            case '1D':
                // one day:
                start = new Date;
                end = new Date(start.getTime());
                start.setHours(0, 0, 0, 0);
                end.setHours(23, 59, 59, 999);
                break;
            case '1W':
                // this week:
                start = new Date;
                end = new Date(start.getTime());
                // start of week
                let diff = start.getDate() - start.getDay() + (start.getDay() === 0 ? -6 : 1);
                start.setDate(diff);
                start.setHours(0, 0, 0, 0);

                // end of week
                let lastday = end.getDate() - (end.getDay() - 1) + 6;
                end.setDate(lastday);
                end.setHours(23, 59, 59, 999);
                break;
            case '1M':
                // this month:
                start = new Date;
                start = new Date(start.getFullYear(), start.getMonth(), 1);
                start.setHours(0, 0, 0, 0);
                end = new Date(start.getFullYear(), start.getMonth() + 1, 0);
                end.setHours(23, 59, 59, 999);
                break;
            case '3M':
                // this quarter
                start = new Date;
                end = new Date;
                let quarter = Math.floor((start.getMonth() + 3) / 3)-1;
                // start and end months? I'm sure this could be better:
                let startMonths = [0,3,6,9];
                let endMonths = [2,5,8,11];
                // set start to the correct month, day one:
                start = new Date(start.getFullYear(), startMonths[quarter], 1);
                start.setHours(0, 0, 0, 0);

                // set end to the correct month, day one
                end = new Date(end.getFullYear(), endMonths[quarter], 1);
                // then to the last day of the month:
                end = new Date(end.getFullYear(), end.getMonth() + 1, 0);
                end.setHours(23, 59, 59, 999);
                break;
            case '6M':
                // this half-year
                start = new Date;
                end = new Date;
                let half = start.getMonth()<= 5 ? 0 : 1;

                let startHalf = [0,6];
                let endHalf = [5,11];
                // set start to the correct month, day one:
                start = new Date(start.getFullYear(), startHalf[half], 1);
                start.setHours(0, 0, 0, 0);

                // set end to the correct month, day one
                end = new Date(end.getFullYear(), endHalf[half], 1);
                // then to the last day of the month:
                end = new Date(end.getFullYear(), end.getMonth() + 1, 0);
                end.setHours(23, 59, 59, 999);
                break;
            case '1Y':
                // this year
                start = new Date;
                end = new Date;
                start = new Date(start.getFullYear(), 0, 1);

                end = new Date(end.getFullYear(), 11, 31);
                start.setHours(0, 0, 0, 0);
                end.setHours(23, 59, 59, 999);
                break;
        }
        console.log('Range is ' + viewRange);
        console.log('Start is ' + start);
        console.log('End is   ' + end);
        context.commit('setStart', start);
        context.commit('setEnd', end);
    }
}

// mutations
const mutations = {
    setStart(state, value) {
        state.start = value;
    },
    setEnd(state, value) {
        state.end = value;
    },
    setViewRange(state, range) {
        state.viewRange = range;
    }
}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}
