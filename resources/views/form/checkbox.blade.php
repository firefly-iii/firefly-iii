<div class="row mb-3" id="{{ $name }}_holder">
    <div class="col-sm-9 offset-sm-3">
        <div class="form-check has-validation">
            <!-- <input class="form-check-input" type="checkbox" id="gridCheck1"> -->
            {{ Html::checkbox($name, $options['checked'], $value)->class($inputClasses)->id($options['id']) }}
            <label class="form-check-label" for="{{ $options['id'] }}">
                {{ $label }}
            </label>
            @if(array_key_exists('small', $options) && true === $options['small'])
                TODO SMALL!!!!!!
            @endif
            @include('form.help')
            @include('form.feedback')
        </div>
    </div>
</div>
