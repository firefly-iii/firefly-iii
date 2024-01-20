import BaseType from "./BaseType.js";

export default class InputType extends BaseType{
    create(){
        // expand input element with necessary classes and things.
        // <input type="text" class="form-control form-control-md" id="inlineFormInputGroupUsername" placeholder="Username">


        const input = this.createElement(`input`);
        const id = this.context.element.id + '_input';
        input.type = this.context.type;
        input.id = id;
        input.autocomplete = 'off';
        input.placeholder = this.context.element.innerText;
        input.classList.add("form-control", "form-control-md");

        return this.createContainer(input);
    }
}
