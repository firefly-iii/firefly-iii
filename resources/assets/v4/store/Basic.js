// basic store for preferred date range and some other vars.
// used in layout.
import Get from '../api/preferences/index.js';

class Basic {
    viewRange = '1M';
    darkMode = 'browser';
    listPageSize = 10;
    locale = 'en-US';
    language = 'en-US';
    currencyCode = 'AAA';
    currencyId = '0';
    ready = false;
    count = 0;
    readyCount = 4;

    constructor() {
    }

    init() {
        this.loadVariable('viewRange')
        this.loadVariable('darkMode')
        this.loadVariable('language')
        this.loadVariable('locale')
    }

    loadVariable(name) {
        if (window.hasOwnProperty(name)) {
            this[name] = window[name];
            return;
        }
        // load from local storage
        if (window.Alpine.store(name)) {
            this[name] = window.Alpine.store(name);
            return;
        }
        // grab
        let getter = (new Get);
        getter.getByName(name).then((response) => this.parseResponse(name, response));
    }

    parseResponse(name, response) {
        this.count++;
        let value = response.data.data.attributes.data;
        this[name] = value;
        if (this.count === this.readyCount) {
            // trigger event:
            const event = new Event("BasicStoreReady");
            document.dispatchEvent(event);
        }
    }
}

export default Basic;
