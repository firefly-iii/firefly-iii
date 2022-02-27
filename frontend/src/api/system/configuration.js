
import {api} from "boot/axios";

export default class Configuration {
  get (identifier) {
    return api.get('/api/v1/configuration/' + identifier);
  }

  put (identifier, value) {
    return api.put('/api/v1/configuration/' + identifier, value);
  }
}
