/*
 * respond-to-first-date-change.js
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

function parseRepetitionSuggestions(data) {
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
}

function respondToFirstDateChange(oldRepetitionType, suggestUrl) {
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
    }).done(parseRepetitionSuggestions);
}

export {respondToFirstDateChange}
