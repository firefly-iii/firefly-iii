<!--
  - CustomTransactionFields.vue
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

</template>

<script>
    export default {
        name: "CustomTransactionFields",
        mounted() {
            this.getPreference();
        },
        methods: {
            getPreference() {

                const url = document.getElementsByTagName('base')[0].href + 'api/v1/preferences/transaction_journal_optional_fields';

                axios.get(url).then(response => {
                    // TODO here we are.
                    //console.log(response.data.data.attributes);
                }).catch(() => console.warn('Oh. Something went wrong'));
            },
        }
    }
</script>

<style scoped>

</style>