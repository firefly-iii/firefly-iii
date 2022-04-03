<!--
  - NewUser.vue
  - Copyright (c) 2022 james@firefly-iii.org
  -
  - This file is part of Firefly III (https://github.com/firefly-iii).
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <https://www.gnu.org/licenses/>.
  -->

<template>
  <div class="row">
    <div class="col q-mb-xs">
      <q-banner rounded class="bg-purple-8 text-white">
        Hi! You must be new to Firefly III. Welcome! Please fill in this form to create some basic accounts and get you
        started.
      </q-banner>
    </div>
  </div>
  <div class="row">
    <div class="col-8 offset-2 q-mb-md">
      <q-card bordered>
        <q-card-section>
          <div class="text-h6">Bank accounts</div>
        </q-card-section>
        <q-card-section>
          <div class="row q-mb-xs">
            <div class="col-8 offset-2">
              <q-input
                :error-message="bank_name_error"
                :error="bank_name_has_error"
                bottom-slots
                :disable="disabledInput"
                clearable
                outlined v-model="bank_name" label="The name of your bank">
                <template v-slot:prepend>
                  <q-icon name="fas fa-university"/>
                </template>
              </q-input>
            </div>
          </div>
          <div class="row q-mb-xs">
            <div class="col-3 offset-2">
              <q-select
                :error-message="currency_error"
                :error="currency_has_error"
                bottom-slots
                :disable="disabledInput"
                outlined
                v-model="currency" emit-value class="q-pr-xs"
                map-options :options="currencies" label="Currency"/>
            </div>
            <div class="col-5">
              <q-input
                :error-message="bank_balance_error"
                :error="bank_balance_has_error"
                bottom-slots
                :disable="disabledInput"
                outlined
                v-model="bank_balance" :mask="balance_input_mask" reverse-fill-mask fill-mask="0"
                label="Today's balance" hint="Enter your current balance">
                <template v-slot:prepend>
                  <q-icon name="fas fa-money-bill-wave"/>
                </template>
              </q-input>
            </div>
          </div>
          <div class="row q-mb-xs">
            <div class="col-8 offset-2">
              <q-input
                :error-message="savings_balance_error"
                :error="savings_balance_has_error"
                bottom-slots
                :disable="disabledInput"
                outlined
                v-model="savings_balance" :mask="balance_input_mask" reverse-fill-mask fill-mask="0"
                label="Today's savings account balance" hint="Leave empty or set to zero if not relevant.">
                <template v-slot:prepend>
                  <q-icon name="fas fa-coins"/>
                </template>
              </q-input>
            </div>
          </div>
        </q-card-section>
      </q-card>
    </div>
  </div>
  <div class="row">
    <div class="col-8 offset-2 q-mb-md">
      <q-card>
        <q-card-section>
          <div class="text-h6">Preferences</div>
        </q-card-section>
        <q-card-section>
          <div class="row q-mb-xs">
            <div class="col-8 offset-2">
              <q-select
                :error-message="language_error"
                :error="language_has_error"
                bottom-slots
                outlined
                :disable="disabledInput"
                v-model="language" emit-value
                map-options :options="languages" label="I prefer the following language"/>

            </div>
          </div>
          <div class="row">
            <div class="col-10 offset-2">
              <q-checkbox
                :disable="disabledInput"
                v-model="manage_cash" label="I want to manage cash using Firefly III"/>
              <q-banner v-if="manage_cash_has_error" class="text-white bg-red">{{ manage_cash_error }}</q-banner>
            </div>
          </div>

          <div class="row">
            <div class="col-8 offset-2">
              <q-checkbox
                :disable="disabledInput"
                v-model="have_cc" label="I have a credit card."/>
              <q-banner v-if="have_cc_has_error" class="text-white bg-red">{{ have_cc_error }}</q-banner>
            </div>
          </div>

          <div class="row">
            <div class="col-8 offset-2">
              <q-checkbox
                :disable="disabledInput"
                v-model="have_questions" label="I know where to go when I have questions"/>
              <div class="q-px-sm">
                Hint: visit <a href="https://github.com/firefly-iii/firefly-iii/discussions/">GitHub</a>
                or <a href="#">Gitter.im</a>. You can also
                contact me on <a href="#">Twitter</a> or via <a href="#">email</a>.
              </div>
              <q-banner v-if="have_questions_has_error" class="text-white bg-red">{{ have_questions_error }}</q-banner>
            </div>
          </div>
        </q-card-section>
        <q-card-section>
          <div class="row">
            <div class="col-8 offset-2">
              <q-btn color="primary" @click="submitNewUser">Submit</q-btn>
            </div>
          </div>
        </q-card-section>
      </q-card>
    </div>
  </div>
</template>

<script>
import Configuration from "../../api/system/configuration";
import List from "../../api/currencies/list";
import Post from "../../api/accounts/post";
import PostCurrency from "../../api/currencies/post";
import Put from "../../api/preferences/put";
import {format, startOfMonth} from "date-fns";

export default {
  name: "NewUser",
  data() {
    return {
      bank_name: '',
      bank_name_error: '',
      bank_name_has_error: false,

      currency: 'EUR',
      currency_error: '',
      currency_has_error: false,

      bank_balance: '',
      bank_balance_error: '',
      bank_balance_has_error: false,

      savings_balance: '',
      savings_balance_error: '',
      savings_balance_has_error: false,

      language: 'en_US',
      language_error: '',
      language_has_error: false,


      manage_cash: false,
      manage_cash_error: '',
      manage_cash_has_error: false,

      have_cc: false,
      have_cc_error: '',
      have_cc_has_error: false,

      have_questions: false,
      have_questions_error: '',
      have_questions_has_error: false,


      balance_input_mask: '#.##',
      balance_prefix: '€',
      languages: [],

      currencies: [],
      disabledInput: false,
    }
  },
  watch: {
    currency: function (value) {
      for (let key in this.currencies) {
        if (this.currencies.hasOwnProperty(key)) {
          let currency = this.currencies[key];
          if (currency.value === value) {
            let hash = '#';
            this.balance_input_mask = '#.' + hash.repeat(currency.decimal_places);
          }
        }
      }
    }
  },
  mounted() {
    // get languages
    let config = new Configuration();
    config.get('firefly.languages').then((response) => {
      let obj = response.data.data.value;
      for (let key in obj) {
        if (obj.hasOwnProperty(key)) {
          let lang = obj[key];
          this.languages.push({value: key, label: lang.name_locale + ' (' + lang.name_english + ')'});
        }
      }
    });

    // get currencies
    let list = new List;
    list.list(1).then((response) => {
      let all = response.data.data;
      for (let key in all) {
        if (all.hasOwnProperty(key)) {
          let currency = all[key];
          this.currencies.push({
            value: currency.attributes.code,
            label: currency.attributes.name,
            decimal_places: currency.attributes.decimal_places,
            symbol: currency.attributes.symbol
          });
        }
      }
    });
  },
  methods: {
    submitNewUser: function () {
      this.resetErrors();
      this.disabledInput = true;
      if ('' === this.bank_name) {
        this.bank_name_error = 'A name is required';
        this.bank_name_has_error = true;
        this.disabledInput = false;
        return;
      }
      if (false === this.have_questions) {
        this.have_questions_error = 'Please check this little box';
        this.have_questions_has_error = true;
        this.disabledInput = false;
        return;
      }
      // submit banks
      let main = this.submitMainAccount();
      let savings = this.submitSavingsAccount();
      let creditCard = this.submitCC();
      let cash = this.submitCash();

      // save language and currency:
      let currency = this.submitCurrency();
      let language = this.submitLanguage();

      Promise.all([main, savings, creditCard, cash, currency, language]).then(() => {
        this.$emit('created-accounts');
      });
    },
    submitCurrency: function () {
      // /api/v1/currencies/{code}/default
      let poster = new PostCurrency;
      return poster.makeDefault(this.currency);
    },
    submitLanguage: function () {
      // /api/v1/currencies/{code}/default
      return (new Put).put('language', this.language);
    },
    submitMainAccount: function () {
      let poster = new Post;
      let submission = {
        name: this.bank_name + ' checking account TODO',
        type: 'asset',
        account_role: 'defaultAsset',
        currency_code: this.currency_code,
      };
      if ('' !== this.bank_balance && 0.0 !== parseFloat(this.bank_balance)) {
        let today = format(new Date, 'y-MM-dd');
        submission.opening_balance = this.bank_balance;
        submission.opening_balance_date = today;
      }
      return poster.post(submission);
    },
    submitSavingsAccount: function () {
      let poster = new Post;
      let submission = {
        name: this.bank_name + ' savings account TODO',
        type: 'asset',
        account_role: 'savingAsset',
        currency_code: this.currency_code,
      };
      if ('' !== this.savings_balance && 0.0 !== parseFloat(this.savings_balance)) {
        let today = format(new Date, 'y-MM-dd');
        submission.opening_balance = this.savings_balance;
        submission.opening_balance_date = today;
        return poster.post(submission);
      }
      return Promise.resolve();
    },
    submitCC: function () {
      if (this.have_cc) {
        let poster = new Post;
        let today = format(startOfMonth(new Date), 'y-MM-dd');
        let submission = {

          name: this.bank_name + ' Credit card',
          type: 'asset',
          account_role: 'ccAsset',
          currency_code: this.currency_code,
          credit_card_type: 'monthlyFull',
          monthly_payment_date: today
        };
        return poster.post(submission);
      }
      return Promise.resolve();
    },
    submitCash: function () {
      if (this.manage_cash) {
        let poster = new Post;
        let submission = {
          name: this.bank_name + ' Cash account',
          type: 'asset',
          account_role: 'cashWalletAsset',
          currency_code: this.currency_code,
        };
        return poster.post(submission);
      }
      return Promise.resolve();
    },
    resetErrors: function () {
      this.disabledInput = false;

      this.bank_name_error = '';
      this.bank_name_has_error = false;

      this.currency_error = '';
      this.currency_has_error = false;

      this.bank_balance_error = '';
      this.bank_balance_has_error = false;

      this.savings_balance_error = '';
      this.savings_balance_has_error = false;

      this.language_error = '';
      this.language_has_error = false;

      this.manage_cash_error = '';
      this.manage_cash_has_error = false;

      this.have_cc_error = '';
      this.have_cc_has_error = false;

      this.have_questions_error = '';
      this.have_questions_has_error = false;

    }
  }
}
</script>

<style scoped>

</style>
