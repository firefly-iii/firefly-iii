import {defineStore} from 'pinia';
import {
  endOfDay,
  endOfMonth,
  endOfQuarter,
  endOfWeek,
  startOfDay,
  startOfMonth,
  startOfQuarter,
  startOfWeek,
  startOfYear,
  subDays
} from "date-fns";

export const useFireflyIIIStore = defineStore('counter', {
  state: () => ({
    drawerState: true, viewRange: '1M', listPageSize: 10, range: {
      start: null, end: null
    }, defaultRange: {
      start: null, end: null
    }, currencyCode: 'AAA', currencyId: '0', cacheKey: 'initial'
  }),

  getters: {
    getViewRange(state) {
      return state.viewRange;
    },

    getListPageSize(state) {
      return state.listPageSize;
    },

    getCurrencyCode(state) {
      return state.currencyCode;
    }, getCurrencyId(state) {
      return state.currencyId;
    },

    getRange(state) {
      return state.range;
    }, getDefaultRange(state) {
      return state.defaultRange;
    },

    getCacheKey(state) {
      return state.cacheKey;
    }
  },

  actions: {
    // actions
    refreshCacheKey() {
      let cacheKey = Math.random().toString(36).replace(/[^a-z]+/g, '').slice(0, 8);
      this.setCacheKey(cacheKey);
    },

    resetRange() {
      let defaultRange = this.defaultRange;
      this.setRange(defaultRange);
    },

    setDatesFromViewRange() {
      let start;
      let end;
      let viewRange = this.viewRange;

      let today = new Date;
      switch (viewRange) {
        case 'last365':
          start = startOfDay(subDays(today, 365));
          end = endOfDay(today);
          break;
        case 'last90':
          start = startOfDay(subDays(today, 90));
          end = endOfDay(today);
          break;
        case 'last30':
          start = startOfDay(subDays(today, 30));
          end = endOfDay(today);
          break;
        case  'last7':
          start = startOfDay(subDays(today, 7));
          end = endOfDay(today);
          break;
        case  'YTD':
          start = startOfYear(today);
          end = endOfDay(today);
          break;
        case  'QTD':
          start = startOfQuarter(today);
          end = endOfDay(today);
          break;
        case  'MTD':
          start = startOfMonth(today);
          end = endOfDay(today);
          break;
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
      this.setRange({start: start, end: end});
      this.setDefaultRange({start: start, end: end});
    },

    // mutators

    increment() {
      this.counter++
    },
    updateViewRange(viewRange) {
      this.viewRange = viewRange;
    },

    updateListPageSize(value) {
      this.listPageSize = value;
    },

    setRange(value) {
      this.range = value;
      return value;
    },

    setDefaultRange(value) {
      this.defaultRange = value;
    },

    setCurrencyCode(value) {
      this.currencyCode = value;
    }, setCurrencyId(value) {
      this.currencyId = value;
    },

    setCacheKey(value) {
      this.cacheKey = value;
    }
  }
})
