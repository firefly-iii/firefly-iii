<tr data-count="{{ $count }}" class="rule-trigger-holder">
    <td class="forty-px">
        <a href="#" class="btn btn-danger btn-sm remove-trigger"><span class="bi bi-trash"></span></a>
    </td>
    <td class="thirty">
        <select name="triggers[{{ $count }}][type]" class="form-control">
            @foreach($triggers as $key => $type)
                <option value="{{ $key }}" label="{{ $type }}"
                        @if($oldTrigger === $key)
                            selected
                        @endif
                >{{ $type }}</option>
            @endforeach
        </select>
    </td>
    <td class="forty-px">
        <div class="checkbox">
            <label>
                <input type="checkbox" name="triggers[{{ $count }}][prohibited]" value="1"
                       @if($oldProhibited) checked @endif
                />
            </label>
        </div>
    </td>
    <td>
        <input autocomplete="off" type="text" value="{{ $oldValue }}" name="triggers[{{ $count }}][value]"
               class="form-control">
        @if($errors->has('triggers.' . $count . '.value'))
            <p class="text-danger">
                {{ $errors->first('triggers.' . $count . '.value') }}
            </p>
        @endif
    </td>
    <td class="twenty">
        <div class="checkbox">
            <label>
                <input type="checkbox" class="trigger-stop-processing" name="triggers[{{ $count }}][stop_processing]" value="1"
                       @if($oldChecked) checked @endif
                />
            </label>
        </div>
    </td>
</tr>
