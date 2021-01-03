<!--
  - Create.vue
  - Copyright (c) 2020 james@firefly-iii.org
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
  <div>
    <div class="row" v-for="(transaction, index) in transactions">
      <div class="col">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">
              <span v-if="0 === transactions.length">{{ $t('firefly.create_new_transaction') }}</span>
              <span v-if="transactions.length > 1">{{ $t('firefly.single_split') }} {{ index + 1 }} / {{ transactions.length }}</span>
            </h3>
            <div v-if="transactions.length > 1" class="card-tools">
              <button class="btn btn-xs btn-danger" type="button" v-on:click="deleteTransaction(index, $event)"><i
                  class="fa fa-trash"></i></button>
            </div>
          </div>
          <!-- /.card-header -->
          <div class="card-body">
            <h4>{{ $t('firefly.basic_journal_information') }}</h4>
            <div class="row">
              <div class="col">
                <p class="d-block d-sm-none">XS</p>
                <p class="d-none d-sm-block d-md-none">SM</p>
                <p class="d-none d-md-block d-lg-none">MD</p>
                <p class="d-none d-lg-block d-xl-none">LG</p>
                <p class="d-none d-xl-block">XL</p>
              </div>
            </div>
            <!-- description etc, 3 rows -->
            <div class="row">
              <div class="col">
                <TransactionDescription
                    :description="transactions[index].description"
                    :index="index"
                ></TransactionDescription>
              </div>
            </div>


            <!-- source and destination -->
            <div class="row">
              <div class="col-xl-5 col-lg-5 col-md-10 col-sm-12 col-xs-12">
                <!-- SOURCE -->
                <TransactionAccount
                    :selectedAccount="transactions[index].source_account"
                    direction="source"
                    :index="index"
                />
              </div>
              <!-- switcharoo! -->
              <div class="col-xl-2 col-lg-2 col-md-2 col-sm-12 text-center d-none d-sm-block">
                <SwitchAccount
                    :index="index"
                />
              </div>

              <!-- destination -->
              <div class="col-xl-5 col-lg-5 col-md-12 col-sm-12 col-xs-12">
                <!-- DESTINATION -->
                <TransactionAccount
                    :selectedAccount="transactions[index].destination_account"
                    direction="destination"
                    :index="index"
                />
              </div>
            </div>

            <!-- amount  -->
            <div class="row">
              <div class="col-xl-5 col-lg-5 col-md-10 col-sm-12 col-xs-12">
                <!-- AMOUNT -->
                <TransactionAmount :index="index"/>
                <!--

                -->
              </div>
              <div class="col-xl-2 col-lg-2 col-md-2 col-sm-12 text-center d-none d-sm-block">
                <TransactionForeignCurrency :index="index"/>
              </div>
              <div class="col-xl-5 col-lg-5 col-md-12 col-sm-12 col-xs-12">
                <TransactionForeignAmount :index="index"/>
              </div>
            </div>

            <!-- dates -->
            <div class="row">
              <div class="col-xl-5 col-lg-5 col-md-12 col-sm-12 col-xs-12">
                <TransactionDate
                    :date="transactions[index].date"
                    :time="transactions[index].time"
                    :index="index"
                />
              </div>

              <div class="col-xl-5 col-lg-5 col-md-12 col-sm-12 col-xs-12 offset-xl-2 offset-lg-2">
                <TransactionCustomDates :index="index" :enabled-dates="customDateFields"/>
              </div>
            </div>

            <!--
                        <p class="d-block d-sm-none">XS</p>
                        <p class="d-none d-sm-block d-md-none">SM</p>
                        <p class="d-none d-md-block d-lg-none">MD</p>
                        <p class="d-none d-lg-block d-xl-none">LG</p>
                        <p class="d-none d-xl-block">XL</p>


                        <br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>

                        {# top stuff #}
                        <div class="row">
                          <div class="col">

                          </div>
                        </div>


            -->

            <div class="row">
              <div class="col">
                <!-- AMOUNT -->
                <!--
                <div class="input-group">
                  <input
                      title="Amount"
                      autocomplete="off"
                      autofocus
                      class="form-control"
                      name="something[]"
                      type="text"
                      placeholder="Amount"
                  >
                </div>
                <div class="input-group">
                  <input
                      title="Foreign"
                      autocomplete="off"
                      autofocus
                      class="form-control"
                      name="something[]"
                      type="text"
                      placeholder="Foreign"
                  >
                </div>
                -->
              </div>
              <div class="col">

              </div>
            </div>


            <h4>{{ $t('firefly.transaction_journal_meta') }}</h4>

            <!-- meta -->
            <div class="row">
              <div class="col">
                <TransactionBudget
                    :budget_id="transactions[index].budget_id"
                    :index="index"
                />
                <div class="input-group">
                  <input
                      title="Category"
                      autocomplete="off"
                      class="form-control"
                      name="something[]"
                      type="text"
                      placeholder="Category"
                  >
                </div>
              </div>
              <div class="col">
                <div class="input-group">
                  <input
                      title="Bill"
                      autocomplete="off"
                      class="form-control"
                      name="something[]"
                      type="text"
                      placeholder="Bill"
                  >
                </div>
                <div class="input-group">
                  <input
                      title="Tags"
                      autocomplete="off"
                      class="form-control"
                      name="something[]"
                      type="text"
                      placeholder="Tags"
                  >
                </div>
                <div class="input-group">
                  <input
                      title="Piggy"
                      autocomplete="off"
                      class="form-control"
                      name="something[]"
                      type="text"
                      placeholder="Piggy"
                  >
                </div>
              </div>
            </div>

            <h4>{{ $t('firefly.transaction_journal_extra') }}</h4>

            <div class="row">
              <div class="col">
                <div class="input-group">
                  <input
                      title="internal ref"
                      autocomplete="off"
                      class="form-control"
                      name="something[]"
                      type="text"
                      placeholder="internal ref"
                  >
                </div>
                <div class="input-group">
                  <input
                      title="Piggy"
                      autocomplete="off"
                      class="form-control"
                      name="something[]"
                      type="text"
                      placeholder="external url"
                  >
                </div>
                <div class="input-group">
                  <textarea class="form-control" placeholder="Notes"></textarea>
                </div>
              </div>
              <div class="col">
                <div class="input-group">
                  <input
                      title="Piggy"
                      autocomplete="off"
                      class="form-control"
                      name="something[]"
                      type="text"
                      placeholder="transaction links"
                  >
                </div>
                <div class="input-group">
                  <input
                      title="Piggy"
                      autocomplete="off"
                      class="form-control"
                      name="something[]"
                      type="text"
                      placeholder="piggy bank"
                  >
                </div>
              </div>

            </div>
          </div>
          <!-- /.card-body -->
        </div>
      </div>
    </div>

    <!-- buttons -->
    <!-- button -->
    <div class="row">
      <div class="col">
        <button @click="addTransaction" class="btn btn-primary">{{ $t('firefly.add_another_split') }}</button>
      </div>
      <div class="col">
        <p class="float-right">
          <button @click="submitTransaction" :disabled="isSubmitting" class="btn btn-success">Store transaction</button>
          <br/>
        </p>
      </div>
    </div>
    <div class="row">
      <div class="col float-right">
        <p class="text-right">
          <small class="text-muted">Create another another another <input type="checkbox"/></small><br/>
          <small class="text-muted">Return here <input type="checkbox"/></small><br/>
        </p>
      </div>
    </div>

  </div>
</template>

<script>
import TransactionDescription from "./TransactionDescription";
import TransactionDate from "./TransactionDate";
import TransactionBudget from "./TransactionBudget";
import {createNamespacedHelpers} from 'vuex'
import TransactionAccount from "./TransactionAccount";
import SwitchAccount from "./SwitchAccount";
import TransactionAmount from "./TransactionAmount";
import TransactionForeignAmount from "./TransactionForeignAmount";
import TransactionForeignCurrency from "./TransactionForeignCurrency";
import TransactionCustomDates from "./TransactionCustomDates";

const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('transactions/create')


export default {
  name: "Create",
  components: {
    TransactionCustomDates,
    TransactionForeignCurrency,
    TransactionForeignAmount, TransactionAmount, SwitchAccount, TransactionAccount, TransactionBudget, TransactionDescription, TransactionDate
  },
  created() {
    this.storeAllowedOpposingTypes();
    this.storeAccountToTransaction();
    this.storeCustomDateFields();
    this.addTransaction();
  },
  data() {
    return {
      groupTitle: '',
      isSubmitting: false
    }
  },
  computed: {
    ...mapGetters([
                    'transactionType', // -> this.someGetter
                    'transactions', // -> this.someOtherGetter
                    'customDateFields'
                  ])
  },
  methods: {
    ...mapMutations(
        [
          'addTransaction',
          'deleteTransaction',
          'setAllowedOpposingTypes',
          'setAccountToTransaction',
        ],
    ),
    storeCustomDateFields: function () {
      // TODO may include all custom fields in the future.
      axios.get('./api/v1/preferences/transaction_journal_optional_fields').then(response => {
        let fields = response.data.data.attributes.data;
        let allDateFields = ['interest_date', 'book_date', 'process_date', 'due_date', 'payment_date', 'invoice_date'];
        let selectedDateFields = {
          interest_date: false,
          book_date: false,
          process_date: false,
          due_date: false,
          payment_date: false,
          invoice_date: false,
        };
        for (let key in fields) {
          if (fields.hasOwnProperty(key)) {
            if(-1 !== allDateFields.indexOf(key)) {
              selectedDateFields[key] = fields[key];
            }
          }
        }
        this.$store.commit('transactions/create/setCustomDateFields', selectedDateFields);
      });
    },
    /**
     *
     */
    storeAllowedOpposingTypes: function () {
      this.setAllowedOpposingTypes(window.allowedOpposingTypes);
    },
    storeAccountToTransaction: function () {
      this.setAccountToTransaction(window.accountToTransaction);
    },
    /**
     *
     */
    submitTransaction: function () {
      this.isSubmitting = true;
      // console.log('Now in submit()');
      const uri = './api/v1/transactions';
      const data = this.convertData();

      console.log('Would have submitted:');
      console.log(data);

      this.isSubmitting = false;
    },
    /**
     *
     */
    convertData: function () {
      // console.log('now in convertData');
      let data = {
        //'group_title': null,
        'transactions': []
      };
      for (let key in this.transactions) {
        if (this.transactions.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
          data.transactions.push(this.convertSplit(key, this.transactions[key]));
        }
      }
      return data;
    },

    /**
     *
     * @param key
     * @param array
     */
    convertSplit: function (key, array) {
      let currentSplit = {
        // basic
        description: array.description,
        date: (array.date + ' ' + array.time).trim(),

        // account
        source_id: array.source_account.id ?? null,
        source_name: array.source_account.name ?? null,
        destination_id: array.destination_account.id ?? null,
        destination_name: array.destination_account.name ?? null,

        // amount:
        currency_id: array.currency_id,
        amount: array.amount,
        foreign_currency_id: array.foreign_currency_id,
        foreign_amount: array.foreign_amount,


        // meta
        budget_id: array.budget_id,

      };

      // return it.
      return currentSplit;
    }

    // addTransactionToArray: function (e) {
    //   console.log('Now in addTransactionToArray()');
    //   this.$store.
    //
    //   this.transactions.push({
    //                            description: '',
    //                          });
    //   if (e) {
    //     e.preventDefault();
    //   }
  },
}
</script>

<style scoped>

</style>