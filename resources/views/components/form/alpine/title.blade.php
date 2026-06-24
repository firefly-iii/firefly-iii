<div class="form-group" x-bind:class="{ 'has-error': hasError()}">
    <label class="col-sm-4 control-label">
        {{ __('form.title') }}
    </label>
    <div class="col-sm-8">
        <div class="input-group">
            <input
                ref="title"
                title="{{ __('form.title') }}"
                x-model={{ $value }}
                autocomplete="off"
                class="form-control"
                name="title"
                type="text"
                @input="handleInput"
                placeholder="{{ __('form.title') }}"
            >
            <span class="input-group-btn">
            <button
                class="btn btn-default"
                tabIndex="-1"
                type="button"
                x-on:click="clearTitle"><i class="fa fa-trash-o"></i></button>
        </span>
        </div>
        <template x-for="error in this.error">
            <ul class="list-unstyled">
                <li class="text-danger" x-text="error"></li>
            </ul>
        </template>
    </div>
</div>
