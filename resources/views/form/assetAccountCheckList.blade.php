@foreach($grouped as $groupName => $accounts)
    <div class="mb-3">
        <div class="row" id="{{ $name }}_holder">
            <div class="col-sm-3">{{ $groupName }}</div>
            <div class="col-sm-9">
                @foreach($accounts as $id => $account)
                    <div class="form-check has-validation">
                        {{-- {% if account in selected or (selected|length == 0 and options.select_all == true) %} --}}
                        @if(in_array($id, $selected) || (count($selected) == 0 && $options['select_all'] === true))
                            {{ Html::checkbox($name . '[]', true, $id)->class('form-check-input')->id($id) }}
                        @else
                            {{ Html::checkbox($name . '[]', false, $id)->class('form-check-input')->id($id) }}
                        @endif
                        <label class="form-check-label" for="{{ $id }}">
                            {{ $account }}
                        </label>

                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endforeach
