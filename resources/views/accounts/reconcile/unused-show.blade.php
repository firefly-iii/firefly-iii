@extends('layout.v3.session')
@section('content')
    <div class="row">
        <div class="col-lg-6 col-md-6 col-sm-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.transaction_journal_information') }}</h3>

                    <div class="box-tools text-end">
                        <div class="btn-group">
                            <button id="transaction_menu" class="btn btn-box-tool dropdown-toggle" data-toggle="dropdown"><span class="bi bi-list"></span>
                            </button>
                            <ul class="dropdown-menu" role="menu">
                                <li><a href="{{ route('transactions.edit',[$journal->id]) }}"><span class="bi bi-pencil"></span> {{ __('firefly.edit') }}</a></li>
                                <li><a href="{{ route('transactions.delete',[$journal->id]) }}"><span class="bi bi-trash"></span> {{ __('firefly.delete') }}</a></li>
                            </ul>
                        </div>
                    </div>

                </div>
                <div class="card-body p-0">
                    <table class="table table-hover">
                        <tbody>
                        <tr>
                            <td>{{ trans('list.type') }}</td>
                            <td>{{ __('firefly.'.$journal->transactiontype->type) }}</td>
                        </tr>
                        <tr>
                            <td>{{ trans('list.description') }}</td>
                            <td>{{ $journal->description }}</td>
                        </tr>
                        {{-- total amount --}}
                        <tr>
                            <td>{{ __('firefly.total_amount') }}</td>
                            <td>
                                {!! format_amount_by_account(transaction.account, transaction.amount) !!}

                            </td>
                        </tr>
                        <tr>
                            <td class="thirty">{{ trans('list.date') }}</td>
                            <td>{{ $journal->date->isoFormat($monthAndDayFormat) }}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    <div class="text-end">
                        <div class="btn-group">
                            <a class="btn btn-outline-secondary" href="{{ route('transactions.edit',[$journal->id]) }}"><span class="bi bi-pencil"></span> {{ __('firefly.edit') }}</a>
                            <a href="{{ route('transactions.delete',[$journal->id]) }}" class="btn btn-danger"><span class="bi bi-trash"></span> {{ __('firefly.delete') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-12">

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.transaction_journal_meta') }}</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-responsive table-hover">
                        <tbody>
                        {{--
                        <tr>
                            <td>{{ 'categories'|_ }}</td>
                            <td>{{ journalCategories(journal)|raw }}</td>
                        </tr>
                        --}}
                        @if($journal->tags->count() > 0)
                            <tr>
                                <td>{{ __('firefly.tags') }}</td>
                                <td>
                                    @foreach($journal->tags as $tag)
                                        <h4 class="inline"><a class="label text-bg-success" href="{{ route('tags.show',[$tag->id]) }}">
                                                {{ $tag->tag }}</a>
                                        </h4>
                                    @endforeach
                                </td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('scripts')
@endsection
