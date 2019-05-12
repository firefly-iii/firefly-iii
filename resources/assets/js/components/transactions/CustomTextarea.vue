<template>
    <div class="form-group">
        <div class="col-sm-12 text-sm">
            {{ title }}
        </div>
        <div class="col-sm-12">
            <textarea class="form-control"  :name="name"
                      :title="title" autocomplete="off"
                      ref="str"
                      :value="value" @input="handleInput"
                      :placeholder="title" ></textarea>
        </div>
    </div>
</template>

<script>
    export default {
        name: "CustomTextarea",
        props: {
            title: String,
            name: String,
            value: String
        },
        methods: {
            handleInput(e) {
                this.$emit('input', this.$refs.str.value);
            }
        }
    }
</script>

<style scoped>

</style>