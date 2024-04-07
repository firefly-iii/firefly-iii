/*
 * dark-editable.js
 * Copyright (c) 2024 https://github.com/DarKsandr/dark-editable
 *
 * License: MIT
 *
 * Copied and slightly edited by James Cole <james@firefly-iii.org>
 */

import "./dark-editable.css";
import PopupMode from "./Modes/PopupMode.js";
import InlineMode from "./Modes/InlineMode.js";
import BaseType from "./Types/BaseType.js";
import InputType from "./Types/InputType.js";
import TextAreaType from "./Types/TextAreaType.js";
import SelectType from "./Types/SelectType.js";
import DateType from "./Types/DateType.js";
import DateTimeType from "./Types/DateTimeType.js";

export default class DarkEditable{
    modeElement = null;
    typeElement = null;
    mode = null;
    type = null;
    emptytext = null;
    viewformat = null;
    pk = null;
    name = null;

    constructor(element, options = {}){
        this.element = element;
        this.options = options;

        this.init_options();
        this.typeElement = this.route_type();
        this.typeElement.initOptions();
        this.modeElement = this.route_mode();
        this.modeElement.init();
        this.init_text();
        this.init_style();
        if(this.disabled){
            this.disable();
        }
        this.element.dispatchEvent(new CustomEvent("init"));
    }

    /* INIT METHODS */

    get_opt(name, default_value){
        return this[ name ] = this.element.dataset?.[ name ] ?? this.options?.[ name ] ?? default_value;
    }
    get_opt_bool(name, default_value){
        this.get_opt(name, default_value);
        if(typeof this[ name ] !== "boolean"){
            if(this[ name ] === "true") {
                this[ name ] = true;
            } else if(this[ name ] === "false") {
                this[ name ] = false;
            } else {
                this[ name ] = default_value;
            }
        }
        return this[ name ];
    }

    init_options(){
        //priority date elements
        this.get_opt("value", this.element.innerHTML);
        this.get_opt("name", this.element.id);
        this.get_opt("pk", null);
        this.get_opt("title", "");
        this.get_opt("type", "text");
        this.get_opt("emptytext", "Empty");
        this.get_opt("mode", "popup");
        this.get_opt("url", null);
        this.get_opt("ajaxOptions", {});
        this.ajaxOptions = Object.assign({
            method: "POST",
            dataType: "text",
        }, this.ajaxOptions);
        this.get_opt_bool("send", true);
        this.get_opt_bool("disabled", false);
        this.get_opt_bool("required", false);
        if(this.options?.success && typeof this.options?.success == "function"){
            this.success = this.options.success;
        }
        if(this.options?.error && typeof this.options?.error == "function"){
            this.error = this.options.error;
        }
    }

    init_text(){
        const empty_class = "dark-editable-element-empty";
        this.element.classList.remove(empty_class);
        if(this.typeElement.initText()){
            this.element.classList.add(empty_class);
        }
    }

    init_style(){
        this.element.classList.add("dark-editable-element");
    }

    /* INIT METHODS END */
    route_mode(){
        switch (this.mode){
            default:
                throw new Error(`Mode ${this.mode} not found!`)
            case 'popup':
                return new PopupMode(this);
            case 'inline':
                return new InlineMode(this);
        }
    }

    route_type(){
        if(this.type.prototype instanceof BaseType){
            return new this.type(this);
        }
        if(typeof this.type === 'string'){
            switch(this.type){
                case "text":
                case "password":
                case "email":
                case "url":
                case "tel":
                case "number":
                case "range":
                case "time":
                    return new InputType(this);
                case "textarea":
                    return new TextAreaType(this);
                case "select":
                    return new SelectType(this);
                case "date":
                    return new DateType(this);
                case "datetime":
                    return new DateTimeType(this);
            }
        }
        throw new Error(`Undefined type`);
    }

    /* AJAX */

    async success(response, newValue){
        return await this.typeElement.successResponse(response, newValue);
    }

    async error(response, newValue){
        return await this.typeElement.errorResponse(response, newValue);
    }

    /* AJAX END */

    /* METHODS */

    enable(){
        this.disabled = false;
        this.element.classList.remove("dark-editable-element-disabled");
        this.modeElement.enable();

    }

    disable(){
        this.disabled = true;
        this.element.classList.add("dark-editable-element-disabled");
        this.modeElement.enable();
    }

    setValue(value){
        this.value = value;
        this.init_text();
    }

    getValue(){
        return this.value;
    }

    /* METHODS END */
}
