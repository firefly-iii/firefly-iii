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
                  class="btn btn-secondary btn-sm"
                  @click="togglePopover({ placement: 'auto-start', positionFixed:true })"
              ><i class="fas fa-calendar-alt"></i></button>
              <button
                  class="btn btn-secondary"
              ><i class="fas fa-history"></i></button>


              <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true"
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
export default {
  name: "Calendar",
  created() {
    // this.locale = localStorage.locale ?? 'en-US';
    // this.$store.commit('increment');
    // console.log(this.$store.state.count);
    // get dates for current period (history button):
    // get dates for optional periods (dropdown) + descriptions.
  },
  data() {
    return {
      locale: 'en-US',
      range: {
        start: new Date(window.sessionStart),
        end: new Date(window.sessionEnd),
      },
      defaultRange: {
        start: new Date(window.sessionStart),
        end: new Date(window.sessionEnd),
      },
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