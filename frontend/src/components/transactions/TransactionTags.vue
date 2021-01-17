<!--
  - TransactionTags.vue
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
      {{ $t('firefly.tags') }}
    </div>
    <div class="input-group">
      <vue-tags-input
          v-model="tag"
          :add-only-from-autocomplete="false"
          :autocomplete-items="autocompleteItems"
          :tags="tags"
          :title="$t('firefly.tags')"
          v-bind:placeholder="$t('firefly.tags')"
          @tags-changed="update"/>
    </div>
  </div>
</template>

<script>
import {createNamespacedHelpers} from "vuex";

const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('transactions/create')
import VueTagsInput from "@johmun/vue-tags-input";
import axios from "axios";

export default {
  name: "TransactionTags",
  components: {
    VueTagsInput
  },
  props: ['value', 'error', 'index'],
  data() {
    return {
      tag: '',
      autocompleteItems: [],
      debounce: null,
      tags: this.value,
    };
  },
  watch: {
    'tag': 'initItems',
  },
  methods: {
    ...mapMutations(
        [
          'updateField',
        ],
    ),
    update(newTags) {
      this.autocompleteItems = [];
      this.tags = newTags;
      // create array for update field thing:
      let shortList = [];
      for(let key in newTags) {
        if (newTags.hasOwnProperty(key)) {
          shortList.push(newTags[key].text);
        }
      }
      this.updateField({field: 'tags', index: this.index, value: shortList});
    },
    hasError: function () {
      return this.error.length > 0;
    },
    initItems() {
      // console.log('Now in initItems');
      if (this.tag.length < 2) {
        return;
      }
      const url = document.getElementsByTagName('base')[0].href + `api/v1/autocomplete/tags?query=${this.tag}`;

      clearTimeout(this.debounce);
      this.debounce = setTimeout(() => {
        axios.get(url).then(response => {
          this.autocompleteItems = response.data.map(a => {
            return {text: a.tag};
          });
        }).catch(() => console.warn('Oh. Something went wrong loading tags.'));
      }, 600);
    },
  },


}
</script>
<style>
.vue-tags-input {
  width: 100%;
  max-width: 100% !important;
  display: block;
  border-radius: 0.25rem;
}

.ti-input {
  border-radius: 0.25rem;
  max-width: 100%;
  width: 100%;
}

.ti-new-tag-input {
  font-size: 1rem;
}
</style>
