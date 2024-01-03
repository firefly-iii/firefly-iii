<template x-for="dateField in dateFields">
    <div class="row mb-1">
        <label :for="dateField + '_date_' + index"
               class="col-sm-1 col-form-label d-none d-sm-block">
            <i class="fa-solid fa-calendar-alt" :title="dateField"></i>
        </label>
        <div class="col-sm-10">
            <input type="date"
                   class="form-control"
                   :id="dateField + '_date_' + index"
                   x-model="transaction[dateField]"
                   :data-index="index"
                   placeholder="">
        </div>
    </div>
</template>
