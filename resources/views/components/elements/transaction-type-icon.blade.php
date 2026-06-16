<!-- TODO icon helper -->
@if('Withdrawal' === $type)
    <span class="bi bi-arrow-left" title="{{ trans('firefly.Withdrawal') }}"></span>
@endif

@if('Deposit' === $type)
    <span class="bi bi-arrow-right" title="{{ trans('firefly.Deposit') }}"></span>
@endif

@if('Transfer' === $type)
    <span class="bi bi-arrow-left-right" title="{{ trans('firefly.Transfer') }}"></span>
@endif

@if('Reconciliation' === $type)
    <span class="bi bi-calculator" title="{{ trans('firefly.reconciliation_transaction') }}"></span>
@endif
@if('Opening balance' === $type)
    <span class="bi bi-star" title="{{ trans('firefly.Opening balance') }}"></span>
@endif
@if('Liability credit' === $type)
    <span class="bi bi-star" title="{{ trans('firefly.Liability credit') }}"></span>
@endif
