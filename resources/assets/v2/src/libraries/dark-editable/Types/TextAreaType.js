import BaseType from "./BaseType.js";

export default class TextAreaType extends BaseType{
    create(){
        const textarea = this.createElement(`textarea`);

        return this.createContainer(textarea);
    }
}