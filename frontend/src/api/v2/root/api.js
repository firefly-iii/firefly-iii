/*
 * api.js
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

import {api} from "boot/axios";

/**
 *
 */
export default class Api {
  root = '/api/v2/';
  path = '';

  constructor(path) {
    this.path = path;
  }

  apiPath() {
    return this.root + this.path;
  }

  apiPathWithObject(object) {
    return this.root + this.path + '/' + object;
  }

  /**
   *
   * @param object
   * @param params
   * @returns {Promise<AxiosResponse<any>>}
   */
  apiGet(object, params) {
    let url = this.apiPathWithObject(object);
    if (params) {
      return api.get(url, {params: params});
    }
    return api.get(url);
  }

  /**
   *
   * @param object
   * @param params
   * @returns {Promise<AxiosResponse<any>>}
   */
  apiGetTransactions(object, params) {
    let url = this.apiPathWithObject(object) + '/transactions';
    if (params) {
      return api.get(url, {params: params});
    }
    return api.get(url);
  }
}
