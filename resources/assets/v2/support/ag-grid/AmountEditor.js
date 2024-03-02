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

export default class AmountEditor {

    init(params) {

        document.addEventListener('cellValueChanged', () => {
            console.log('I just realized a cell value has changed.');
        });

        console.log('AmountEditor.init');
        this.params = params;
        this.originalValue = params.value;
        this.eGui = document.createElement('div');

        this.input = document.createElement('input');
        this.input.type = 'number';
        this.input.min = '0';
        this.input.step = 'any';
        this.input.style.overflow = 'hidden';
        this.input.style.textOverflow = 'ellipsis';
        this.input.autofocus = true;
        this.input.value = parseFloat(params.value.amount).toFixed(params.value.decimal_places);

        //this.input.onchange = function(e) { this.onChange(e, params);}
        //  params.onValueChange;
        //this.input.onblur = params.onValueChange;

        // this.input.onblur = function () {
        //     params.stopEditing();
        // };

        // this.eGui.innerHTML = `<input
        // type="number" min="0"
        // onChange="params.onValueChange"
        // step="any" style="overflow: hidden; text-overflow: ellipsis" value="${parseFloat(params.value.amount).toFixed(params.value.decimal_places)}" />`;
    }
    onChange(e) {
        console.log('AmountEditor.onChange');
        this.params.onValueChange(e);
        this.params.stopEditing(e);
    }

    // focus and select can be done after the gui is attached
    afterGuiAttached() {
        this.input.focus();
        this.input.select();
    }


    getGui() {
        console.log('AmountEditor.getGui');
        this.eGui.appendChild(this.input);
        return this.eGui;
    }

    getValue() {
        console.log('AmountEditor.getValue');
        this.originalValue.amount = parseFloat(this.input.value);

        // needs a manual submission to Firefly III here.
        this.submitAmount(this.originalValue);


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
