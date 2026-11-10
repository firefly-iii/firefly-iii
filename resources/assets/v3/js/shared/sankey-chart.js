import i18next from "i18next";
import getCacheKey from "../store/get-cache-key.js";
import { format } from "date-fns";
import Get from "../api/model/transaction/get.js";
import { Chart, LinearScale } from "chart.js";
import { Flow, SankeyController } from "chartjs-chart-sankey";

export default class {
    start = null;
    end = null;
    anonymous = false;
    translations = {};
    holder = "";
    loading = false;
    transactions = [];
    currencies = [];
    convertToPrimary = false;
    processedData = {};
    chart = null;
    cacheKey = "ds_data_sankey";
    constructor(start, end, anonymous, holder) {
        this.start = start;
        this.end = end;
        this.anonymous = anonymous;
        this.holder = holder;
        this.convertToPrimary = window.store.get("convert_to_primary");

        // some translations:
        this.translations.all_money = i18next.t("firefly.all_money");
        this.translations.category = i18next.t("firefly.category");
        this.translations.in = i18next.t("firefly.money_flowing_in");
        this.translations.out = i18next.t("firefly.money_flowing_out");
        this.translations.unknown_category = i18next.t("firefly.unknown_category_plain");
        this.translations.unknown_source = i18next.t("firefly.unknown_source_plain");
        this.translations.unknown_dest = i18next.t("firefly.unknown_dest_plain");
        this.translations.unknown_account = i18next.t("firefly.unknown_any_plain");
        this.translations.unknown_budget = i18next.t("firefly.unknown_budget_plain");
        this.translations.expense_account = i18next.t("firefly.expense_account");
        this.translations.revenue_account = i18next.t("firefly.revenue_account");
        this.translations.budget = i18next.t("firefly.budget");
    }

    loadChart() {
        if (true === this.loading) {
            return;
        }
        this.loading = true;

        if (0 !== this.transactions.length) {
            this.drawChart(this.generateOptions());
            this.loading = false;
            return;
        }
        this.getFreshData();
    }
    getFreshData() {
        const cacheKey = getCacheKey(this.cacheKey, { start: this.start, end: this.end });

        const cacheValid = window.store.get("cacheValid");
        let cachedData = window.store.get(cacheKey);

        if (cacheValid && typeof cachedData !== "undefined") {
            this.transactions = cachedData;
            this.drawChart(this.generateOptions());
            this.loading = false;
            return;
        }

        let params = {
            start: format(this.start, "y-MM-dd"),
            end: format(this.end, "y-MM-dd"),
            type: "withdrawal,deposit",
            page: 1,
        };
        this.downloadTransactions(params);
    }

    downloadTransactions(params) {
        const cacheKey = getCacheKey(this.cacheKey, {
            convertToPrimary: this.convertToPrimary,
            start: this.start,
            end: this.end,
        });

        //console.log('Downloading page ' + params.page + '...');
        const getter = new Get();
        getter.list(params).then((response) => {
            this.transactions = [...this.transactions, ...response.data.data];
            //this.drawChart(this.generateOptions(response.data));
            //this.loading = false;
            if (parseInt(response.data.meta.pagination.total_pages) > params.page) {
                // continue to next page.
                params.page++;
                this.downloadTransactions(params);
                return;
            }
            console.log('Transactions', this.transactions);
            //window.store.set(cacheKey, this.transactions);
            this.drawChart(this.generateOptions());
            this.loading = false;
        });
    }

    generateOptions() {
        let options = {
            type: "sankey",
            data: {
                datasets: [],
            },
            options: { animations: false },
        };

        // reset currencies
        this.currencies = [];

        // variables collected for the sankey chart:
        this.parseTransactionGroups();

        let dataSet =
            // sankey chart has one data set.
            {
                label: "Firefly III dashboard sankey chart",
                data: [],
                colorFrom: (c) => this.getColor(c.dataset.data[c.dataIndex] ? c.dataset.data[c.dataIndex].from : ""),
                colorTo: (c) => this.getColor(c.dataset.data[c.dataIndex] ? c.dataset.data[c.dataIndex].to : ""),
                colorMode: "gradient", // or 'from' or 'to'
                labels: this.processedData.labels,
                size: "min", // or 'min' if flow overlap is preferred
            };
        for (let i in this.processedData.amounts) {
            if (Object.hasOwn(this.processedData.amounts, i)) {
                let amount = this.processedData.amounts[i];
                dataSet.data.push({ from: amount.from, to: amount.to, flow: amount.amount });
            }
        }
        options.data.datasets.push(dataSet);

        return options;
    }

