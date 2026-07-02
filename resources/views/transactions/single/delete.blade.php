@extends('layout.v3.session')
@section('content')
    <form method="POST" action="{{ route('transactions.destroy', [$journal->id]) }}" accept-charset="UTF-8" class="form-horizontal" id="destroy">
        <input name="_token" type="hidden" value="{{ csrf_token() }}">

        <div class="row">
            <div class="col-lg-6 offset-lg-3 col-md-6 col-sm-12">
                <div class="card card-danger card-outline">
                    <div class="card-header">
                        <h3 class="card-title">{{ trans('form.delete_journal', ['description' => $journal->description]) }}</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-danger">
                            {{ trans('form.permDeleteWarning') }}
                        </p>

                        <p>
                            {{ trans('form.journal_areYouSure', ['description' => $journal->description]) }}
                        </p>
                    </div>
                    <div class="card-footer text-end">
                        <input type="submit" name="submit" value="{{ trans('form.deletePermanently') }}" class="btn btn-danger"/>
                        @if('Withdrawal' === $journal->transaction_type_type)
                            <a href="{{ route('transactions.index','withdrawal') }}" class="btn-outline-secondary btn">{{ trans('form.cancel') }}</a>
                        @endif
                        @if('Deposit' === $journal->transaction_type_type)
                            <a href="{{ route('transactions.index','deposit') }}" class="btn-outline-secondary btn">{{ trans('form.cancel') }}</a>
                        @endif
                        @if('Transfer' === $journal->transaction_type_type)
                            <a href="{{ route('transactions.index','transfers') }}" class="btn-outline-secondary btn">{{ trans('form.cancel') }}</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </form>
@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
@endsection
