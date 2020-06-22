import Dashboard from "../components/dashboard/Dashboard";
import TopBoxes from "../components/dashboard/TopBoxes";
import MainAccount from "../components/dashboard/MainAccount";
import MainAccountList from "../components/dashboard/MainAccountList";
import MainBillsChart from "../components/dashboard/MainBillsChart";
import MainBudgetChart from "../components/dashboard/MainBudgetChart";
import MainCategoryChart from "../components/dashboard/MainCategoryChart";
import MainCrebitChart from "../components/dashboard/MainCrebitChart";
import MainDebitChart from "../components/dashboard/MainDebitChart";
import MainPiggyList from "../components/dashboard/MainPiggyList";
import TransactionListLarge from "../components/transactions/TransactionListLarge";
import TransactionListMedium from "../components/transactions/TransactionListMedium";
import TransactionListSmall from "../components/transactions/TransactionListSmall";
/**
 * First we will load Axios via bootstrap.js
 * jquery and bootstrap-sass preloaded in app.js
 * vue, uiv and vuei18n are in app_vue.js
 */

require('../bootstrap');

Vue.component('transaction-list-large', TransactionListLarge);
Vue.component('transaction-list-medium', TransactionListMedium);
Vue.component('transaction-list-small', TransactionListSmall);

// components as an example
Vue.component('dashboard', Dashboard);
Vue.component('top-boxes', TopBoxes);
Vue.component('main-account', MainAccount);
Vue.component('main-account-list', MainAccountList);
Vue.component('main-bills-chart', MainBillsChart);
Vue.component('main-budget-chart', MainBudgetChart);
Vue.component('main-category-chart', MainCategoryChart);
Vue.component('main-credit-chart', MainCrebitChart);
Vue.component('main-debit-chart', MainDebitChart);
Vue.component('main-piggy-list', MainPiggyList);



// i18n
let i18n = require('../i18n');

let props = {};
new Vue({
            i18n,
            el: "#dashboard",
            render: (createElement) => {
                return createElement(Dashboard, { props: props });
            },
        });
