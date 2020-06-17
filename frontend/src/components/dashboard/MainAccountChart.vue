
<script>
    import { Line } from 'vue-chartjs'
    export default {
        extends: Line,
        props: ['options'],

        mounted () {
            // this.chartData is created in the mixin.
            // If you want to pass options please create a local options object
            this.renderChart(this.chartData, this.options)
        }
    }
</script>


<style scoped>

</style>
