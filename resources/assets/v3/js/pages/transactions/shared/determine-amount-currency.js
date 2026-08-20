export function determineAmountCurrency(code) {
    let list = [];
    let currency;
    for (let i in this.formData.enabledCurrencies) {
        if (this.formData.enabledCurrencies.hasOwnProperty(i)) {
            let current = this.formData.enabledCurrencies[i];
            if (current.code === code) {
                currency = current;
            }
        }
    }
    list.push(currency);
    if(1 === list.length) {
        this.formData.amountCurrency = list[0];
        // this also forces the currency_code on ALL entries.
        for (let i in this.entries) {
            if (this.entries.hasOwnProperty(i)) {
                this.entries[i].currency_code = code;
            }
        }
    }
};
