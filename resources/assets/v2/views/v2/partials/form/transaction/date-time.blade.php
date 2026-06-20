<div class="row mb-3">
    <label :for="'date_' + index" class="col-sm-1 col-form-label d-none d-sm-block">
        <em title="{{ __('firefly.date_and_time') }}" class="fa-solid fa-calendar"></em>
    </label>
    <div class="col-sm-10">
        <input type="datetime-local" class="form-control" :id="'date_' + index"
               @change="changedDateTime"
               @keyup.enter="submitTransaction()"
               x-model="transaction.date"
        >
    </div>
</div>
