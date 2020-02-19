/*
 * app_vue.js
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

/* Creates a base file for vue apps
 * Bootstrat-sass and jquery are loaded via app.js
*/
import Vue from 'vue';
import VueI18n from 'vue-i18n'
import * as uiv from 'uiv';

window.vuei18n = VueI18n;
window.uiv =uiv;
Vue.use(vuei18n);
Vue.use(uiv);
window.Vue = Vue;
