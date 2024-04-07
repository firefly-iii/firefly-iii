/*
 * GenericEditor.js
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

export default class GenericEditor {
    setElement(element) {
        console.log('GenericEditor.setElement()', element);
        this.element = element;
        this.parent = element.parentElement;
        this.options = {};
    }

    init() {
        // grab some options from element itself:
        this.options.type = this.element.dataset.type;
        this.options.id = this.element.dataset.id;
        this.options.value = this.element.dataset.value;
        this.options.index = this.element.dataset.index;
        this.options.model = this.element.dataset.model;
        this.options.field = this.element.dataset.field;
        //this.options.field = this.element.dataset.type;
        console.log('GenericEditor[' + this.options.index + '].init()');
    }

    replace() {
        console.log('GenericEditor[' + this.options.index + '].replace()');
        // save old HTML in data field (does that work, is it safe?)
        this.options.original = this.element.parentElement.innerHTML;
        if (this.options.type === 'text') {
            this.replaceText();
        }
    }

    replaceText() {
        console.log('GenericEditor[' + this.options.index + '].replaceText()');
        // create form
        let form = document.createElement('form');
        form.classList.add('form-inline');

        // create row
        let row = document.createElement('div');
        row.classList.add('row');

        // create column
        let column = document.createElement('div');
        column.classList.add('col-7');

        // create label, add to column
        let label = document.createElement('label');
        label.classList.add('sr-only');
        label.setAttribute('for', 'input');
        label.innerText = 'Field value';
        column.appendChild(label);

        // creat text field, add to column
        let input = document.createElement('input');
        input.classList.add('form-control');
        input.classList.add('form-control-sm');
        input.dataset.index = this.options.index + 'index';
        input.setAttribute('autocomplete', 'off');
        input.setAttribute('type', 'text');
        input.setAttribute('id', 'input');
        input.setAttribute('name', 'name');
        input.setAttribute('value', this.options.value);
        input.setAttribute('placeholder', this.options.value);
        input.setAttribute('autofocus', 'true');
        column.appendChild(input);

        // add column to row
        row.appendChild(column);

        // create column for buttons
        let column2 = document.createElement('div');
        column2.classList.add('col-5');
        column2.classList.add('text-right');

        // create button group
        let buttonGroup = document.createElement('div');
        buttonGroup.classList.add('btn-group');
        buttonGroup.classList.add('btn-group-sm');
        buttonGroup.setAttribute('role', 'group');
        buttonGroup.setAttribute('aria-label', 'Options');

        // add buttons
        let cancelButton = document.createElement('button');
        cancelButton.dataset.index = this.options.index;
        cancelButton.setAttribute('type', 'button');
        cancelButton.setAttribute('x-click', 'cancelInlineEdit');
        cancelButton.classList.add('btn');
        cancelButton.classList.add('btn-danger');

        // add icon to cancel button
        let icon = document.createElement('em');
        icon.classList.add('fa-solid');
        icon.classList.add('fa-xmark');
        icon.classList.add('text-white');
        cancelButton.appendChild(icon);

        // '<button data-index="' + this.options.index + '" type="submit" @click="submitInlineEdit" class="btn btn-success"><em class="fa-solid fa-check"></em></button>' +
        let submitButton = document.createElement('button');
        submitButton.dataset.index = this.options.index;
        submitButton.setAttribute('type', 'submit');
        submitButton.setAttribute('x-click', 'submitInlineEdit');
        submitButton.classList.add('btn');
        submitButton.classList.add('btn-success');

        // add icon to submit button
        let icon2 = document.createElement('em');
        icon2.classList.add('fa-solid');
        icon2.classList.add('fa-check');
        icon2.classList.add('text-white');
        submitButton.appendChild(icon2);


        // add to button group
        buttonGroup.appendChild(cancelButton);
        buttonGroup.appendChild(submitButton);

        // add button group to column
        column2.appendChild(buttonGroup);

        // add column to row
        row.appendChild(column2);

        this.element.parentElement.innerHTML = row.outerHTML;
    }

    textField() {
        return '<input data-index="' + this.options.index + 'input" autocomplete="off" type="text" class="form-control form-control-sm" id="input" name="name" value="' + this.options.value + '" placeholder="' + this.options.value + '" autofocus>';
    }

    closeDiv() {
        return '</div>';
    }

    closeForm() {
        return '</form>';
    }

    formStart() {
        return '<form class="form-inline">';
    }

    rowStart() {
        return '<div class="row">';
    }

    columnStart(param) {
        if ('' === param) {
            return '<div class="col">';
        }
        return '<div class="col-' + param + '">';
    }

    label() {
        return '<label class="sr-only" for="input">Field value</label>';
    }

    buttonGroup() {
        return '<div class="btn-group btn-group-sm" role="group" aria-label="Options">' +
            '<button data-index="' + this.options.index + '" type="button" @click="cancelInlineEdit" class="btn btn-danger"><em class="fa-solid fa-xmark text-white"></em></button>' +
            '<button data-index="' + this.options.index + '" type="submit" @click="submitInlineEdit" class="btn btn-success"><em class="fa-solid fa-check"></em></button>' +
            '</div>';
    }

    cancel() {
        console.log('GenericEditor[' + this.options.index + '].cancel()');
        console.log(this.element);
        console.log(this.parent);
        this.parent.innerHTML = this.options.original;
    }

    submitInlineEdit(e) {
        console.log('Submit?');
    }
}
