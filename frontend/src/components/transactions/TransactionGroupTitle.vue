<!--
  - TransactionGroupTitle.vue
  - Copyright (c) 2021 james@firefly-iii.org
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
  <div class="form-group">
    <div class="text-xs d-none d-lg-block d-xl-block">
      {{ $t('firefly.split_transaction_title') }}
    </div>
    <vue-typeahead-bootstrap
        inputName="group_title"
        v-model="value"
        :data="descriptions"
        :placeholder="$t('firefly.split_transaction_title')"
        :showOnFocus=true
        :minMatchingChars="3"
        :serializer="item => item.description"
        @input="lookupDescription"
        :inputClass="errors.length > 0 ? 'is-invalid' : ''"
    >
      <template slot="append">
        <div class="input-group-append">
          <button v-on:click="clearDescription" class="btn btn-outline-secondary" type="button"><i class="far fa-trash-alt"></i></button>
        </div>
      </template>
    </vue-typeahead-bootstrap>

    <span v-if="errors.length > 0">
      <span v-for="error in errors" class="text-danger small">{{ error }}<br/></span>
    </span>

  </div>
</template>

<script>
import VueTypeaheadBootstrap from 'vue-typeahead-bootstrap';
import {debounce} from "lodash";
import {createNamespacedHelpers} from "vuex";

const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('transactions/create')
export default {
  props: ['value', 'errors'],
  name: "TransactionGroupTitle",
  components: {VueTypeaheadBootstrap},
  data() {
    return {
      descriptions: [],
      initialSet: []
    }
  },

  created() {
    axios.get(this.getACURL(''))
        .then(response => {
          this.descriptions = response.data;
          this.initialSet = response.data;
        });
  },
  watch: {
    value: function (value) {
      //console.log('set');
      this.setGroupTitle({groupTitle: value});
    }
  },
  methods: {
    ...mapMutations(
        [
          'setGroupTitle'
        ],
    ),
    ...mapGetters(
        [
          'groupTitle'
        ]
    ),
    clearDescription: function () {
      this.setGroupTitle({groupTitle: ''});
      this.value = '';
    },
    getACURL: function (query) {
      // update autocomplete URL:
      return document.getElementsByTagName('base')[0].href + 'api/v1/autocomplete/transactions?query=' + query;
    },
    lookupDescription: debounce(function () {
      // update autocomplete URL:
      axios.get(this.getACURL(this.value))
          .then(response => {
            this.descriptions = response.data;
          })
    }, 300)
  }
}
</script>

<style scoped>

</style>