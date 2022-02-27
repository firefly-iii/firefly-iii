<template>
  <q-page>
    <div class="row q-mx-md">
      <div class="col-12">
        <q-card bordered>
          <q-card-section>
            <div class="text-h6">{{ group.title }}</div>
          </q-card-section>
          <q-card-section>
            <div class="row">
              <div class="col-12 q-mb-xs">
                Title: {{ group.title }}<br>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script>
import Get from "../../api/groups/get";

export default {
  name: "Show",
  data() {
    return {
      group: {},
      id: 0
    }
  },
  created() {
    this.id = parseInt(this.$route.params.id);
    this.getGroup();
  },
  components: {},
  methods: {
    onRequest: function (payload) {
      this.page = payload.page;
      this.getGroup();
    },
    getGroup: function () {
      let get = new Get;
      get.get(this.id).then((response) => this.parseGroup(response));
    },
    parseGroup: function (response) {
      this.group = {
        title: response.data.data.attributes.title,
      };
    },
  }
}
</script>

<style scoped>

</style>
