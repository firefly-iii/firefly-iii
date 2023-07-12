import './bootstrap';
import {
    addMonths,
    endOfDay,
    endOfMonth,
    endOfQuarter,
    endOfWeek,
    startOfDay,
    startOfMonth,
    startOfQuarter,
    startOfWeek,
    startOfYear,
    subDays, subMonths
} from "date-fns";
import format from './util/format'

class MainApp {
    range = {
        start: null, end: null
    };
    defaultRange = {
        start: null, end: null
    };
    viewRange = '1M';
    locale = 'en-US';
    language = 'en-US';

    constructor() {
        //console.log('MainApp constructor');
        // TODO load range from local storage (Apline)
    }

    init() {
        // get values from store and use them accordingly.
        this.viewRange = window.BasicStore.viewRange;
        this.locale = window.BasicStore.locale;
        this.language = window.BasicStore.language;
        this.locale = 'equal' === this.locale ? this.language : this.locale;
        window.__localeId__ = this.language;

        // the range is always null but later on we will store it in BasicStore.
        if (null === this.range.start && null === this.range.end
            && null === this.defaultRange.start && null === this.defaultRange.end
        ) {
            this.setDatesFromViewRange();
        }
    }

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
        this.range = {start: start, end: end};
        this.defaultRange = {start: start, end: end};
    }

    buildDateRange() {

        // generate ranges
        let nextRange = this.getNextRange();
        let prevRange = this.getPrevRange();
        let last7 = this.lastDays(7);
        let last30 = this.lastDays(30);
        let mtd = this.mtd();
        let ytd = this.ytd();

        // set the title:
        let element = document.getElementsByClassName('daterange-holder')[0];
        element.textContent = format(this.range.start) + ' - ' + format(this.range.end);
        element.setAttribute('data-start', format(this.range.start, 'yyyy-MM-dd'));
        element.setAttribute('data-end', format(this.range.end, 'yyyy-MM-dd'));

        // set the current one
        element = document.getElementsByClassName('daterange-current')[0];
        element.textContent = format(this.range.start) + ' - ' + format(this.range.end);
        element.setAttribute('data-start', format(this.range.start, 'yyyy-MM-dd'));
        element.setAttribute('data-end', format(this.range.end, 'yyyy-MM-dd'));

        // generate next range
        element = document.getElementsByClassName('daterange-next')[0];
        element.textContent = format(nextRange.start) + ' - ' + format(nextRange.end);
        element.setAttribute('data-start', format(nextRange.start, 'yyyy-MM-dd'));
        element.setAttribute('data-end', format(nextRange.end, 'yyyy-MM-dd'));

        // previous range.
        element = document.getElementsByClassName('daterange-prev')[0];
        element.textContent = format(prevRange.start) + ' - ' + format(prevRange.end);
        element.setAttribute('data-start', format(prevRange.start, 'yyyy-MM-dd'));
        element.setAttribute('data-end', format(prevRange.end, 'yyyy-MM-dd'));

        // last 7
        element = document.getElementsByClassName('daterange-7d')[0];
        element.setAttribute('data-start', format(last7.start, 'yyyy-MM-dd'));
        element.setAttribute('data-end', format(last7.end, 'yyyy-MM-dd'));

        // last 30
        element = document.getElementsByClassName('daterange-90d')[0];
        element.setAttribute('data-start', format(last30.start, 'yyyy-MM-dd'));
        element.setAttribute('data-end', format(last30.end, 'yyyy-MM-dd'));

        // MTD
        element = document.getElementsByClassName('daterange-mtd')[0];
        element.setAttribute('data-start', format(mtd.start, 'yyyy-MM-dd'));
        element.setAttribute('data-end', format(mtd.end, 'yyyy-MM-dd'));

        // YTD
        element = document.getElementsByClassName('daterange-ytd')[0];
        element.setAttribute('data-start', format(ytd.start, 'yyyy-MM-dd'));
        element.setAttribute('data-end', format(ytd.end, 'yyyy-MM-dd'));

        // custom range.
    }

    getNextRange() {
        let nextMonth = addMonths(this.range.start, 1);
        let end = endOfMonth(nextMonth);
        return {start: nextMonth, end: end};
    }

    getPrevRange() {
        let prevMonth = subMonths(this.range.start, 1);
        let end = endOfMonth(prevMonth);
        return {start: prevMonth, end: end};
    }

    ytd() {
        let end = this.range.start;
        let start = startOfYear(this.range.start);
        return {start: start, end: end};
    }

    mtd() {
        let end = this.range.start;
        let start = startOfMonth(this.range.start);
        return {start: start, end: end};
    }

    lastDays(days) {
        let end = this.range.start;
        let start = subDays(end, days);
        return {start: start, end: end};
    }
}

let app = new MainApp();

// Listen for the basic store, we need it to continue with the
document.addEventListener(
    "BasicStoreReady",
    (e) => {
        // e.target matches elem
        app.init();
        app.buildDateRange();
    },
    false,
);

function handleClick(e) {
    console.log('here we are');
    e.preventDefault();
    alert('OK');
    return false;
}

export {app, handleClick};
