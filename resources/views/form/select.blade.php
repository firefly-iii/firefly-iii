<div class="row mb-3" id="{{ $name }}_holder">
    <div class="input-group has-validation">
        <label for="{{ $options['id'] }}" class="col-sm-3 col-form-label has-validation">{{ $label }}</label>
        <div class="col-sm-9">
            {{ Html::select($name, $list, $selected)->id($options['id'])->class($errors->has($name) ? 'is-invalid form-select' : 'form-select')->attribute('autocomplete','off')->attribute('spellcheck','false')->attribute('placeholder', $options['placeholder'] ?? '') }}
            @include('form.help')
            @include('form.feedback')
        </div>
    </div>
</div>
