// basic store for preferred date range and some other vars.
// used in layout.
import Get from '../api/preferences/index.js';

class Basic {
    viewRange = '1M';
    darkMode = 'browser';
    language = 'en-US';
    locale = 'en-US';

    // others, to be used in the future.
    listPageSize = 10;
    currencyCode = 'AAA';
    currencyId = '0';
    ready = false;
    count = 0;
    readyCount = 4;
    start = null;
    end = null;

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
            this.count++;
            if (this.count === this.readyCount) {
                // trigger event:
                const event = new Event("BasicStoreReady");
                document.dispatchEvent(event);
            }

            return;
        }
        // load from local storage
        if (localStorage.getItem(name)) {
            this[name] = localStorage.getItem(name);
            this.count++;
            if (this.count === this.readyCount) {
                // trigger event:
                const event = new Event("BasicStoreReady");
                document.dispatchEvent(event);
            }

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
        localStorage.setItem(name, value);
        if (this.count === this.readyCount) {
            // trigger event:
            const event = new Event("BasicStoreReady");
            document.dispatchEvent(event);
        }
    }

    store(name, value) {
        this[name] = value;
        localStorage.setItem(name, value);
    }

    getFromLocalStorage(name) {
        return localStorage.getItem(name);
    }

    isReady() {
        return this.count === this.readyCount;
    }
}

export default Basic;
