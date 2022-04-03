/*
 * axios.js
 * Copyright (c) 2022 james@firefly-iii.org
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

import {boot} from 'quasar/wrappers'
import axios from 'axios'
import {setupCache} from 'axios-cache-adapter'

const cache = setupCache({
                           maxAge: 15 * 60 * 1000,
                           exclude: { query: false }
                         })

// Be careful when using SSR for cross-request state pollution
// due to creating a Singleton instance here;
// If any client changes this (global) instance, it might be a
// good idea to move this instance creation inside of the
// "export default () => {}" function below (which runs individually
// for each client)

const url = process.env.DEBUGGING ? 'https://firefly.sd.home' : '/';
const api = axios.create({baseURL: url, withCredentials: true, adapter: cache.adapter});

export default boot(({app}) => {
  // for use inside Vue files (Options API) through this.$axios and this.$api
  axios.defaults.withCredentials = true;
  axios.defaults.baseURL = url;

  app.config.globalProperties.$axios = axios
  // ^ ^ ^ this will allow you to use this.$axios (for Vue Options API form)
  //       so you won't necessarily have to import axios in each vue file

  app.config.globalProperties.$api = api
  // ^ ^ ^ this will allow you to use this.$api (for Vue Options API form)
  //       so you can easily perform requests against your app's API
})

export {api}
