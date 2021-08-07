require('../../bootstrap');

import Edit from "../../components/accounts/Edit";

// i18n
let i18n = require('../../i18n');

let props = {};
const app = new Vue({
            i18n,
            render(createElement) {
                return createElement(Edit, {props: props});
            }
        }).$mount('#accounts_edit');
