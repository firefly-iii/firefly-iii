<div class="form-group">
    <label for="inputTags" class="col-sm-3 control-label">{{ __('firefly.select_tag') }}</label>
    <div class="col-sm-9">
        <select id="inputTags" name="tag[]" multiple="multiple" class="form-control">
            @foreach($tags as $year)
            <optgroup label="{{ $year['year'] }}">
                @foreach($year['tags'] as $tag)
                    <option value="{{ $tag->id }}" label="{{ e($tag->tag) }}">{{ e($tag->tag) }}</option>
                @endforeach
            @endforeach
        </select>
    </div>
</div>
