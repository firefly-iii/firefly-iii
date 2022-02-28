<template>
  <q-page>
    <div class="row q-mx-md">
      <div class="col-12">
        <!-- Balance chart -->
        <q-card bordered>
          <q-card-section>
            <div class="text-h6">{{ piggyBank.name }}</div>
          </q-card-section>
          <q-card-section>
            <div class="row">
              <div class="col-12 q-mb-xs">
                Name: {{ piggyBank.name }}<br>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script>
import Get from "../../api/piggy-banks/get";

export default {
  name: "Show",
  data() {
    return {
      piggyBank: {},
      id: 0
    }
  },
  created() {
      this.id = parseInt(this.$route.params.id);
      this.getPiggyBank();
  },
  methods: {
    onRequest: function (payload) {
      this.page = payload.page;
      this.getPiggyBank();
    },
    getPiggyBank: function () {
      (new Get).get(this.id).then((response) => this.parsePiggyBank(response));
    },
    parsePiggyBank: function (response) {
      this.piggyBank = {
        name: response.data.data.attributes.name,
      };
    },
  }
}
</script>

<style scoped>

</style>
