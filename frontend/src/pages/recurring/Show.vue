<template>
  <q-page>
    <div class="row q-mx-md">
      <div class="col-12">
        <!-- Balance chart -->
        <q-card bordered>
          <q-card-section>
            <div class="text-h6">{{ recurrence.title }}</div>
          </q-card-section>
          <q-card-section>
            <div class="row">
              <div class="col-12 q-mb-xs">
                Title: {{ recurrence.title }}<br>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script>
import Get from "../../api/recurring/get";

export default {
  name: "Show",
  data() {
    return {
      recurrence: {},
      id: 0
    }
  },
  created() {
    this.id = parseInt(this.$route.params.id);
    this.getRecurring();
  },
  methods: {
    onRequest: function (payload) {
      this.page = payload.page;
      this.getRecurring();
    },
    getRecurring: function () {
      (new Get).get(this.id).then((response) => this.parseRecurring(response));
    },
    parseRecurring: function (response) {
      this.recurrence = {
        title: response.data.data.attributes.title,
      };
    },
  }
}
</script>

<style scoped>

</style>
