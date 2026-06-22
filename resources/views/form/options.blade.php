@if('create' === $type)
    <div class="row mb-3" id="{{ $name }}_holder">
        <div class="col-sm-9 offset-sm-3">
            <div class="form-check has-validation">
                {{ Html::checkbox('create_another')->class('form-check-input')->id($name . '_return_to_form') }}
                <label class="form-check-label" for="{{ $name }}">
                    {{ trans('form.returnHereExplanation') }}
                </label>
            </div>
        </div>
    </div>
@endif
@if('update' === $type)
    <div class="row mb-3" id="{{ $name }}_holder">
        <div class="col-sm-9 offset-sm-3">
            <div class="form-check has-validation">
                {{ Html::checkbox('create_another')->class('form-check-input')->id($name . '_return_to_edit') }}

                <label for="{{ $name }}_return_to_edit" class="col-sm-4 control-label">
                    {{ trans('form.returnHere') }}
                    {{ trans('form.returnHereUpdateExplanation') }}
                </label>
            </div>
        </div>
    </div>
@endif

