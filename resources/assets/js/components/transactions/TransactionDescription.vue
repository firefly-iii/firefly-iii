<template>
    <div class="form-group" v-bind:class="{ 'has-error': hasError()}">
        <div class="col-sm-12">
            <input
                    type="text"
                    class="form-control"
                    name="description[]"
                    title="Description"
                    ref="descr"
                    autocomplete="off"
                    placeholder="Description"
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
        name: "TransactionDescription",
        methods: {
            hasError: function () {
                return this.error.length > 0;
            },
            handleInput(e) {
                this.$emit('input', this.$refs.descr.value);
            }
        }
    }
</script>

<style scoped>

</style>