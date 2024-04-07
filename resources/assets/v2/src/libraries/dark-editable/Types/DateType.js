import BaseType from "./BaseType.js";

export default class DateType extends BaseType{
    create(){
        const input = this.createElement(`input`);
        input.type = "date";

        return this.createContainer(input);
    }

    initText(){
        if(this.value === ""){
            this.context.element.innerHTML = this.context.emptytext;
            return true;
        } else {
            this.context.element.innerHTML = moment(this.context.value).format(this.context.viewformat);
            return false;
        }
    }

    initOptions(){
        this.context.get_opt("format", "YYYY-MM-DD");
        this.context.get_opt("viewformat", "YYYY-MM-DD");
    }
}