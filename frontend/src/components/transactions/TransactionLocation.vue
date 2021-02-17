<!--
  - TransactionLocation.vue
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
  <div class="form-group" v-if="showField">
    <div class="text-xs d-none d-lg-block d-xl-block">
      {{ $t('firefly.location') }}
    </div>
    <div style="width:100%;height:300px;">
      <l-map
          style="width:100%;height:300px;"
          :zoom="zoom"
          ref="myMap" @ready="prepMap()"
          :center="center"
          @update:zoom="zoomUpdated"
          @update:center="centerUpdated"
          @update:bounds="boundsUpdated"
      >
        <l-tile-layer :url="url"></l-tile-layer>
        <l-marker :lat-lng="marker" :visible="hasMarker"></l-marker>
      </l-map>
      <span>
        <button class="btn btn-default btn-xs" @click="clearLocation">{{ $t('firefly.clear_location') }}</button>
      </span>
    </div>
  </div>
</template>

<script>

// If you need to reference 'L', such as in 'L.icon', then be sure to
// explicitly import 'leaflet' into your component
// import L from 'leaflet';
import {LMap, LMarker, LTileLayer} from 'vue2-leaflet';
import 'leaflet/dist/leaflet.css';

import L from 'leaflet';

delete L.Icon.Default.prototype._getIconUrl;

L.Icon.Default.mergeOptions({
                              iconRetinaUrl: require('leaflet/dist/images/marker-icon-2x.png'),
                              iconUrl: require('leaflet/dist/images/marker-icon.png'),
                              shadowUrl: require('leaflet/dist/images/marker-shadow.png')
                            });

export default {
  name: "TransactionLocation",
  props: ['index', 'value', 'errors', 'customFields'],
  components: {
    LMap,
    LTileLayer,
    LMarker,
  },
  created() {
    axios.get('./api/v1/configuration/static/firefly.default_location').then(response => {
      this.zoom = parseInt(response.data['firefly.default_location'].zoom_level);
      this.center =
          [
            parseFloat(response.data['firefly.default_location'].latitude),
            parseFloat(response.data['firefly.default_location'].longitude),
          ]
      ;
    });
  },
  data() {
    return {
      availableFields: this.customFields,
      url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
      zoom: 3,
      center: [0, 0],
      bounds: null,
      map: null,
      hasMarker: false,
      marker: [0, 0],
    }
  },
  methods: {
    prepMap: function () {
      this.map = this.$refs.myMap.mapObject;
      this.map.on('contextmenu', this.setObjectLocation);
      this.map.on('zoomend', this.saveZoomLevel);
    },
    setObjectLocation: function (event) {
      this.marker = [event.latlng.lat, event.latlng.lng];
      this.hasMarker = true;
      this.emitEvent();
    },
    saveZoomLevel: function () {
      this.emitEvent();
    },
    clearLocation: function () {
      this.hasMarker = false;
      this.emitEvent();
    },
    emitEvent() {
      this.$emit('set-marker-location', {zoomLevel: this.zoom, lat: this.marker[0], lng: this.marker[1], hasMarker: this.hasMarker});
    },
    zoomUpdated(zoom) {
      this.zoom = zoom;
    },
    centerUpdated(center) {
      this.center = center;
    },
    boundsUpdated(bounds) {
      this.bounds = bounds;
    }
  },
  computed: {
    showField: function () {
      if ('location' in this.availableFields) {
        return this.availableFields.location;
      }
      return false;
    }
  },
  watch: {
    customFields: function (value) {
      this.availableFields = value;
    },
  }
}
</script>

<style scoped>

</style>