/*
 * BaseMode.js
 * Copyright (c) 2024 https://github.com/DarKsandr/dark-editable
 *
 * License: MIT
 *
 * Copied and slightly edited by James Cole <james@firefly-iii.org>
 */

export default class BaseType {
    context = null;
    element = null;
    error = null;
    form = null;
    load = null;
    buttonGroup = null;
    buttons = {success: null, cancel: null};

    constructor(context) {
        if (this.constructor === BaseType) {
            throw new Error(`It's abstract class`);
        }
        this.context = context;
    }

    create() {
        throw new Error('Method `create` not define!');
    }

    createContainer(element) {
        const div = document.createElement(`div`);

        // original list of elements:
        this.element = element;
        this.error = this.createContainerError();
        this.form = this.createContainerForm();
        this.load = this.createContainerLoad();
        this.buttons.success = this.createButtonSuccess();
        this.buttons.cancel = this.createButtonCancel();

        // create first div, with label and input:
        const topDiv = document.createElement(`div`);
        topDiv.classList.add("col-12");

        // create label:
        const label = document.createElement(`label`);
        label.classList.add("visually-hidden");
        label.for = element.id;

        // add label + input to top div:
        topDiv.append(label, element);

        // create second div, with button group:
        const bottomDiv = document.createElement(`div`);
        bottomDiv.classList.add("col-12");

        // create button group:
        this.buttonGroup = this.createButtonGroup();

        // append buttons to button group:
        this.buttonGroup.append(this.buttons.success, this.buttons.cancel);
        bottomDiv.append(this.buttonGroup);

        // append bottom and top div to form:
        this.form.append(topDiv, bottomDiv);

        //this.form.append(element, this.load, this.buttons.success, this.buttons.cancel);
        //this.form.append(element, this.load, this.buttonGroup);
        div.append(this.error, this.form);
        return div;
    }

    createButtonGroup() {
        const div = document.createElement(`div`);
        div.classList.add("btn-group", "btn-group-sm");
        return div;
    }

    createContainerError() {
        const div = document.createElement(`div`);
        div.classList.add("text-danger", "fst-italic", "mb-2", "fw-bold");
        div.style.display = "none";
        return div;
    }

    createContainerForm() {


        const form = document.createElement(`form`);
        form.classList.add("row", "row-cols-lg-auto", "g-3", "align-items-center");
        //form.style.gap = "20px";
        form.addEventListener('submit', async e => {
            e.preventDefault();
            const newValue = this.getValue();
            if (this.context.send && this.context.pk && this.context.url && (this.context.value !== newValue)) {
                this.showLoad();
                let msg;
                try {
                    const response = await this.ajax(newValue);
                    if (response.ok) {
                        msg = await this.context.success(response, newValue);
                    } else {
                        msg = await this.context.error(response, newValue) || `${response.status} ${response.statusText}`;
                    }
                } catch (error) {
                    console.error(error);
                    msg = error;
                }

                if (msg) {
                    this.setError(msg);
                    this.showError();
                } else {
                    this.setError(null);
                    this.hideError();
                    this.context.value = this.getValue();
                    this.context.modeElement.hide();
                    this.initText();
                }
                this.hideLoad();
            } else {
                this.context.value = this.getValue();
                this.context.modeElement.hide();
                this.initText();
            }
            this.context.element.dispatchEvent(new CustomEvent("save"));
        })
        return form;
    }

    createContainerLoad() {
        const div = document.createElement(`div`);
        div.style.display = "none";
        div.style.position = "absolute";
        div.style.background = "white";
        div.style.width = "100%";
        div.style.height = "100%";
        div.style.top = 0;
        div.style.left = 0;
        const loader = document.createElement(`div`);
        loader.classList.add("dark-editable-loader");
        div.append(loader);
        return div;
    }

    createButton() {
        const button = document.createElement("button");
        button.type = "button";
        button.classList.add("btn", "btn-sm");
        button.style.color = "transparent";
        button.style.textShadow = "0 0 0 white";
        return button;
    }

    createButtonSuccess() {
        const btn_success = this.createButton();
        btn_success.type = "submit";
        btn_success.classList.add("btn-success");
        btn_success.innerHTML = "✔";
        return btn_success;
    }

    createButtonCancel() {
        const btn_cancel = this.createButton();
        btn_cancel.classList.add("btn-danger");
        const div = document.createElement("div");
        div.innerHTML = "✖";
        btn_cancel.append(div);
        btn_cancel.addEventListener("click", () => {
            this.context.modeElement.hide();
        });
        return btn_cancel;
    }

    hideLoad() {
        this.load.style.display = "none";
    }

    showLoad() {
        this.load.style.display = "block";
    }

    ajax(new_value) {
        let url = this.context.url;
        //const form = new FormData;
        let message;
        let submit = false;

        console.log(this.context);
        // replace form with custom sets. Not sure yet of the format, this will have to grow in time.
        if ('journal_description' === this.context.options.formType) {
            submit = true;
            message = {
                transactions: [
                    {
                        transaction_journal_id: this.context.options.journalId,
                        description: new_value,
                    }
                ]
            };
        }

        if(false === submit) {
            console.error('Cannot deal with form type "'+this.context.formType+'"');
        }

        // form.append("pk", this.context.pk);

        // form.append("name", this.context.name);
        // form.append("value", new_value);

        const option = {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
            }
        };
        option.method = this.context.ajaxOptions.method;

        if(this.context.options.method) {
            option.method = this.context.options.method;
        }
        if ('POST' === option.method || 'PUT' === this.context.options.method) {
            option.body = JSON.stringify(message);
        } else {
            url += "?" + new URLSearchParams(form).toString();
        }
        return fetch(url, option);
    }

    async successResponse(response, newValue) {

    }

    async errorResponse(response, newValue) {

    }

    setError(errorMsg) {
        this.error.innerHTML = errorMsg;
    }

    showError() {
        this.error.style.display = "block";
    }

    hideError() {
        if (this.error) {
            this.error.style.display = "none";
        }
    }

    createElement(name) {
        const element = document.createElement(name);
        console.log(element);
        element.classList.add("form-control");
        if (this.context.required) {
            element.required = this.context.required;
        }
        this.add_focus(element);
        return element;
    }

    add_focus(element) {
        this.context.element.addEventListener('shown', function () {
            element.focus();
        });
    }

    initText() {
        if (this.context.value === "") {
            this.context.element.innerHTML = this.context.emptytext;
            return true;
        } else {
            this.context.element.innerHTML = this.context.value;
            return false;
        }
    }

    initOptions() {

    }

    getValue() {
        return this.element.value;
    }
}
