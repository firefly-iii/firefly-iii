<div class="row mb-3" id="{{ $name }}_holder">
    <div class="input-group has-validation">
        <label for="{{ $options['id'] }}" class="col-sm-3 col-form-label has-validation">{{ $label }}</label>
        <div class="col-sm-9">
            {{ Html::multiselect($name . '[]', $list, $selected)->id($options['id'])->attribute('size', 12)->class('form-select')->attribute('autocomplete','off')->attribute('spellcheck','false')->attribute('placeholder', $options['placeholder'] ?? '')  }}
            @include('form.feedback')
            @include('form.help')
        </div>
    </div>
</div>
