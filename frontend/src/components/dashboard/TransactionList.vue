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
          <q-item-label><strong>{{ accountName }}</strong> (balance)</q-item-label>
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
                <router-link :to="{ name: 'transactions.show', params: {id: transaction.transactionGroupId} }">
                <strong v-if="transaction.transactions.length > 1">
                  {{ transaction.transactionGroupTitle }}<br />
                </strong>
                </router-link>
                <span v-for="tr in transaction.transactions">
                  <span v-if="transaction.transactions.length > 1">
                    {{tr.description}}
                  <br />
                  </span>
                  <router-link :to="{ name: 'transactions.show', params: {id: transaction.transactionGroupId} }" v-if="transaction.transactions.length === 1">
                    {{tr.description}}
                  </router-link>
                </span>
              </td>
              <td class="text-right">159</td>
              <td class="text-right">6</td>
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
      this.accountName = data.data.attributes.name;
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
                amount: transaction.amount,
                currency_code: transaction.currency_code,
                destination_name: transaction.destination_name,
                destination_id: transaction.destination_id,
                type: transaction.type,
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
