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
