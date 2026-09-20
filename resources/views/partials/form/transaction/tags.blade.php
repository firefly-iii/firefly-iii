<div class="row mb-2">
    <label :for="'tags_' + index"
           class="col-sm-1 col-form-label d-none d-sm-block">
        <em title="{{ __('firefly.tags') }}" class="bi bi-tag"></em>
    </label>
    <div class="col-sm-10">
        <select
            class="form-select ac-tags"
            :id="'tags_' + index"
            :name="'tags['+index+'][]'"
            x-model="transaction.tags"
            multiple>
            <option value="">{{ __('firefly.select_tag') }}</option>

        </select>

    </div>
</div>

<!--
<div class="row mb-2">
    <label :for="'tags_' + index"
           class="col-sm-1 col-form-label d-none d-sm-block">
        <em title="{{ __('firefly.tags') }}" class="bi bi-tag"></em>
    </label>
    <template x-for="(currentTag, index) in transaction.tags" :key="index">
                <option :value="currentTag" x-text="currentTag" selected="selected"></option>
            </template>
    <div class="col-sm-10">
        <select
            class="form-select ac-tags"
            :id="'tags_' + index"
            :name="'tags['+index+'][]'"
            x-model="transaction.tags"
            multiple>
            <option value="">{{ __('firefly.select_tag') }}</option>
            <template x-for="(currentValue, index) in transaction.tags" :key="index">
                <option :label="index" :value="index" x-text="index" selected="selected"></option>
            </template>
        </select>

    </div>
</div>

<option value="currentValue" x-text="currentValue" selected="selected"></option>
 :value="tag"
<template x-for="(tag, index) in transaction.tags" :key="index">
                <option value="bla">bla</option>
            </template>

 :value="tag"
<template x-for="(tag, index) in transaction.tags" :key="index">
                <option value="bla">bla</option>
            </template>
 -->
