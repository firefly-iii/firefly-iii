/*
 * index.js
 * Copyright (c) 2020 james@firefly-iii.org
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

require('../../bootstrap');

import VueRouter from 'vue-router';
import Index from "../../components/accounts/Index";

const routes = [
    {path: '/', component: Index},
    // maybe "create" and "edit" in component.
]

// 3. Create the router instance and pass the `routes` option
// You can pass in additional options here, but let's
// keep it simple for now.
const router = new VueRouter({
                                 mode: 'history',
                                 routes // short for `routes: routes`
                             })

// i18n
let i18n = require('../../i18n');

let props = {};
// new Vue({router,
//             i18n,
//             el: "#accounts",
//             render: (createElement) => {
//                 return createElement(List, { props: props });
//             },
//         });
Vue.use(VueRouter);          // <== very important
new Vue({
            router,
            i18n,
            render(createElement) {
                return createElement(Index, {props: props});
            }
        }).$mount('#accounts');
