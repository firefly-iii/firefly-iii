<template>
    <div class="form-group"
         v-bind:class="{ 'has-error': hasError()}"
    >
        <div class="col-sm-12 text-sm">
            {{ title }}
        </div>
        <div class="col-sm-12">
            <input type="date" class="form-control" :name="name"
                   :title="title" autocomplete="off"
                   ref="date"
                   :value="value" @input="handleInput"
                   :placeholder="title">
            <ul class="list-unstyled" v-for="error in this.error">
                <li class="text-danger">{{ error }}</li>
            </ul>
        </div>
    </div>
</template>

<script>
    export default {
        name: "CustomDate",
        props: {
            value: String,
            title: String,
            name: String,
            error: Array,
        },
        methods: {
            handleInput(e) {
                this.$emit('input', this.$refs.date.value);
            },
            hasError: function () {
                return this.error.length > 0;
            },
        }
    }
</script>

<style scoped>

</style>