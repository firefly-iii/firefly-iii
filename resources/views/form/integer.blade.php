<div class="row mb-3" id="{{ $name }}_holder">
    <div class="input-group has-validation">
        <label for="{{ $options['id'] }}" class="col-sm-3 col-form-label has-validation">{{ $label }}</label>

        <div class="col-sm-9">
        {{ Html::input('number', $name, $value)->id($options['id'])->attribute('step',$options['step'])->attribute('autocomplete','off')->attribute('spellcheck', 'false')->class($errors->has($name) ? 'is-invalid form-control' : 'form-control')->attribute('placeholder',$options['placeholder']) }}
            @include('form.feedback')
        @include('form.help')
    </div>
</div>
</div>