    parseTransactionGroups() {
        this.processedData = {
            amounts: {},
            labels: {},
        };
        for (let i in this.transactions) {
            if (Object.hasOwn(this.transactions, i)) {
                let group = this.transactions[i];
                this.parseTransactionGroup(group);
            }
        }
        console.log('Processed data', this.processedData);
    }

    parseTransactionGroup(group) {
        for (let ii in group.attributes.transactions) {
            if (Object.hasOwn(group.attributes.transactions, ii)) {
                // properties of the transaction, used in the generation of the chart:
                let transaction = group.attributes.transactions[ii];
                this.parseTransaction(transaction);
            }
        }
    }
    parseTransaction(transaction) {
        let currencyCode = transaction.currency_code;
        let amount = parseFloat(transaction.amount);
        if (this.convertToPrimary) {
            currencyCode = transaction.primary_currency_code;
            amount = parseFloat(transaction.pc_amount);
        }
        if ("deposit" === transaction.type) {
            this.parseDeposit(transaction, currencyCode, amount);
            return;
        }

        if ("withdrawal" === transaction.type) {
            this.parseWithdrawal(transaction, currencyCode, amount);
        }
    }

    parseWithdrawal(transaction, currencyCode, amount) {
        /*
        Three entries in the sankey diagram for withdrawals:
        1. From the big box to a budget.
        2. From a budget to a category.
        3. From a category to an expense account.
        */

        // first one:
        let budget = this.getObjectName("budget", transaction.budget_name, "out", currencyCode);
        this.processedData.labels[budget] = this.getLabel("budget", transaction.budget_name, currencyCode);
        let flowKey = this.translations.all_money + "-" + budget + "-" + currencyCode;

        if (!Object.hasOwn(this.processedData.amounts, flowKey)) {
            this.processedData.amounts[flowKey] = {
                from: this.translations.all_money + (this.convertToPrimary ? " (" + currencyCode + ")" : ""),
                to: budget,
                amount: 0,
            };
        }
        this.processedData.amounts[flowKey].amount += amount;

        // second one:
        let category = this.getObjectName("category", transaction.category_name, "out", currencyCode);
        this.processedData.labels[category] = this.getLabel("category", transaction.category_name, currencyCode);
        flowKey = budget + "-" + category + "-" + currencyCode;

        if (!Object.hasOwn(this.processedData.amounts, flowKey)) {
            this.processedData.amounts[flowKey] = {
                from: budget,
                to: category,
                amount: 0,
            };
        }
        this.processedData.amounts[flowKey].amount += amount;

        // third one:
        let expenseAccount = this.getObjectName("account", transaction.destination_name, "out", currencyCode);
        this.processedData.labels[expenseAccount] = this.getLabel(
            "account",
            transaction.destination_name,
            currencyCode,
        );
        flowKey = category + "-" + expenseAccount + "-" + currencyCode;

        if (!Object.hasOwn(this.processedData.amounts, flowKey)) {
            this.processedData.amounts[flowKey] = {
                from: category,
                to: expenseAccount,
                amount: 0,
            };
        }
        this.processedData.amounts[flowKey].amount += amount;
    }

    parseDeposit(transaction, currencyCode, amount) {
        /*
        Two entries in the sankey diagram for deposits:
        1. From the revenue account (source) to a category (in).
        2. From the category (in) to the big inbox.
        */

        // this is the first one:
        let category = this.getObjectName("category", transaction.category_name, "in", currencyCode);
        let revenueAccount = this.getObjectName("account", transaction.source_name, "in", currencyCode);
        let flowKey = revenueAccount + "-" + category + "-" + currencyCode;
        this.processedData.labels[category] = this.getLabel("category", transaction.category_name, currencyCode);
        this.processedData.labels[revenueAccount] = this.getLabel("account", transaction.source_name, currencyCode);

        // create if necessary:
        if (!Object.hasOwn(this.processedData.amounts, flowKey)) {
            this.processedData.amounts[flowKey] = {
                from: revenueAccount,
                to: category,
                amount: 0,
            };
        }
        this.processedData.amounts[flowKey].amount += amount;

        // this is the second one:
        flowKey = category + "-" + this.translations.all_money + "-" + currencyCode;
        if (!Object.hasOwn(this.processedData.amounts, flowKey)) {
            this.processedData.amounts[flowKey] = {
                from: category,
                to: this.translations.all_money + (this.convertToPrimary ? " (" + currencyCode + ")" : ""),
                amount: 0,
            };
        }
        this.processedData.amounts[flowKey].amount += amount;
    }

