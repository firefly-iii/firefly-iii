/*
 * edit.js
 * Copyright (c) 2026 james@firefly-iii.org
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


import '../../boot/bootstrap.js';
import sidebar from '../../pages/shared/sidebar.js';
import dates from '../shared/dates.js';
import {createTagField} from "../../form/create-tag-field.js";
import {createAutocomplete} from "../../form/create-autocomplete.js";
import {createButtonSwitcher} from "./shared/create-button-switcher.js";
import {switchTransactionType} from "./shared/switch-transaction-type.js";
import {respondToFirstDateChange} from './shared/respond-to-first-date-change.js';
import {respondToRepetitionEnd} from './shared/respond-to-repetition-end.js';
import {Calendar} from "fullcalendar";
import themePlugin from "fullcalendar/themes/monarch";
import dayGridPlugin from "fullcalendar/daygrid";
import timeGridPlugin from "fullcalendar/timegrid";
import listPlugin from "fullcalendar/list";

// stylesheets
import 'fullcalendar/skeleton.css'; // ALWAYS NEED SKELETON
import 'fullcalendar/themes/monarch/theme.css'; // YOUR THEME
import 'fullcalendar/themes/monarch/palettes/purple.css'; // YOUR THEME'S PALETTE

let edit = function () {
    return {
        init() {
            createTagField('ffInput_tags');
            createAutocomplete('ffInput_category','./api/v1/autocomplete/categories');
            createButtonSwitcher();
            switchTransactionType(document.getElementsByName('transaction_type')[0].value);

            let type = document.getElementById('repetitionType').value;
            let suggestUrl = document.getElementById('suggestUrl').value;
            respondToFirstDateChange(type, suggestUrl);
            respondToRepetitionEnd();
            document.getElementById('ffInput_first_date').addEventListener('change', respondToFirstDateChange.bind(this));
            document.getElementById('ffInput_repetition_end').addEventListener('change', respondToRepetitionEnd.bind(this));

            let calendarEl = document.getElementById('recurring_calendar');
            this.calendar = new Calendar(calendarEl, {
                plugins: [themePlugin, dayGridPlugin, timeGridPlugin, listPlugin],
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title'
                }
            });
            this.calendar.render();
            document.getElementById('calendar-link').addEventListener('click', this.showRepCalendar.bind(this));
        },

        showRepCalendar() {
            let eventsUrl = document.getElementById('eventsUrl').value;
            // pre-append URL with repetition info:
            let newEventsUrl = eventsUrl + '?type=' + document.getElementById('ffInput_repetition_type').value;
            newEventsUrl += '&skip=' + document.getElementById('ffInput_skip').value;
            newEventsUrl += '&ends=' + document.getElementById('ffInput_repetition_end').value;
            newEventsUrl += '&end_date=' + document.getElementById('ffInput_repeat_until').value;
            newEventsUrl += '&reps=' + document.getElementById('ffInput_repetitions').value;
            newEventsUrl += '&first_date=' + document.getElementById('ffInput_first_date').value;
            newEventsUrl += '&weekend=' + document.getElementById('ffInput_weekend').value;

            let eventSource = new EventSource(newEventsUrl);
            this.calendar.removeAllEventSources();
            this.calendar.addEventSource(eventSource);
            $('#calendarModal').modal('show');

            return false;
        }
    }
};


const comps = {
    edit,
    sidebar,
    dates
};

function loadPage(comps) {
    // console.log('loadPage');
    Object.keys(comps).forEach(comp => {
        let data = comps[comp]();
        Alpine.data(comp, () => data);
        // console.log(comp);
    });
    Alpine.start();
}

// wait for load until bootstrapped event is received.
document.addEventListener('firefly-iii-bootstrapped', () => {
    // console.log('Loaded through event listener.');
    loadPage(comps);
});
// or is bootstrapped before event is triggered.
if (window.bootstrapped) {
    // console.log('Loaded through window variable.');
    loadPage(comps);
}
