<template>
  <q-page>
    <div class="row q-mx-md">
      <div class="col-12">
        <!-- Balance chart -->
        <q-card bordered>
          <q-card-section>
            <div class="text-h6">{{ rule.title }}</div>
          </q-card-section>
          <q-card-section>
            <div class="row">
              <div class="col-12 q-mb-xs">
                Rule: {{ rule.title }}<br>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script>
import Get from "../../api/rules/get";

export default {
  name: "Show",
  data() {
    return {
      rule: {},
      id: 0
    }
  },
  created() {
    this.id = parseInt(this.$route.params.id);
    this.getRule();
  },
  methods: {
    onRequest: function (payload) {
      this.page = payload.page;
      this.getRule();
    },
    getRule: function () {
      (new Get).get(this.id).then((response) => this.parseRule(response));
    },
    parseRule: function (response) {
      this.rule = {
        title: response.data.data.attributes.title,
      };
    },
  }
}
</script>

<style scoped>

</style>
