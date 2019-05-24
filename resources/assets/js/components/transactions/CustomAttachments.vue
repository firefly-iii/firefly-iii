<template>
    <div class="form-group"
         v-bind:class="{ 'has-error': hasError()}"
    >
        <div class="col-sm-12 text-sm">
            {{ title }}
        </div>
        <div class="col-sm-12">
            <input multiple="multiple"
                   autocomplete="off"
                   :placeholder="title"
                   :title="title"
                   :name="name" type="file" class="form-control">
            <ul class="list-unstyled" v-for="error in this.error">
                <li class="text-danger">{{ error }}</li>
            </ul>
        </div>
    </div>
</template>

<script>
    export default {
        name: "CustomAttachments",
        props: {
            title: String,
            name: String,
            error: Array
        },
        methods: {
            hasError: function () {
                return this.error.length > 0;
            },
        }
    }
</script>

<style scoped>

</style>