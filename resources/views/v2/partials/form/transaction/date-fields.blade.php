@foreach($optionalDateFields as $name => $enabled)
    @if($enabled)
        <div class="row mb-1">
            <label :for="'{{ $name }}_' + index"
                   class="col-sm-1 col-form-label d-none d-sm-block">
                <em class="fa-solid fa-calendar-alt" title="{{ __('firefly.pref_optional_tj_' . $name) }}"></em>
            </label>
            <div class="col-sm-10">
                <input type="date"
                       class="form-control"
                       :id="'{{ $name }}_' + index"
                       x-model="transaction.{{ $name }}"
                       :data-index="index"
                       placeholder="">
            </div>
        </div>
    @endif
@endforeach
