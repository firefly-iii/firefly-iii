<!--
  - TransactionList.vue
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
  <div>
    <q-card bordered>
      <q-item>
        <q-item-section>
          <q-item-label><strong>{{ accountName }}</strong>
            <span v-if="accountCurrencyCode !== ''">
              ({{ formatAmount(accountCurrencyCode, accountBalance) }})
            </span>
          </q-item-label>
        </q-item-section>
      </q-item>
      <q-separator/>
      <q-markup-table>
        <thead>
        <tr>
          <th class="text-left">Description</th>
          <th class="text-right">Opposing account</th>
          <th class="text-right">Amount</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="transaction in transactions">
          <td class="text-left">
            <router-link :class="$q.dark.isActive ? 'text-red' : 'text-blue'" :to="{ name: 'transactions.show', params: {id: transaction.transactionGroupId} }">
              <strong v-if="transaction.transactions.length > 1">
                {{ transaction.transactionGroupTitle }}<br/>
              </strong>
            </router-link>
            <span v-for="tr in transaction.transactions">
                  <span v-if="transaction.transactions.length > 1">
                    {{ tr.description }}
                  <br/>
                  </span>
                  <router-link :class="$q.dark.isActive ? 'text-red' : 'text-blue'" v-if="transaction.transactions.length === 1"
                               :to="{ name: 'transactions.show', params: {id: transaction.transactionGroupId} }">
                    {{ tr.description }}
                  </router-link>
                </span>
          </td>
          <td class="text-right">
            <!-- withdrawal -->
            <!-- deposit -->
            <!-- transfer -->
            <!-- other -->
            <span v-if="transaction.transactions.length > 1"><br></span>
            <span v-for="tr in transaction.transactions">

                      <router-link :class="$q.dark.isActive ? 'text-red' : 'text-blue'" :to="{ name: 'accounts.show', params: {id: tr.destination_id} }">
                      {{ tr.destination_name }}
                    </router-link>
              <br v-if="transaction.transactions.length > 1"/>
                </span>
          </td>
          <td class="text-right">
            <span v-if="transaction.transactions.length > 1"><br></span>
            <!-- per transaction -->
            <span v-for="tr in transaction.transactions">
              <!-- simply show the amount -->
              <span v-if="false === tr.native_currency_converted">{{ formatAmount(tr.currency_code, tr.amount) }}</span>

              <!-- show amount with original in the title -->
              <span v-if="true === tr.native_currency_converted" :title="formatAmount(tr.currency_code, tr.amount)">{{
                  formatAmount(tr.native_currency_code, tr.native_amount)
                }}</span>

              <!-- show foreign amount if present and not converted (may lead to double amounts) -->
              <span v-if="null !== tr.foreign_amount">
                    <span v-if="false === tr.foreign_currency_converted"> ({{
                        formatAmount(tr.foreign_currency_code, tr.foreign_amount)
                      }})</span>
                    <span v-if="true === tr.foreign_currency_converted"
                          :title="formatAmount(tr.foreign_currency_code, tr.foreign_amount)"> ({{
                        formatAmount(tr.native_currency_code, tr.native_foreign_amount)
                      }})</span>
              </span>
            <br v-if="transaction.transactions.length > 1"/>
            </span>
          </td>
        </tr>
        </tbody>
      </q-markup-table>
    </q-card>
  </div>
</template>

<script>
import Get from "../../api/v2/accounts/get";
import {useFireflyIIIStore} from "../../stores/fireflyiii";
import {format} from "date-fns";

export default {
  name: "TransactionList",
  props: {
    accountId: 0,
  },
  data() {
    return {
      store: null,
      accountName: '',
      transactions: [],
      accountCurrencyCode: '',
      accountBalance: 0.0
    }
  },
  mounted() {
    this.store = useFireflyIIIStore();
    if (0 !== this.accountId) {
      this.getAccount();
      // TODO this code snippet is recycled a lot.
      // subscribe, then update:
      this.store.$onAction(
        ({name, $store, args, after, onError,}) => {
          after((result) => {
            if (name === 'setRange') {
              this.getTransactions();
            }
          })
        }
      )
      this.getTransactions();
    }
  },
  methods: {
    getAccount: function () {
      (new Get).get(this.accountId).then((response) => this.parseAccount(response.data));
    },
    parseAccount: function (data) {
      console.log(data.data.attributes);
      this.accountName = data.data.attributes.name;
      this.accountBalance = data.data.attributes.current_balance;
      this.accountCurrencyCode = data.data.attributes.currency_code;
    },
    getTransactions: function () {
      if (null !== this.store.getRange.start && null !== this.store.getRange.end) {
        const start = new Date(this.store.getRange.start);
        const end = new Date(this.store.getRange.end);
        let startStr = format(start, 'y-MM-dd');
        let endStr = format(end, 'y-MM-dd');
        (new Get).transactions(this.accountId,
          {
            start: startStr,
            end: endStr,
            limit: 10
          }).then((response) => this.parseTransactions(response.data));
      }
    },
    // TODO this method is recycled a lot.
    formatAmount: function (currencyCode, amount) {
      return Intl.NumberFormat(this.store.getLocale, {style: 'currency', currency: currencyCode}).format(amount);
    },
    parseTransactions: function (data) {
      for (let i in data.data) {
        if (data.data.hasOwnProperty(i)) {
          let group = data.data[i];
          let ic = {
            transactionGroupId: group.id,
            transactionGroupTitle: group.attributes.group_title,
            transactions: [],
          };
          for (let ii in group.attributes.transactions) {
            if (group.attributes.transactions.hasOwnProperty(ii)) {
              let transaction = group.attributes.transactions[ii];
              let iic = {
                journalId: transaction.transaction_journal_id,
                description: transaction.description,
                destination_name: transaction.destination_name,
                destination_id: transaction.destination_id,
                type: transaction.type,

                amount: transaction.amount,
                native_amount: transaction.native_amount,

                foreign_amount: transaction.foreign_amount,
                native_foreign_amount: transaction.native_foreign_amount,

                currency_code: transaction.currency_code,
                native_currency_code: transaction.native_currency_code,

                foreign_currency_code: transaction.foreign_currency_code,

                native_currency_converted: transaction.native_currency_converted,
                foreign_currency_converted: transaction.foreign_currency_converted,
              };
              ic.transactions.push(iic);
            }
          }
          this.transactions.push(ic);
        }
      }
    }
  },
}
</script>
