@if($errors->has($name))
<div class="invalid-feedback">
    {{ $errors->first($name) }}
</div>
@endif
