<template>
    <div class="form-group">
        <div class="col-sm-12 text-sm">
            Custom Date
        </div>
        <div class="col-sm-12">
            <!-- v-model="transaction.date" -->
            <!-- :disabled="index > 0" -->
            <input type="date" class="form-control" name="date[]"
                   title="Custom Date" value="" autocomplete="off"
                   placeholder="Custom Date">
        </div>
    </div>
</template>

<script>
    export default {
        name: "CustomDate"
    }
</script>

<style scoped>

</style>