<template>
    <div class="form-group" v-bind:class="{ 'has-error': hasError()}">
        <div class="col-sm-12">
            <input
                    type="text"
                    class="form-control"
                    name="group_title"
                    title="Description of the split transaction"
                    ref="descr"
                    autocomplete="off"
                    placeholder="Description of the split transaction"
                    :value="value" @input="handleInput"
            >
            <p class="help-block" v-if="error.length === 0">
                If you create a split transaction, there must be a global description for all splits
                of the transaction.
            </p>
            <ul class="list-unstyled" v-for="error in this.error">
                <li class="text-danger">{{ error }}</li>
            </ul>
        </div>
    </div>
</template>

<script>
    export default {
        props: ['error', 'value', 'index'],
        name: "GroupDescription",
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