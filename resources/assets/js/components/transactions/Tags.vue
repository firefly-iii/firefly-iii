<!--
  - Tags.vue
  - Copyright (c) 2019 thegrumpydictator@gmail.com
  -
  - This file is part of Firefly III.
  -
  - Firefly III is free software: you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation, either version 3 of the License, or
  - (at your option) any later version.
  -
  - Firefly III is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="form-group"
         v-bind:class="{ 'has-error': hasError()}"
    >
        <div class="col-sm-12">
            <vue-tags-input
                    v-model="tag"
                    :tags="tags"
                    classes="form-input"
                    :autocomplete-items="autocompleteItems"
                    :add-only-from-autocomplete="false"
                    @tags-changed="update"
                    placeholder="Tags"
            />
            <ul class="list-unstyled" v-for="error in this.error">
                <li class="text-danger">{{ error }}</li>
            </ul>
        </div>
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
        props: ['value','error'],
        data() {
            return {
                tag: '',
                tags: [],
                autocompleteItems: [],
                debounce: null,
            };
        },
        watch: {
            'tag': 'initItems',
        },
        methods: {
            update(newTags) {
                this.autocompleteItems = [];
                this.tags = newTags;
                this.$emit('input', this.tags);
            },
            hasError: function () {
                return this.error.length > 0;
            },
            initItems() {
                if (this.tag.length < 2) {
                    return;
                }
                const url = document.getElementsByTagName('base')[0].href + `json/tags?search=${this.tag}`;

                clearTimeout(this.debounce);
                this.debounce = setTimeout(() => {
                    axios.get(url).then(response => {
                        this.autocompleteItems = response.data.map(a => {
                            return {text: a.tag};
                        });
                    }).catch(() => console.warn('Oh. Something went wrong'));
                }, 600);
            },
        },
    }
</script>

<style scoped>

</style>