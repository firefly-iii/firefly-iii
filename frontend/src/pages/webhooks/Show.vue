<template>
  <q-page>
    <div class="row q-mx-md">
      <div class="col-12">
        <!-- Balance chart -->
        <q-card bordered>
          <q-card-section>
            <div class="text-h6">{{ webhook.title }}</div>
          </q-card-section>
          <q-card-section>
            <div class="row">
              <div class="col-12 q-mb-xs">
                Name: {{ webhook.title }}<br>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script>
import Get from "../../api/webhooks/get";

export default {
  name: "Show",
  data() {
    return {
      webhook: {},
      id: 0
    }
  },
  created() {
      this.id = parseInt(this.$route.params.id);
      this.getWebhook();
  },
  methods: {
    getWebhook: function () {
      (new Get).get(this.id).then((response) => this.parseWebhook(response));
    },
    parseWebhook: function (response) {
      this.webhook = {
        title: response.data.data.attributes.title,
      };
    },
  }
}
</script>

<style scoped>

</style>
