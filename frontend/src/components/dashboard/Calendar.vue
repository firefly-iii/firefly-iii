<!--
  - Calendar.vue
  - Copyright (c) 2020 james@firefly-iii.org
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
  <div>
    <div class="row">
      <div class="col">Start</div>
      <div class="col-8">{{ new Intl.DateTimeFormat(locale, {year: 'numeric', month: 'long', day: 'numeric'}).format(range.start) }}</div>
    </div>
    <div class="row">
      <div class="col">End</div>
      <div class="col-8">{{ new Intl.DateTimeFormat(locale, {year: 'numeric', month: 'long', day: 'numeric'}).format(range.end) }}</div>
    </div>
    <date-picker
        v-model="range"
        mode="date"
        rows="2"
        is-range
    >
      <template v-slot="{ inputValue, inputEvents, isDragging, togglePopover }">
        <div class="row">
          <div class="col">
            <div class="btn-group btn-group-sm d-flex">
              <button
                  class="btn btn-secondary btn-sm" :title="$t('firefly.custom_period')"
                  @click="togglePopover({ placement: 'auto-start', positionFixed:true })"
              ><i class="fas fa-calendar-alt"></i></button>
              <button
                  class="btn btn-secondary"
                  :title="$t('firefly.reset_to_current')"
              ><i class="fas fa-history"></i></button>


              <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true"
                      :title="$t('firefly.select_period')"
                      aria-expanded="false">
                <i class="fas fa-list"></i>
              </button>
              <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                <a class="dropdown-item" href="#">(prev period)</a>
                <a class="dropdown-item" href="#">(next period)</a>
                <a class="dropdown-item" href="#">(this week?)</a>
              </div>

            </div>
            <input type="hidden"
                   :class="isDragging ? 'text-gray-600' : 'text-gray-900'"
                   :value="inputValue.start"
                   v-on="inputEvents.start"
            />
            <input type="hidden"
                   :class="isDragging ? 'text-gray-600' : 'text-gray-900'"
                   :value="inputValue.end"
                   v-on="inputEvents.end"
            />
          </div>
        </div>
      </template>
    </date-picker>
  </div>
</template>

<script>
import {createNamespacedHelpers} from "vuex";

const {mapState, mapGetters, mapActions, mapMutations} = createNamespacedHelpers('dashboard/index')

export default {
  name: "Calendar",
  created() {
    this.ready = true;
    this.locale = localStorage.locale ?? 'en-US';
  },

  computed: {
    ...mapGetters([
                    'viewRange',
                    'start',
                    'end'
                  ]),
    'datesReady': function () {
      return null !== this.start && null !== this.end && this.ready;
    }
  },
  watch: {
    datesReady: function (value) {
      if (true === value) {
        this.range.start = new Date(this.start);
        this.range.end = new Date(this.end);
      }
    },
  },
  data() {
    return {
      locale: 'en-US',
      ready: false,
      range: {
        start: new Date,
        end: new Date,
      }
    };
  },
}
</script>

<style scoped>
.dropdown-item {
  color: #212529;
}

.dropdown-item:hover {
  color: #212529;
}
</style>