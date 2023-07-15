// basic store for preferred date range and some other vars.
// used in layout.
import Get from '../api/preferences/index.js';
import store from 'store2';

/**
 * A basic store for Firefly III persistent UI data and preferences.
 */
class Basic {

    // currently availabel variables:
    viewRange = '1M';
    darkMode = 'browser';
    language = 'en-US';
    locale = 'en-US';
    // start and end are used by most pages to allow the user to browse back and forth.
    start = null;
    end = null;

    // others, to be used in the future.
    listPageSize = 10;
    currencyCode = 'AAA';
    currencyId = '0';
    ready = false;

    // a very basic way to signal the store now contains all variables.
    count = 0;
    readyCount = 4;

    /**
     *
     */
    constructor() {
        console.log('Basic constructor')
    }

    /**
     *
     */
    init() {
        console.log('Basic init')
        this.loadVariable('viewRange')
        this.loadVariable('darkMode')
        this.loadVariable('language')
        this.loadVariable('locale')
    }

    /**
     * Load a variable, fresh or from storage.
     * @param name
     */
    loadVariable(name) {

        // currently unused, window.X can be used by the blade template
        // to make things available quicker than if the store has to grab it through the API.
        // then again, it's not that slow.
        if (window.hasOwnProperty(name)) {
            this[name] = window[name];
            this.triggerReady();
            return;
        }
        // load from store2
        if (store.has(name)) {
            this[name] = store.get(name);
            this.triggerReady();
            return;
        }
        // grab
        let getter = (new Get);
        getter.getByName(name).then((response) => this.parseResponse(name, response));
    }

    parseResponse(name, response) {
        let value = response.data.data.attributes.data;
        this[name] = value;
        store.set(name, value);
        this.triggerReady();
    }

    set(name, value) {
        this[name] = value;
        store.set(name, value);
    }

    get(name) {
        return store.get(name, this[name]);
    }

    isReady() {
        return this.count === this.readyCount;
    }

    triggerReady() {
        this.count++;
        if (this.count === this.readyCount) {
            console.log('Basic store is ready!')
            // trigger event:
            const event = new Event("BasicStoreReady");
            document.dispatchEvent(event);
        }
    }
}

export default Basic;
