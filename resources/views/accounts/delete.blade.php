@extends('layout.v3.session')
@section('content')

    <form method="POST" action="{{ route('accounts.destroy', [$account->id]) }}" accept-charset="UTF-8"
          class="form-horizontal" id="destroy">
        <input name="_token" type="hidden" value="{{ csrf_token() }}">
        <div class="row">
            <div class="col-lg-6 offset-lg-3 col-md-12 col-sm-12">
                <div class="card card-outline card-danger">
                    <div class="card-header">
                        <h3 class="card-title">{{ trans('form.delete_account', ['name' => $account->name]) }}</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-danger">
                            {{ trans('form.permDeleteWarning') }}
                        </p>

                        <p>
                            {{ trans('form.account_areYouSure', ['name' => $account->name]) }}
                        </p>

                        @if($account->transactions->count() > 0 || $account->piggyBanks->count() > 0)
                            <p>
                                @if($account->transactions->count())
                                    {{ Lang::choice('form.also_delete_transactions', $account->transactions->count(), ['count' => $account->transactions->count()]) }}
                                @endif<br/>
                                @if($account->piggyBanks()->count() > 0)
                                    {{ Lang::choice('form.also_delete_piggyBanks', $account->piggyBanks->count(), ['count' => $account->piggyBanks->count()]) }}
                                @endif
                            </p>
                        @endif
                        @if($account->transactions()->count() > 0 && 'Asset account' === $account->accountType->type)
                            <p class="text-success">
                                {{ trans_choice('firefly.save_transactions_by_moving', $account->transactions->count() ) }}
                            </p>

                            <p>
                                {{ Html::select('move_account_before_delete', $accountList, null)->class('form-select') }}
                            </p>
                        @else
                            <input type="hidden" name="move_account_before_delete" value="0"/>
                        @endif

                    </div>
                    <div class="card-footer text-end">
                        <input type="submit" name="submit" value="{{ trans('form.deletePermanently') }}"
                               class="btn btn-danger"/>
                        <a href="{{ URL::previous() }}" class="btn-outline-secondary btn">{{ trans('form.cancel') }}</a>
                    </div>
                </div>
            </div>
        </div>

    </form>
@endsection
