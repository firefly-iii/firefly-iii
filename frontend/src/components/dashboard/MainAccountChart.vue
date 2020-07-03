
<!--
  - MainAccountChart.vue
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

<script>
    import {Line} from 'vue-chartjs'

    export default {
        extends: Line,
        props: ['options', 'chartData'],

        mounted() {
            // this.chartData is created in the mixin.
            // If you want to pass options please create a local options object
            this.renderChart(this.chartData, this.options)
        }
    }
</script>


<style scoped>

</style>
