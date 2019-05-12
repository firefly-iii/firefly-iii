<template>
    <div class="form-group">
        <div class="col-sm-12 text-sm">
            {{ title }}
        </div>
        <div class="col-sm-12">
            <input multiple="multiple"
                   autocomplete="off"
                   :placeholder="title"
                   :title="title"
                   :name="name" type="file" class="form-control">
        </div>
    </div>
</template>

<script>
    export default {
        name: "CustomAttachments",
        props: {
            title: String,
            name: String
        },
    }
</script>

<style scoped>

</style>