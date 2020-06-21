
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
