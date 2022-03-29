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
            <div class="text-h6">{{ budget.name }}</div>
          </q-card-section>
          <q-card-section>
            <div class="row">
              <div class="col-12 q-mb-xs">
                Name: {{ budget.name }}<br>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <div class="row q-mt-sm">
      <div class="col-12">
        <LargeTable ref="table"
                    title="Transactions"
                    :rows="rows"
                    :loading="loading"
                    v-on:on-request="onRequest"
                    :rows-number="rowsNumber"
                    :rows-per-page="rowsPerPage"
                    :page="page"
        >
        </LargeTable>
      </div>
    </div>


  </q-page>
</template>

<script>
import LargeTable from "../../components/transactions/LargeTable";
import Get from "../../api/budgets/get";
import Parser from "../../api/transactions/parser";

export default {
  name: "Show",
  data() {
    return {
      budget: {},
      rows: [],
      rowsNumber: 1,
      rowsPerPage: 10,
      page: 1
    }
  },
  created() {
    if ('no-budget' === this.$route.params.id) {
      this.id = 0;
      this.getWithoutBudget();
    }
    if ('no-budget' !== this.$route.params.id) {
      this.id = parseInt(this.$route.params.id);
      this.getBudget();
    }
  },
  components: {LargeTable},
  methods: {
    onRequest: function (payload) {
      this.page = payload.page;
      this.getBudget();
    },
    getWithoutBudget: function () {
      this.budget = {name: '(without budget)'};

      this.loading = true;
      const parser = new Parser;
      this.rows = [];
      let get = new Get;
      get.transactionsWithoutBudget(this.page, this.getCacheKey).then(
        (response) => {
          let resp = parser.parseResponse(response);

          this.rowsPerPage = resp.rowsPerPage;
          this.rowsNumber = resp.rowsNumber;
          this.rows = resp.rows;
          this.loading = false;
        }
      );

    },
    getBudget: function () {
      let get = new Get;
      get.get(this.id).then((response) => this.parseBudget(response));

      this.loading = true;
      const parser = new Parser;
      this.rows = [];

      get.transactions(this.id, this.page, this.getCacheKey).then(
        (response) => {
          let resp = parser.parseResponse(response);

          this.rowsPerPage = resp.rowsPerPage;
          this.rowsNumber = resp.rowsNumber;
          this.rows = resp.rows;
          this.loading = false;
        }
      );
    },
    parseBudget: function (response) {
      this.budget = {
        name: response.data.data.attributes.name,
      };
    },
  }
}
</script>

<style scoped>

</style>
