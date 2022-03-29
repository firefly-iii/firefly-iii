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
