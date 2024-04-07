/*
 * AmountEditor.js
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */



import Put from "../../api/v2/model/transaction/put.js";
import format from "../../util/format.js";

export default class DateTimeEditor {

    init(params) {
        console.log('DateTimeEditor.init');
        this.params = params;
        this.originalValue = params.value;
        this.eGui = document.createElement('div');

        this.input = document.createElement('input');
        this.input.type = 'datetime-local';
        this.input.style.overflow = 'hidden';
        this.input.style.textOverflow = 'ellipsis';
        this.input.value = format(params.value, 'yyyy-MM-dd HH:mm');
    }
    onChange(e) {
        console.log('DateTimeEditor.onChange');
        this.params.onValueChange(e);
        this.params.stopEditing(e);
    }

    // focus and select can be done after the gui is attached
    afterGuiAttached() {
        this.input.focus();
    }

    getGui() {
        console.log('DateTimeEditor.getGui');
        this.eGui.appendChild(this.input);
        return this.eGui;
    }

    getValue() {
        console.log('DateTimeEditor.getValue');
        this.originalValue = this.input.value;

        return this.originalValue;
    }

    submitAmount(value) {
        console.log('AmountEditor.submitAmount');
        console.log(value);
        const newValue = value.amount;
        console.log('New value for field "amount" in transaction journal #' + value.transaction_journal_id + ' of group #' + value.id + ' is "' + newValue + '"');

        // push update to Firefly III over API:
        let submission = {
            transactions: [
                {
                    transaction_journal_id: value.transaction_journal_id,
                    amount: newValue
                }
            ]
        };

        let putter = new Put();
        putter.put(submission, {id: value.id});
    }

}
