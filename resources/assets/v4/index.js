import Summary from "./api/summary/index.js";
import {format} from 'date-fns'
import Alpine from "alpinejs";

//let amounts = [];

class IndexApp {

    balanceBox = {foo: 'bar'};

    constructor() {
        console.log('IndexApp constructor');
    }

    init() {
        console.log('IndexApp init');
        this.loadBoxes();
    }

    loadBoxes() {
        console.log('IndexApp loadBoxes');
        let getter = new Summary();
        let start = window.BasicStore.get('start');
        let end = window.BasicStore.get('end');

        // check on NULL values:
        if (start !== null && end !== null) {
            start = new Date(start);
            end = new Date(end);
        }

        getter.get(format(start, 'yyyy-MM-dd'), format(end, 'yyyy-MM-dd'), null).then((response) => {
            //
            console.log('IndexApp done!');
            console.log(response.data);
            document.querySelector('#balanceAmount').innerText = 'ok dan';
            //window.$refs.balanceAmount.text = 'bar!';
            for (const i in response.data) {
                if (response.data.hasOwnProperty(i)) {
                    const current = response.data[i];
                    if (i.startsWith('balance-in-')) {
                        //amounts.push(current);
                        console.log('Balance in: ', current);
                    }
                }
            }
        });

    }
}

let index = new IndexApp();

document.addEventListener("AppReady", (e) => {
    index.init();
}, false,);

if (window.BasicStore.isReady()) {
    index.init();
}
document.addEventListener('alpine:init', () => {
    Alpine.data('balanceBox', () => ({
        foo: 'barX'
    }))
})

export function amounts() {
    return {
        amounts: ['bar', 'boo', 'baz'],
        add() {
            this.amounts.push('foo');
        },
        get() {
            return this.amounts[1];
        }
    }
}

window.Alpine = Alpine
Alpine.start()
