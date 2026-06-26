@extends('layout.v3.session')
@section('content')
    <form action="{{ route('new-user.submit') }}" method="post" id="store" class="form-horizontal">
        <input type="hidden" name="_token" value="{{ csrf_token() }}"/>

        <div class="row">
            <div class="col-lg-6 offset-lg-3 col-md-12 col-xs-12">

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.getting_started') }}</h3>
                    </div>
                    <div class="card-body">
                        <p>
                            <strong>{{ __('firefly.welcome') }}</strong>
                        </p>
                        <p>
                            {{ __('firefly.to_get_started') }}
                        </p>
                        {!! ExpandedForm::text('bank_name') !!}
                        {!! CurrencyForm::balanceAll('bank_balance') !!}

                        <p class="text-success">
                            {{ __('firefly.currency_not_present') }}
                        </p>

                        <p>
                            {{ __('firefly.savings_balance_text') }}
                        </p>

                        {!! ExpandedForm::integer('savings_balance',0, ["step" => "any"]) !!}

                        <p class="mb-1">
                            {{ __('firefly.set_preferred_language') }}
                        </p>
                        <div id="language_holder" class="row mb-3">
                            <div class="input-group">
                            <label for="language_holder_select" class="col-sm-3 col-form-label">{{ __('firefly.language') }}</label>
                            <div class="col-sm-9">
                                <select class="form-select" id="lang_holder" name="language">
                                    @foreach(config('firefly.languages') as $key => $lang)
                                        <option @if($lang === $language) selected @endif value="{{ $key }}">{{ $lang['name_locale'] }} ({{ $lang['name_english'] }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            </div>
                        </div>


                        <p>
                            {!! __('firefly.finish_up_new_user') !!}
                        </p>

                    </div>
                    <div class="card-footer text-end">
                        <input type="submit" name="submit" value="{{ __('firefly.submit') }}" class="btn btn-success text-end"/>
                    </div>
                </div>
            </div>
        </div>
    </form>


@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
@endsection
