<template>

</template>

<script>
    export default {
        name: "DataConverter",
        data() {
            return {
                dataSet: null,
                newDataSet: null,
            }
        },
        methods: {
            convertChart(dataSet) {
                this.dataSet = dataSet;
                this.newDataSet = {
                    count: 0,
                    labels: [],
                    datasets: []
                }
                this.getLabels();
                this.getDataSets();
                this.newDataSet.count = this.newDataSet.datasets.length;
                return this.newDataSet;
            },
            getLabels() {
                let firstSet = this.dataSet[0];
                for (const entryLabel in firstSet.entries) {
                    if (firstSet.entries.hasOwnProperty(entryLabel)) {
                        this.newDataSet.labels.push(entryLabel);
                    }
                }
            },
            getDataSets() {
                for (const setKey in this.dataSet) {
                    if (this.dataSet.hasOwnProperty(setKey)) {
                        let newSet = {};
                        let oldSet = this.dataSet[setKey];
                        newSet.label = oldSet.label;
                        newSet.type = oldSet.type;
                        newSet.currency_symbol = oldSet.currency_symbol;
                        newSet.yAxisID = oldSet.yAxisID;
                        newSet.data = [];
                        for (const entryLabel in oldSet.entries) {
                            if (oldSet.entries.hasOwnProperty(entryLabel)) {
                                newSet.data.push(oldSet.entries[entryLabel]);
                            }
                        }
                        this.newDataSet.datasets.push(newSet);
                    }
                }
            }
        }
    }
</script>

<style scoped>

</style>
