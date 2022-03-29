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
            <div class="text-h6">{{ currency.name }}</div>
          </q-card-section>
          <q-card-section>
            <div class="row">
              <div class="col-12 q-mb-xs">
                Name: {{ currency.name }}<br>
                Code: {{ currency.code }}<br>
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
import Get from "../../api/currencies/get";
import Parser from "../../api/transactions/parser";

export default {
  name: "Show",
  data() {
    return {
      currency: {},
      rows: [],
      rowsNumber: 1,
      rowsPerPage: 10,
      page: 1,
      code: '',
    }
  },
  created() {
    this.code = this.$route.params.code;
    this.getCurrency();
  },
  components: {LargeTable},
  methods: {
    onRequest: function (payload) {
      this.page = payload.page;
      this.getCurrency();
    },
    getCurrency: function () {
      let get = new Get;
      get.get(this.code).then((response) => this.parseCurrency(response));

      this.loading = true;
      const parser = new Parser;
      this.rows = [];

      get.transactions(this.code, this.page, this.getCacheKey).then(
        (response) => {
          let resp = parser.parseResponse(response);

          this.rowsPerPage = resp.rowsPerPage;
          this.rowsNumber = resp.rowsNumber;
          this.rows = resp.rows;
          this.loading = false;
        }
      );
    },
    parseCurrency: function (response) {
      this.currency = {
        name: response.data.data.attributes.name,
        code: response.data.data.attributes.code,
      };
    },
  }
}
</script>

<style scoped>

</style>
