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
          v-model="currentTag"
          :add-only-from-autocomplete="false"
          :autocomplete-items="autocompleteItems"
          :tags="tags"
          :title="$t('firefly.tags')"
          v-bind:placeholder="$t('firefly.tags')"
          @tags-changed="newTags => this.tags = newTags"
      />
    </div>
    <span v-if="errors.length > 0">
      <span v-for="error in errors" class="text-danger small">{{ error }}<br/></span>
    </span>
  </div>
</template>

<script>
import VueTagsInput from "@johmun/vue-tags-input";
import axios from "axios";

export default {
  name: "TransactionTags",
  components: {
    VueTagsInput
  },
  props: ['value', 'index', 'errors'],
  data() {
    return {
      autocompleteItems: [],
      debounce: null,
      tags: [],
      currentTag: '',
      updateTags: true, // the idea is that this is always true, except when the tags-function sets the value.
      tagList: this.value
    };
  },
  watch: {
    'currentTag': 'initItems',
    tagList: function (value) {
      this.$emit('set-tags', {field: 'tags', index: this.index, value: value});
      this.updateTags = false;
      this.tags = value;
    },
    tags: function (value) {
      if (this.updateTags) {
        let shortList = [];
        for (let key in value) {
          if (value.hasOwnProperty(key)) {
            shortList.push({text: value[key].text});
          }
        }
        this.tagList = shortList;
      }
      this.updateTags = true;
    }
  },
  methods: {
    initItems() {
      if (this.currentTag.length < 2) {
        return;
      }
      const url = document.getElementsByTagName('base')[0].href + `api/v1/autocomplete/tags?query=${this.currentTag}`;

      clearTimeout(this.debounce);
      this.debounce = setTimeout(() => {
        axios.get(url).then(response => {
          this.autocompleteItems = response.data.map(item => {
            return {text: item.tag};
          });
        }).catch(() => console.warn('Oh. Something went wrong loading tags.'));
      }, 300);
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
