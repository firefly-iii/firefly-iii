<template>
    <div class="form-group" v-bind:class="{ 'has-error': hasError()}">
        <div class="col-sm-12">
            <input
                    type="date"
                    class="form-control"
                    name="date[]"
                    title="Date"
                    ref="date"
                    autocomplete="off"
                    :disabled="index > 0"
                    placeholder="Date"
                    :value="value" @input="handleInput"
            >
            <ul class="list-unstyled" v-for="error in this.error">
                <li class="text-danger">{{ error }}</li>
            </ul>
        </div>
    </div>
</template>

<script>
    export default {
        props: ['error', 'value', 'index'],
        name: "StandardDate",
        methods: {
            hasError: function () {
                return this.error.length > 0;
            },
            handleInput(e) {
                this.$emit('input', this.$refs.date.value);
            }
        }
    }
</script>

<style scoped>

</style>