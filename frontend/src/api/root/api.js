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
  root = '/api/v1/';
  path = '';

  constructor(path) {
    this.path = path;
  }

  apiPath() {
    return this.root + this.path;
  }

  apiPathId(identifier) {
    return this.root + this.path + '/' + identifier;
  }

  /**
   *
   * @param identifier
   * @param params
   * @returns {Promise<AxiosResponse<any>>}
   */
  apiGet(identifier, params) {
    let url = this.apiPathId(identifier);
    if (params) {
      return api.get(url, {params: params});
    }
    return api.get(url);
  }

  /**
   * This is a generic method that works for all DELETE operations.
   *
   * @param identifier
   * @returns {Promise<AxiosResponse<any>>}
   */
  destroy(identifier) {
    let url = this.apiPathId(identifier);
    return api.delete(url);
  }

  apiPathChildren(identifier, type) {
    return this.apiPathId(identifier) + '/' + type;
  }

  apiGetChildren(type, identifier, page) {
    let url = this.apiPathChildren(identifier, type);
    let cacheKey = 'still-todo';
    // needs a cache key. Based on type.
    return api.get(url, {params: {page: page, cache: cacheKey}});
  }


  /**
   *
   * @param page
   * @param params
   * @returns {Promise<AxiosResponse<any>>}
   */
  apiList(page, params) {
    let type = 'transactions';
    let identifier = '1';

    let cacheKey = 'still-todo';
    let url = this.apiPathChildren(identifier, type);

    // needs a cache key. Based on type.
    return api.get(url, {params: {page: page, cache: cacheKey}});


    // let identifier = 'abc';
    // // test:
    // let type= 'expense';

    // let type ='accounts';
    //
    // this.store.getters["fireflyiii/getScopedCacheKey"](type);
    // let cacheKey = 'def';
    // let url = this.apiPath();
    //
    // // needs a cache key. Based on type.
    // return api.get(url, {params: {page: page, cache: cacheKey}});

    //
    //
    // console.log('apiList');
    // let cacheKey;
    //
    // //let $q = useQuasar();
    // //const store = useStore();
    // cacheKey = 'OK';
    // console.log('path: ' + this.path);
    // //cacheKey = $store.getters["fireflyiii/getScopedCacheKey"](this.path);
    // //store.getters["fireflyiii/getScopedCacheKey"](this.path)
    // let cache = {
    //   cache: cacheKey
    // };
    // let merged = {...params, ...cache};
    // console.log(merged);
    // let url = this.apiPath();
    // console.log(url);
    // return api.get(url, {params: merged});
  }

}
