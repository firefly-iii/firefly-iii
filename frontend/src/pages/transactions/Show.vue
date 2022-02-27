<!--
  - Show.vue
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
  <q-page>
    <div class="row q-mx-md">
      <div class="col-12">
        <!-- Balance chart -->
        <q-card bordered>
          <q-card-section>
            <div class="text-h6">Transaction: {{ title }}
            </div>
          </q-card-section>
          <q-card-section>
            <div class="row" v-for="(transaction, index) in group.transactions">
              <div class="col-12 q-mb-xs">
                <strong>index {{ index }}</strong><br>
                {{ transaction.description }}<br>
                {{ transaction.amount }}<br>
                {{ transaction.source_name }} --&gt; {{ transaction.destination_name }}
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script>
import Get from "../../api/transactions/get";
import LargeTable from "../../components/transactions/LargeTable";

export default {
  name: "Show",
  data() {
    return {
      title: '',
      group: {
        transactions: []
      },
      rows: [],
      rowsNumber: 1,
      rowsPerPage: 10,
      page: 1
    }
  },
  created() {
    this.id = parseInt(this.$route.params.id);
    this.getTransaction();
  },
  mounted() {
  },
  components: {LargeTable},
  methods: {
    onRequest: function (payload) {
      this.page = payload.page;
      this.getTag();
    },
    getTransaction: function () {
      let get = new Get;
      this.loading = true;
      get.get(this.id).then((response) => this.parseTransaction(response.data.data));
    },
    parseTransaction: function (data) {
      this.group = {
        group_title: data.attributes.group_title,
        transactions: [],
      };
      if(null !== data.attributes.group_title) {
        this.title = data.attributes.group_title;
      }
      for(let i in data.attributes.transactions) {
        if(data.attributes.transactions.hasOwnProperty(i)) {
          let transaction = data.attributes.transactions[i];
          this.group.transactions.push(transaction);

          if(0 === parseInt(i) && null === data.attributes.group_title) {
            this.title = transaction.description;
          }
        }
      }
      this.loading = false;
    },
  }
}
</script>

<style scoped>

</style>
