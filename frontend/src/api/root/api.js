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
