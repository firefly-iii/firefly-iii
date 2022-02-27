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
