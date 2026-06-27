<div class="form-group">
    <label for="inputBudgets" class="col-sm-3 control-label">{{ __('firefly.select_budget') }}</label>
    <div class="col-sm-9">
        <select id="inputBudgets" name="budget[]" multiple="multiple" class="form-control">
            @foreach($budgets as $budget)
                <option value="{{ $budget->id }}" label="{{ $budget->name }}">{{ $budget->name }}</option>
            @endforeach
        </select>
    </div>
</div>
