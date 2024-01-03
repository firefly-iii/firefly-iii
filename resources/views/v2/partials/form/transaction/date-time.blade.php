<div class="row mb-3">
    <label for="date_0" class="col-sm-1 col-form-label d-none d-sm-block">
        <i class="fa-solid fa-calendar"></i>
    </label>
    <div class="col-sm-10">
        <input type="datetime-local" class="form-control" :id="'date_' + index"
               @change="detectTransactionType"
               x-model="transaction.date"
        >
    </div>
</div>
