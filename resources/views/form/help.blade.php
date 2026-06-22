@if(array_key_exists('helpText', $options) && '' !== $options['helpText'])
    <p class="form-text"{{ $options['helpText'] }}</p>
@endif
