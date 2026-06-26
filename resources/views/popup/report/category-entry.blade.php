<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title"
                id="expenseEntryTitle">{{ trans('firefly.category_entry', {name: category.name, start: $start->isoFormat($monthAndDayFormat), end: $end->isoFormat($monthAndDayFormat)}) }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('firefly.close') }}"></button>
        </div>
        <div class="modal-body">
            {% set hideCategory = true %}
            {% include 'popup.list.journals' %}
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('firefly.close') }}</button>
        </div>
    </div>
</div>
