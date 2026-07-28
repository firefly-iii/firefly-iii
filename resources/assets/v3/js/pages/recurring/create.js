/*
 * dashboard.js
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
import Autocomplete from "bootstrap5-autocomplete";
import Tags from "bootstrap5-tags";

import {Calendar} from 'fullcalendar';
import themePlugin from 'fullcalendar/themes/monarch'; // YOUR THEME
import dayGridPlugin from 'fullcalendar/daygrid';
import timeGridPlugin from 'fullcalendar/timegrid';
import listPlugin from 'fullcalendar/list';

// stylesheets
import 'fullcalendar/skeleton.css'; // ALWAYS NEED SKELETON
import 'fullcalendar/themes/monarch/theme.css'; // YOUR THEME
import 'fullcalendar/themes/monarch/palettes/purple.css'; // YOUR THEME'S PALETTE

let calendarEl = document.getElementById('recurring_calendar');
let calendar = new Calendar(calendarEl, {
    plugins: [themePlugin, dayGridPlugin, timeGridPlugin, listPlugin],
    initialView: 'dayGridMonth',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,listWeek'
    }
});
calendar.render();


let create = function () {
    return {
        calendar: null,
        eventSource: null,
        init() {
            console.log('Init recurring create');
            this.createTagField();
            this.createAutocomplete();
            this.createButtonSwitcher();
            this.switchTransactionType('withdrawal');
            this.respondToFirstDateChange();
            this.respondToRepetitionEnd();
            document.getElementById('ffInput_first_date').addEventListener('change', this.respondToFirstDateChange.bind(this));
            document.getElementById('ffInput_repetition_end').addEventListener('change', this.respondToRepetitionEnd.bind(this));

            // TODO create calendar
            this.calendar = new Calendar(calendarEl, {
                plugins: [dayGridPlugin],
                initialView: 'dayGridMonth'
            });

            document.getElementById('calendar-link').addEventListener('click', this.showRepCalendar);
        },
        showRepCalendar() {

            // pre-append URL with repetition info:
            let newEventsUrl = eventsUrl + '?type=' + document.getElementById('ffInput_repetition_type').value;
            newEventsUrl += '&skip=' + document.getElementById('ffInput_skip').value;
            newEventsUrl += '&ends=' + document.getElementById('ffInput_repetition_end').value;
            newEventsUrl += '&end_date=' + document.getElementById('ffInput_repeat_until').value;
            newEventsUrl += '&reps=' + document.getElementById('ffInput_repetitions').value;
            newEventsUrl += '&first_date=' + document.getElementById('ffInput_first_date').value;
            newEventsUrl += '&weekend=' + document.getElementById('ffInput_weekend').value;

            console.log(newEventsUrl);

            // remove all event sources from calendar:
            let eventSource = new EventSource(newEventsUrl);
            calendar.removeAllEventSources();
            calendar.addEventSource(eventSource);
            $('#calendarModal').modal('show');

            return false;
        },
        respondToRepetitionEnd() {
            var obj = document.getElementById('ffInput_repetition_end');
            var value = obj.value;
            switch (value) {
                case 'forever':
                    document.getElementById('repeat_until_holder').style.display = 'none';
                    document.getElementById('repetitions_holder').style.display = 'none';
                    break;
                case 'until_date':
                    document.getElementById('repeat_until_holder').style.display = 'block';
                    document.getElementById('repetitions_holder').style.display = 'none';

                    break;
                case 'times':
                    document.getElementById('repeat_until_holder').style.display = 'none';
                    document.getElementById('repetitions_holder').style.display = 'block';
                    break;
            }
        },
        respondToFirstDateChange() {
            let obj = document.getElementById('ffInput_first_date');
            let select = document.getElementById('ffInput_repetition_type');
            let date = obj.value;
            select.disabled = true;

            // preselected value:
            var preSelected = oldRepetitionType;
            if (preSelected === '') {
                preSelected = select.value;
            }

            $.getJSON(suggestUrl, {date: date, pre_select: preSelected, past: 'true'}).fail(function () {
                console.error('Could not load repetition suggestions');
                alert('Could not load repetition suggestions. Please enter a valid date.');
            }).done(this.parseRepetitionSuggestions);
        },
        parseRepetitionSuggestions(data) {
            let select = document.getElementById('ffInput_repetition_type');
            select.innerHTML = '';
            let opt;
            for (var k in data) {
                if (data.hasOwnProperty(k)) {
                    console.log('label: ' + data[k].label + ', selected: ' + data[k].selected);
                    opt = document.createElement('option');
                    opt.value = k;
                    opt.label = data[k].label;
                    opt.text = data[k].label;
                    if (data[k].selected) {
                        opt.selected = true;
                    }
                    select.appendChild(opt);
                }
            }
            select.disabled = false;
        },


        createButtonSwitcher() {
            console.log('Now in initializeButtons()');
            let list = document.getElementsByClassName('switch-button');
            for (let i = 0; i < list.length; i++) {

                list[i].addEventListener('click', (event) => {
                    let transactionType = event.currentTarget.dataset.value;
                    // console.log('Clicked value is ' + transactionType);
                    this.switchTransactionType(transactionType);
                    return false;
                });
            }
            // $.each($('.switch-button'), function (i, v) {
            //     //var btn = $(v);
            //
            //     if (btn.data('value') === transactionType) {
            //         btn.addClass('btn-info disabled').removeClass('btn-default');
            //         $('input[name="transaction_type"]').val(transactionType);
            //     } else {
            //         btn.removeClass('btn-info disabled').addClass('btn-default');
            //     }
            // });
            // updateFormFields();
        },
        switchTransactionType(transactionType) {
            let list = document.getElementsByClassName('switch-button');
            for (let i = 0; i < list.length; i++) {
                let currentType = list[i].dataset.value;
                console.log('Selected is ' + currentType + ', form type is ' + transactionType);
                if (currentType === transactionType) {
                    list[i].classList.add('btn-primary');
                    list[i].classList.remove('btn-secondary');
                }
                if (currentType !== transactionType) {
                    list[i].classList.remove('btn-primary');
                    list[i].classList.add('btn-secondary');
                }
            }
            if ('withdrawal' === transactionType) {
                // hide source account name:
                document.getElementById('deposit_source_id_holder').style.display = 'none';

                // // show source account ID:
                document.getElementById('source_id_holder').style.display = 'block';

                // show destination name:
                document.getElementById('withdrawal_destination_id_holder').style.display = 'block';

                // // hide destination ID:
                document.getElementById('destination_id_holder').style.display = 'none';

                // show budget + bill
                document.getElementById('budget_id_holder').style.display = 'block';
                document.getElementById('bill_id_holder').style.display = 'block';

                // hide piggy bank:
                document.getElementById('piggy_bank_id_holder').style.display = 'none';
            }

            if (transactionType === 'deposit') {
                document.getElementById('deposit_source_id_holder').style.display = 'block';

                document.getElementById('source_id_holder').style.display = 'none';

                document.getElementById('withdrawal_destination_id_holder').style.display = 'none';

                document.getElementById('destination_id_holder').style.display = 'block';
                document.getElementById('budget_id_holder').style.display = 'none';
                document.getElementById('bill_id_holder').style.display = 'none';
                document.getElementById('piggy_bank_id_holder').style.display = 'none';
            }

            if (transactionType === 'transfer') {
                document.getElementById('deposit_source_id_holder').style.display = 'none';
                document.getElementById('source_id_holder').style.display = 'block';
                document.getElementById('withdrawal_destination_id_holder').style.display = 'none';
                document.getElementById('destination_id_holder').style.display = 'block';
                document.getElementById('budget_id_holder').style.display = 'none';
                document.getElementById('bill_id_holder').style.display = 'none';
                document.getElementById('piggy_bank_id_holder').style.display = 'block';
            }
        },
        createTagField() {
            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            Tags.init('#ffInput_tags',
                {
                    allowNew: true,
                    allowClear: true,
                    server: './api/v1/autocomplete/tags?_token=' + token,
                    liveServer: true,
                    labelField: 'name',
                    valueField: 'name',
                    fetchOptions: {
                        method: 'GET',
                        credentials: 'include',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token
                        }
                    }
                }
            );
        },
        createAutocomplete() {
            this.createCategoryAutocomplete();
        },
        createCategoryAutocomplete() {
            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            Autocomplete.init('#ffInput_category', {

                server: './api/v1/autocomplete/categories?_token=' + token,
                labelField: 'name',
                valueField: 'name',
                liveServer: true,
                fetchOptions: {
                    method: 'GET',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    }
                }
            });
        },
    }
};


const comps = {
    create,
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
