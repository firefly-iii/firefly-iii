// basic store for preferred date range and some other vars.
// used in layout.
import Get from '../api/preferences/index.js';
import store from 'store';


/**
 * A basic store for Firefly III persistent UI data and preferences.
 */
const Basic = () => {

    // currently availabel variables:
    const viewRange = '1M';
    const darkMode = 'browser';
    const language = 'en-US';
    const locale = 'en-US';

    // start and end are used by most pages to allow the user to browse back and forth.
    const start = null;
    const end = null;

    // others, to be used in the future.
    const listPageSize = 10;
    const currencyCode = 'AAA';
    const currencyId = '0';
    const ready = false;
    //
    // a very basic way to signal the store now contains all variables.
    const count = 0;
    const readyCount = 4;

    /**
     *
     */
    const init = () => {
        this.loadVariable('viewRange')
        this.loadVariable('darkMode')
        this.loadVariable('language')
        this.loadVariable('locale')
    }

    /**
     * Load a variable, fresh or from storage.
     * @param name
     */
    const loadVariable = (name) => {

        // currently unused, window.X can be used by the blade template
        // to make things available quicker than if the store has to grab it through the API.
        // then again, it's not that slow.
        if (window.hasOwnProperty(name)) {
            this[name] = window[name];
            this.triggerReady();
            return;
        }
        // load from store
        if (store.get(name)) {
            this[name] = store.get(name);
            this.triggerReady();
            return;
        }
        // grab
        let getter = (new Get);
        getter.getByName(name).then((response) => this.parseResponse(name, response));
    }
    //
    const parseResponse = (name, response) => {
        let value = response.data.data.attributes.data;
        this[name] = value;
        // TODO store.
        store.set(name, value);
        this.triggerReady();
    }
    //
    // set(name, value) {
    //     this[name] = value;
    //     store.set(name, value);
    // }
    //
    // get(name) {
    //     return store.get(name, this[name]);
    // }
    //
    const isReady = () => {
        return this.count === this.readyCount;
    }

    const triggerReady = () => {
        this.count++;
        if (this.count === this.readyCount) {
            // trigger event:
            const event = new Event("BasicStoreReady");
            document.dispatchEvent(event);
        }
    }
    return {
        init
    };
}
export const basic = Basic();
