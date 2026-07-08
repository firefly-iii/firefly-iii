<div class="mb-3">
    <template x-if="form.triggers.loading">
        <div class="row">
            <div class="text-center">
                <span class="form-control-plaintext">
                    <div class="spinner-border spinner-border-sm" role="status"><span
                            class="visually-hidden">Loading...</span></div>
                </span>
            </div>
        </div>
    </template>
    <template x-if="!form.triggers.loading">
        <div class="row">
            <label for="form_triggers" class="col-sm-3 col-form-label has-validation">{{ __('form.triggers') }}</label>
            <div class="col-sm-9">
                <select
                    id="form_triggers"
                    ref="triggers"
                    title="{{ __('form.title') }}"
                    x-model={{ $value }}
                    autocomplete="off"
                    x-bind:class="{'form-select': true, 'is-invalid': errors.triggers.length > 0}"
                    name="triggers[]"
                    {{ $multiple }}
                    @input="handleInput">
                    <template x-for="trigger in options.triggers">
                        <option :label="trigger.name" :selected="triggers.includes(trigger.id)" :value="trigger.id"
                                x-text="trigger.name"></option>
                    </template>
                </select>
            </div>
        </div>
    </template>
    <template x-for="error in errors.triggers">
        <div class="row">
            <div class="col-sm-9 offset-sm-3">
                <ul class="list-unstyled">
                    <li class="text-danger" x-text="error"></li>
                </ul>
            </div>
        </div>
    </template>
</div>
