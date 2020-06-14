import Dashboard from "../components/dashboard/Dashboard";
import TopBoxes from "../components/dashboard/TopBoxes";

/**
 * First we will load Axios via bootstrap.js
 * jquery and bootstrap-sass preloaded in app.js
 * vue, uiv and vuei18n are in app_vue.js
 */

require('../bootstrap');

// components as an example
Vue.component('dashboard', Dashboard);
Vue.component('top-boxes', TopBoxes);

// i18n
let i18n = require('../i18n');

let props = {};
new Vue({
            i18n,
            el: "#dashboard",
            render: (createElement) => {
                return createElement(Dashboard, { props: props });
            },
        });
