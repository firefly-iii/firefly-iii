/*
 * BaseMode.js
 * Copyright (c) 2024 https://github.com/DarKsandr/dark-editable
 *
 * License: MIT
 *
 * Copied and slightly edited by James Cole <james@firefly-iii.org>
 */
export default class BaseMode{
    constructor(context) {
        if(this.constructor === BaseMode){
            throw new Error(`It's abstract class`);
        }
        this.context = context;
    }
    event_show(){
        this.context.typeElement.hideError();
        this.context.typeElement.element.value = this.context.value;
        this.context.element.dispatchEvent(new CustomEvent("show"));
    }
    event_shown(){
        this.context.element.dispatchEvent(new CustomEvent("shown"));
    }
    event_hide(){
        this.context.element.dispatchEvent(new CustomEvent("hide"));
    }
    event_hidden(){
        this.context.element.dispatchEvent(new CustomEvent("hidden"));
    }
    init(){
        throw new Error('Method `init` not define!');
    }
    enable(){
        throw new Error('Method `enable` not define!');
    }
    disable(){
        throw new Error('Method `disable` not define!');
    }
    hide(){
        throw new Error('Method `hide` not define!');
    }
}
