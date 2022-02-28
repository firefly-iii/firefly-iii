import {api} from "boot/axios";

export default class AboutUser {
  get() {
    return api.get('/api/v1/about/user');
  }

  put(identifier, submission) {
    console.log('here we are');
    return api.put('/api/v1/users/' + identifier, submission);
  }

  logout() {
    return api.post('/logout');
  }
}
