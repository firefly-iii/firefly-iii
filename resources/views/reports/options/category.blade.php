<div class="form-group">
    <label for="inputCategories" class="col-sm-3 control-label">{{ __('firefly.select_category') }}</label>
    <div class="col-sm-9">
        <select id="inputCategories" name="category[]" multiple="multiple" class="form-control">
            @foreach($categories as $category)
                <option value="{{ $category->id }}" label="{{ $category->name }}">{{ $category->name }}</option>
            @endforeach
        </select>

    </div>
</div>