    getObjectName(type, name, direction, code) {
        if (this.convertToPrimary) {
            return this.getObjectNameWithoutCurrency(type, name, direction);
        }
        return this.getObjectNameWithCurrency(type, name, direction, code);
    }

    getLabel(type, name, code) {
        if (this.convertToPrimary) {
            return this.getLabelWithoutCurrency(type, name);
        }
        return this.getLabelWithCurrency(type, name, code);
    }

    getLabelWithoutCurrency(type, name) {
        if ("category" === type) {
            return null === name ? this.translations.unknown_category : this.translations.category + ' "' + name + '"';
        }
        if ("account" === type) {
            return null === name ? this.translations.unknown_account : name;
        }
        if ("budget" === type) {
            return null === name ? this.translations.unknown_budget : this.translations.budget + ' "' + name + '"';
        }
        console.error('[a] Cannot handle: type:"' + type + '"');
    }
    getLabelWithCurrency(type, name, code) {
        if ("category" === type) {
            return (
                (null === name ? this.translations.unknown_category : this.translations.category + ' "' + name + '"') +
                " (" +
                code +
                ")"
            );
        }
        if ("account" === type) {
            return (null === name ? this.translations.unknown_account : name) + " (" + code + ")";
        }
        if ("budget" === type) {
            return (
                (null === name ? this.translations.unknown_budget : this.translations.budget + ' "' + name + '"') +
                " (" +
                code +
                ")"
            );
        }
        console.error('[b] Cannot handle: type:"' + type + '"');
    }

    getObjectNameWithoutCurrency(type, name, direction) {
        if ("category" === type) {
            let catName =
                null === name ? this.translations.unknown_category : this.translations.category + ' "' + name + '"';
            let directionText = "in" === direction ? this.translations.in : this.translations.out;
            return catName + " (" + directionText + ")";
        }
        if ("account" === type) {
            let accountName = null === name ? this.translations.unknown_account : name;
            let directionText = "in" === direction ? this.translations.in : this.translations.out;
            let fullAccountName =
                "in" === direction
                    ? this.translations.revenue_account + ' "' + accountName + '"'
                    : this.translations.expense_account + ' "' + accountName + '"';
            return fullAccountName + " (" + directionText + ")";
        }
        if ("budget" === type) {
            return null === name ? this.translations.unknown_budget : this.translations.budget + ' "' + name + '"';
        }
        console.error('[a] Cannot handle: type:"' + type + '", dir: "' + direction + '"');
    }

    getObjectNameWithCurrency(type, name, direction, code) {
        if ("category" === type) {
            let catName =
                null === name ? this.translations.unknown_category : this.translations.category + ' "' + name + '"';
            let directionText = "in" === direction ? this.translations.in : this.translations.out;
            return catName + " (" + directionText + ", " + code + ")";
        }
        if ("account" === type) {
            let accountName = null === name ? this.translations.unknown_account : name;
            let directionText = "in" === direction ? this.translations.in : this.translations.out;
            let fullAccountName =
                "in" === direction
                    ? this.translations.revenue_account + ' "' + accountName + '"'
                    : this.translations.expense_account + ' "' + accountName + '"';
            return fullAccountName + " (" + directionText + ", " + code + ")";
        }
        if ("budget" === type) {
            return (
                (null === name ? this.translations.unknown_budget : this.translations.budget + ' "' + name + '"') +
                " (" +
                code +
                ")"
            );
        }
        console.error('[b] Cannot handle: type:"' + type + '", dir: "' + direction + '"');
    }

    getColor(key) {
        if (key.includes(this.translations.revenue_account)) {
            return "forestgreen";
        }
        if (key.includes("(" + this.translations.in + ",")) {
            return "green";
        }

        if (key.includes(this.translations.budget) || key.includes(this.translations.unknown_budget)) {
            return "Orchid";
        }
        if (key.includes("(" + this.translations.out + ",")) {
            return "MediumOrchid";
        }

        if (key.includes(this.translations.all_money)) {
            return "blue";
        }
        return "red";
    }
    drawChart(options) {
        if (null !== this.chart) {
            this.chart.data.datasets = options.data.datasets;
            this.chart.update();
            return;
        }
        console.log(options);
        Chart.register(LinearScale, SankeyController, Flow);
        this.chart = new Chart(document.getElementById(this.holder).getContext("2d"), options);
        }
}
