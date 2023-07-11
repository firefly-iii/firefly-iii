

// basic store for preferred date range and some other vars.
// used in layout.
class Basic {
    viewRange = '1M';
    darkMode = 'browser';
    listPageSize = 10;
    locale = 'en-US';
    range = {
        start: null, end: null
    };
    currencyCode = 'AAA';
    currencyId = '0';
    constructor() {
    }

    init() {
        console.log('init');
        // load variables from window if present
        this.loadVariable('viewRange')
    }

    loadVariable(name) {
        console.log('loadVariable(' +  name + ')');
        if(window.hasOwnProperty(name)) {
            console.log('from windows');
            this[name] = window[name];
            return;
        }
        // load from local storage
        if(window.Alpine.store(name)) {
            console.log('from alpine');
            this[name] = window.Alpine.store(name);
            return;
        }
        // grab using axios
        console.log('axios');
    }
}

export default Basic;
