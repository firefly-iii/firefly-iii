<!--
  - Tags.vue
  - Copyright (c) 2019 james@firefly-iii.org
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
  <div class="form-group"
       v-bind:class="{ 'has-error': hasError()}">
    <div class="col-sm-12 text-sm">
      {{ $t('firefly.tags') }}
    </div>
    <div class="col-sm-12">
      <div class="input-group">
        <vue-tags-input
            v-model="tag"
            :add-only-from-autocomplete="false"
            :autocomplete-items="autocompleteItems"
            :tags="tags"
            :title="$t('firefly.tags')"
            class="force-background-tags-input"
            v-bind:placeholder="$t('firefly.tags')"
            @tags-changed="update"
        />
        <span class="input-group-btn">
                <button
                    class="btn btn-default"
                    tabIndex="-1"
                    type="button"
                    v-on:click="clearTags"><i class="fa fa-trash-o"></i></button>
                </span>
      </div>
    </div>
    <ul v-for="error in this.error" class="list-unstyled">
      <li class="text-danger">{{ error }}</li>
    </ul>
  </div>
</template>

<script>
import axios from 'axios';
import VueTagsInput from '@johmun/vue-tags-input';

export default {
  name: "Tags",
  components: {
    VueTagsInput
  },
  props: ['value', 'error'],
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
    update(newTags) {
      // console.log('update', newTags);
      this.autocompleteItems = [];
      this.tags = newTags;
      this.$emit('input', this.tags);
    },
    clearTags() {
      // console.log('clearTags');
      this.tags = [];
      this.$emit('input', this.tags);

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
