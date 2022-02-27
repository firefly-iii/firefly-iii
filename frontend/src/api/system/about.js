import {api} from "boot/axios";
//import createAuthRefreshInterceptor from 'axios-auth-refresh';

export default class About {
    list() {
        return api.get('/api/v1/about');
    }
}
