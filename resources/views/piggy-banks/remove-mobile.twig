@extends('layout.v3.session')

    {{ Breadcrumbs.render(Route.getCurrentRoute.getName, piggyBank) }}
@endsection

@section('content')
    <form id="remove" class="form-horizontal" action="{{ route('piggy-banks.remove', piggyBank.id) }}" method="POST">
        <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ trans('firefly.remove_money_from_piggy_title', {name: piggyBank.name}) }}</h3>
                    </div>
                    <div class="card-body">

                        {% for account in accounts %}
                            <p>
                                {{  account.account.name }}: {{ 'max_amount_remove'|_ }}: {{ formatAmountByCurrency(piggyBank.transactionCurrency, account.saved_so_far) }}.
                            </p>
                            <div class="input-group">
                                <div class="input-group-addon">{{ piggyBank.transactionCurrency.symbol|raw }}</div>
                                <input step="any" class="form-control" id="amount_{{ account.account.id }}" autocomplete="off" name="amount[{{ account.account.id }}]" max="{{ account.saved_so_far }}"
                                       type="number">
                            </div>
                        @endforeach

                        <p>
                            &nbsp;
                        </p>
                        <button type="submit" class="btn btn-success text-end">
                            {{ 'remove'|_ }}
                        </button>


                    </div>
                </div>
            </div>

        </div>
    </form>
@endsection
