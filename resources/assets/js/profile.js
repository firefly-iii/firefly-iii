/*
 * profile.js
 * Copyright (c) 2019 james@firefly-iii.org
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

import Clients from './components/passport/Clients';
import AuthorizedClients from "./components/passport/AuthorizedClients";
import PersonalAccessTokens from "./components/passport/PersonalAccessTokens";
import ProfileOptions from "./components/profile/ProfileOptions";

/**
 * First we will load Axios via bootstrap.js
 * jquery and bootstrap-sass preloaded in app.js
 * vue, uiv and vuei18n are in app_vue.js
 */

require('./bootstrap');

Vue.component('passport-clients', Clients);
Vue.component('passport-authorized-clients',AuthorizedClients);
Vue.component('passport-personal-access-tokens', PersonalAccessTokens);

Vue.component('profile-options', ProfileOptions);

let props = {};
new Vue({
            el: "#passport_clients",
            render: (createElement) => {
                return createElement(ProfileOptions, { props: props })
            },
        });