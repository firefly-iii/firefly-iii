<!--
  - GenericLocation.vue
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
  <div class="form-group" v-if="enableExternalMap">
    <div class="text-xs d-none d-lg-block d-xl-block">
      {{ title }}
    </div>
    <div style="width:100%;height:300px;">
      <LMap
          ref="myMap"
          :center="center"
          :zoom="zoom" style="width:100%;height:300px;"
          @ready="prepMap"
          @update:zoom="zoomUpdated"
          @update:center="centerUpdated"
          @update:bounds="boundsUpdated"
      >
        <l-tile-layer :url="url"></l-tile-layer>
        <l-marker :lat-lng="marker" :visible="hasMarker"></l-marker>
      </LMap>
      <span>
        <button class="btn btn-default btn-xs" @click="clearLocation">{{ $t('firefly.clear_location') }}</button>
      </span>
    </div>
    <p>&nbsp;</p>
    <span v-if="errors.length > 0">
      <span v-for="error in errors" class="text-danger small">{{ error }}<br/></span>
    </span>
  </div>
</template>

<script>

// If you need to reference 'L', such as in 'L.icon', then be sure to
// explicitly import 'leaflet' into your component
// import L from 'leaflet';


// OLD
// import {LMap, LMarker, LTileLayer} from 'vue2-leaflet';
// import 'leaflet/dist/leaflet.css';
//
// import L from 'leaflet';
//
// delete L.Icon.Default.prototype._getIconUrl;
//
// L.Icon.Default.mergeOptions({
//                               iconRetinaUrl: require('leaflet/dist/images/marker-icon-2x.png'),
//                               iconUrl: require('leaflet/dist/images/marker-icon.png'),
//                               shadowUrl: require('leaflet/dist/images/marker-shadow.png')
//                             });


import {LMap, LMarker, LTileLayer} from 'vue2-leaflet';
import 'leaflet/dist/leaflet.css';

export default {
  name: "GenericLocation",
  components: {LMap, LTileLayer, LMarker,},
  props: {
    title: {},
    disabled: {
      type: Boolean,
      default: false
    },
    value: {
      type: Object,
      required: true,
      default: function () {
        return {
          // some defaults here.
        };
      }
    },
    errors: {},
    customFields: {},
  },
  data() {
    return {
      availableFields: this.customFields,
      url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
      zoom: 3,
      center: [0, 0],
      bounds: null,
      map: null,
      enableExternalMap: false,
      hasMarker: false,
      marker: [0, 0],
    }
  },
  created() {
    // enable_external_map
    this.verifyMapEnabled();
  },
  methods: {
    verifyMapEnabled: function () {
      axios.get('./api/v1/configuration/firefly.enable_external_map').then(response => {
        this.enableExternalMap = response.data.data.value;
        if (true === this.enableExternalMap) {
          this.loadMap();
        }

      });
    },
    loadMap: function () {
      if (null === this.value || typeof this.value === 'undefined' || 0 === Object.keys(this.value).length) {
        axios.get('./api/v1/configuration/firefly.default_location').then(response => {
          this.zoom = parseInt(response.data.data.value.zoom_level);
          this.center =
              [
                parseFloat(response.data.data.value.latitude),
                parseFloat(response.data.data.value.longitude),
              ]
          ;
        });
        return;
      }
      if (null !== this.value.zoom_level && null !== this.value.latitude && null !== this.value.longitude) {
        this.zoom = this.value.zoom_level;
        this.center = [
          parseFloat(this.value.latitude),
          parseFloat(this.value.longitude),
        ];
        this.hasMarker = true;
      }
    },
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
    clearLocation: function (e) {
      e.preventDefault();
      this.hasMarker = false;
      this.emitEvent();
    },
    emitEvent() {
      this.$emit('set-field', {
                   field: "location",
                   value: {
                     zoomLevel: this.zoom,
                     lat: this.marker[0],
                     lng: this.marker[1],
                     hasMarker: this.hasMarker
                   }
                 }
      );
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
}
</script>

<style scoped>

</style>