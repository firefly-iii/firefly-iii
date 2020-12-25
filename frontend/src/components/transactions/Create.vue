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
              {{ $t('firefly.new_transaction')}}
              <span v-if="transactions.length > 1">({{ $t('firefly.single_split') }} {{ index + 1}} / {{ transactions.length }})</span>
            </h3>
            <div v-if="transactions.length > 1" class="box-tools pull-right">
              <button class="btn btn-xs btn-danger" type="button" v-on:click="deleteTransaction(index, $event)"><i
                  class="fa fa-trash"></i></button>
            </div>
          </div>
          <!-- /.card-header -->
          <div class="card-body">
            <div id="accordion">
              <!-- we are adding the .class so bootstrap.js collapse plugin detects it -->
              <div class="card card-primary">
                <div class="card-header">
                  <h4 class="card-title">
                    <a data-toggle="collapse" data-parent="#accordion" :href="'#collapseBasic' + index" class='' aria-expanded="true">
                      {{ $t('firefly.basic_journal_information') }}
                    </a>
                  </h4>
                </div>
                <div :id="'collapseBasic' + index" class="panel-collapse in collapse show" style=''>
                  <div class="card-body">
                    <div class="row">
                      <div class="col">
                        <p>
                          Source
                        </p>
                      </div>
                      <div class="col">
                        <p>
                          Amount
                          <br>
                          foreign amount
                        </p>
                      </div>
                      <div class="col">
                        <p>
                          Destination
                        </p>
                      </div>
                    </div>
                    <div class="row">
                      <div class="col">
                        <TransactionDescription
                            :description="transactions[index].description"
                            :index="index"
                        ></TransactionDescription>
                      </div>
                    </div>
                    <div class="row">
                      <div class="col">
                        <!--
                        <TransactionDate
                            :description="transactions[index].date"
                            :index="index"
                        ></TransactionDate>
                        -->
                        Date and time.
                      </div>
                      <div class="col">
                        Other date
                      </div>
                      <div class="col">
                        Other date
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="card card-secondary">
                <div class="card-header">
                  <h4 class="card-title">
                    <a data-toggle="collapse" data-parent="#accordion" :href="'#collapseMeta' + index" class="collapsed" aria-expanded="false">
                      {{ $t('firefly.transaction_journal_meta') }}
                    </a>
                  </h4>
                </div>
                <div :id="'collapseMeta' + index" class="panel-collapse collapse">
                  <div class="card-body">
                    <div class="row">
                      <div class="col">
                        Budget<br>
                        Cat<br>
                      </div>
                      <div class="col">
                        Bill<br>
                        Tags<br>
                        Piggy<br>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="card card-secondary">
                <div class="card-header">
                  <h4 class="card-title">
                    <a data-toggle="collapse" data-parent="#accordion" :href="'#collapseExtra' + index" class="collapsed" aria-expanded="false">
                      {{ $t('firefly.transaction_journal_extra') }}
                    </a>
                  </h4>
                </div>
                <div :id="'collapseExtra' + index" class="panel-collapse collapse">
                  <div class="card-body">
                    <div class="row">
                      <div class="col">
                        Internal ref<br/>
                        External URL<br/>
                        Notes
                      </div>
                      <div class="col">
                        Transaction links<br/>
                        Attachments
                      </div>

                    </div>
                  </div>
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
import {createNamespacedHelpers} from 'vuex'

const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('transactions/create')


export default {
  name: "Create",
  components: {TransactionDescription},
  created() {
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
                  ])
  },
  methods: {
    ...mapMutations(
        [
          'addTransaction',
          'deleteTransaction'
        ]
    ),
    /**
     *
     */
    submitTransaction: function () {
      this.isSubmitting = true;
      console.log('Now in submit()');
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
      console.log('now in convertData');
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
        description: array.description
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