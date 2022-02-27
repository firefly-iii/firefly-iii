<template>
  <q-page>
    <q-card>
      <q-card-section>
        <span v-for="tag in tags">
              <q-badge outline class="q-ma-xs" color="blue">
                <router-link :to="{ name: 'tags.show', params: {id: tag.id} }">
                {{ tag.attributes.tag }}
                </router-link>

              </q-badge>
        </span>
      </q-card-section>
    </q-card>
    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-fab
        label="Actions"
        square
        vertical-actions-align="right"
        label-position="left"
        color="green"
        icon="fas fa-chevron-up"
        direction="up"
      >
        <q-fab-action color="primary" square :to="{ name: 'tags.create'}" icon="fas fa-exchange-alt" label="New tag"/>
      </q-fab>
    </q-page-sticky>
  </q-page>
</template>

<script>
import {mapGetters, useStore} from "vuex";
import List from "../../api/tags/list";

export default {
  name: 'Index',
  watch: {
    $route(to) {
      // react to route changes...
      if ('tags.index' === to.name) {
        this.page = 1;
        this.updateBreadcrumbs();
        this.triggerUpdate();
      }
    }
  },
  data() {
    return {
      tags: [],
      loading: false,
    }
  },
  computed: {
    ...mapGetters('fireflyiii', ['getRange', 'getCacheKey']),
  },
  created() {
  },
  mounted() {
    if (null === this.getRange.start || null === this.getRange.end) {
      // subscribe, then update:
      const $store = useStore();
      $store.subscribe((mutation, state) => {
        if ('fireflyiii/setRange' === mutation.type) {
          this.range = {start: mutation.payload.start, end: mutation.payload.end};
          this.triggerUpdate();
        }
      });
    }
    if (null !== this.getRange.start && null !== this.getRange.end) {
      this.range = {start: this.getRange.start, end: this.getRange.end};
      this.triggerUpdate();
    }
  },
  methods: {
    updateBreadcrumbs: function () {
      this.$route.meta.pageTitle = 'firefly.tags';
      this.$route.meta.breadcrumbs = [{title: 'tags'}];

    },
    onRequest: function (props) {
      this.page = props.pagination.page;
      this.triggerUpdate();
    },
    triggerUpdate: function () {
      if (this.loading) {
        return;
      }
      this.loading = true;
      this.getPage(1);
    },
    getPage: function (page) {
      const list = new List();
      this.rows = [];
      list.list(page, this.getCacheKey).then(
        (response) => {
          for (let i in response.data.data) {
            if (response.data.data.hasOwnProperty(i)) {
              let current = response.data.data[i];
              this.tags.push(current);
            }
          }
          // get next page:
          if (page < parseInt(response.data.meta.pagination.total_pages)) {
            this.getPage(page + 1);
          }
          if (page === parseInt(response.data.meta.pagination.total_pages)) {
            this.loading = false;
          }
        }
      );
    }
  }
}
</script>
