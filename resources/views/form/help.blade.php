@if(array_key_exists('helpText', $options) && '' !== $options['helpText'])
    <p class="help-block">{{ $options['helpText'] }}</p>
@endif
