<div class="form-group">
    <label for="inputDoubleAccounts" class="col-sm-3 control-label">{{ __('firefly.select_expense_revenue') }}</label>
    <div class="col-sm-9">
        <select id="inputDoubleAccounts" name="double[]" multiple="multiple" class="form-control">
            @foreach($set as $account)
                <option value="{{ $account->id }}" label="{{ $account->name }}@if($account->iban !='') ({{ $account->iban }})@endif">{{ $account->name }}@if($account->iban !='') ({{ $account->iban }})@endif</option>
            @endforeach
        </select>
    </div>
</div>
