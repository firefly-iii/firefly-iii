<div class="modal-dialog modal-lg">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">
                {{ __('firefly.overview_of_reconcile_modal') }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('firefly.close') }}"></button>
        </div>

        <form class="form-horizontal inline" id="income" action="{{ $route }}" method="POST">
            <div class="modal-body">
                <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
                <input type="hidden" name="start" value="{{ $start->format('Y-m-d') }}"/>
                <input type="hidden" name="end" value="{{ $start->format('Y-m-d') }}"/>
                <input type="hidden" name="startBalance" value="{{ $startBalance }}"/>
                <input type="hidden" name="endBalance" value="{{ $endBalance }}"/>
                @foreach($selectedIds as $id)
                    <input type="hidden" name="journals[]" value="{{ $id }}"/>
                @endforeach

                <table class="table table-striped table-bordered">
                    <tr>
                        <td>{{ __('firefly.submitted_start_balance') }} ({{ $start->isoFormat($monthAndDayFormat) }})</td>
                        <td>{!! format_amount_by_account($account, $startBalance) !!} </td>
                    </tr>
                    <tr>
                        <td>{{ trans('firefly.selected_transactions', ['count'=> count($selectedIds)]) }}</td>
                        <td>{!! format_amount_by_account($account, $amount) !!} </td>
                    </tr>
                    <tr>
                        <td>{{ trans('firefly.already_cleared_transactions', ['count' => $countCleared]) }}</td>
                        <td>{!! format_amount_by_account($account, $clearedAmount) !!} </td>
                    </tr>
                    <tr>
                        <td>{{ __('firefly.submitted_end_balance') }} ({{ $end->isoFormat($monthAndDayFormat) }})</td>
                        <td>{!! format_amount_by_account($account, $endBalance) !!} </td>
                    </tr>
                    <tr>
                        <td>{{ __('firefly.sum_of_reconciliation') }}</td>
                        <td>{!! format_amount_by_account($account, $reconSum) !!} </td>
                    </tr>

                    <tr>
                        <td>{{ __('firefly.difference') }}</td>
                        <td>
                            {!! format_amount_by_account($account, $difference) !!}
                            <input type="hidden" name="difference" value="{{ $difference }}"/>
                        </td>

                    </tr>
                </table>
                <p>
                    @if($diffCompare > 0)
                        {{ __('firefly.reconcile_has_more') }}
                    @endif
                    @if($diffCompare < 0)
                        {{ __('firefly.reconcile_has_less') }}
                    @endif
                </p>
                @if(0 === $diffCompare)
                    <p>
                        {{ __('firefly.reconcile_is_equal') }}
                    </p>
                    <input type="hidden" name="reconcile" value="nothing">
                @endif
                @if(0 !== $diffCompare)
                    <div class="form-group">
                        <div class="col-lg-12">
                            <div class="radio">
                                <label>
                                    <input type="radio" name="reconcile" value="create">
                                    @if($diffCompare > 0)
                                        {!! trans('firefly.create_neg_reconcile_transaction', ['amount' => format_amount_by_account($account, ($difference*-1))]) !!}
                                    @endif
                                    @if($diffCompare < 0)
                                        {!! trans('firefly.create_pos_reconcile_transaction', ['amount' => format_amount_by_account($account, ($difference*-1))]) !!}
                                    @endif
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-lg-12">
                            <div class="radio">
                                <label>
                                    <input type="radio" checked name="reconcile" value="nothing"> {{ __('firefly.reconcile_do_nothing') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <p>
                        {{ __('firefly.reconcile_go_back') }}
                    </p>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('firefly.close') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('firefly.confirm_reconciliation') }}</button>
            </div>
        </form>
    </div>
</div>

