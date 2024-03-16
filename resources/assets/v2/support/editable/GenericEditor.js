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
        console.log('GenericEditor['+this.options.index+'].init()');
    }

    replace() {
        console.log('GenericEditor['+this.options.index+'].replace()');
        // save old HTML in data field (does that work, is it safe?)
        this.options.original = this.element.parentElement.innerHTML;
        if (this.options.type === 'text') {
            this.replaceText();
        }
    }

    replaceText() {
        console.log('GenericEditor['+this.options.index+'].replaceText()');
        let html = this.formStart() + this.rowStart();

        // input field:
        html += this.columnStart('7') + this.label() + this.textField() + this.closeDiv();

        // add submit button
        html += this.columnStart('5') + this.buttonGroup() + this.closeDiv();

        // close column and form:
        html += this.closeDiv() + this.closeForm();
        this.element.parentElement.innerHTML = html;
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
        return '<div class="btn-group btn-group-sm" role="group" aria-label="Options">'+
            '<button data-index="'+this.options.index+'" type="button" @click="cancelInlineEdit" class="btn btn-danger"><em class="fa-solid fa-xmark text-white"></em></button>'+
            '<button data-index="'+this.options.index+'" type="submit" @click="submitInlineEdit" class="btn btn-success"><em class="fa-solid fa-check"></em></button>' +
            '</div>';
    }
    cancel() {
        console.log('GenericEditor['+this.options.index+'].cancel()');
        console.log(this.element);
        console.log(this.parent);
        this.parent.innerHTML = this.options.original;
    }
    submitInlineEdit(e) {
        console.log('Submit?');
    }
}
