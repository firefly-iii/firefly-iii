<template>
  <q-page>
    <div class="row q-mx-md">
      <div class="col-12">
        <!-- Balance chart -->
        <q-card bordered>
          <q-card-section>
            <div class="text-h6">{{ category.name }}</div>
          </q-card-section>
          <q-card-section>
            <div class="row">
              <div class="col-12 q-mb-xs">
                Name: {{ category.name }}<br>
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
import Get from "../../api/categories/get";
import Parser from "../../api/transactions/parser";

export default {
  name: "Show",
  data() {
    return {
      category: {},
      rows: [],
      rowsNumber: 1,
      rowsPerPage: 10,
      page: 1,
      id: 0
    }
  },
  created() {
    if ('no-category' === this.$route.params.id) {
      this.id = 0;
      this.getWithoutCategory();
    }
    if ('no-category' !== this.$route.params.id) {
      this.id = parseInt(this.$route.params.id);
      this.getCategory();
    }
  },
  components: {LargeTable},
  methods: {
    onRequest: function (payload) {
      this.page = payload.page;
      this.getCategory();
    },
    getWithoutCategory: function () {
      this.category = {name: '(without category)'};

      this.loading = true;
      const parser = new Parser;
      this.rows = [];
      let get = new Get;
      get.transactionsWithoutCategory(this.page, this.getCacheKey).then(
        (response) => {
          let resp = parser.parseResponse(response);

          this.rowsPerPage = resp.rowsPerPage;
          this.rowsNumber = resp.rowsNumber;
          this.rows = resp.rows;
          this.loading = false;
        }
      );

    },
    getCategory: function () {
      let get = new Get;
      get.get(this.id).then((response) => this.parseCategory(response));

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
    parseCategory: function (response) {
      this.category = {
        name: response.data.data.attributes.name,
      };
    },
  }
}
</script>

<style scoped>

</style>
