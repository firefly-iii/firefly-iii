import BaseType from "./BaseType.js";

export default class SelectType extends BaseType{
    create(){
        const select = this.createElement(`select`);
        this.context.source.forEach(item => {
            const opt = document.createElement(`option`);
            opt.value = item.value;
            opt.innerHTML = item.text;
            select.append(opt);
        });

        return this.createContainer(select);
    }

    initText(){
        this.context.element.innerHTML = this.context.emptytext;
        if(this.context.value !== "" && this.context.source.length > 0){
            for(const key in this.context.source){
                const item = this.context.source[ key ];
                if(item.value == this.context.value){
                    this.context.element.innerHTML = item.text;
                    return false;
                }
            }
        }
        return true;
    }

    initOptions(){
        this.context.get_opt("source", []);
        if(typeof this.context.source === "string" && this.context.source !== ""){
            this.context.source = JSON.parse(this.context.source);
        }
    }
}