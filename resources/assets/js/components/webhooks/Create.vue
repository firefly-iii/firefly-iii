<!--
  - Create.vue
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
  <form accept-charset="UTF-8" class="form-horizontal" enctype="multipart/form-data">
    <input name="_token" type="hidden" value="xxx">
  <div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title">
            {{ $t('firefly.create_new_webhook') }}
          </h3>
        </div>
        <div class="box-body">
          <div class="row">
            <div class="col-lg-6">
              <Title value="Hello" url="something" :error="[]"></Title>
            </div>
            <div class="col-lg-6">
              B
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  </form>
</template>

<script>
import Title from "../form/Title";
export default {
  name: "Create",
  components: {Title},
  //     this.descriptionAutoCompleteURI = document.getElementsByTagName('base')[0].href + "api/v1/autocomplete/transactions?query=";

}
</script>

<style scoped>

</style>
