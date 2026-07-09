@extends('layout.v3.session')
@section('content')
    <form method="POST" action="{{ route('profile.delete-account.post') }}" accept-charset="UTF-8" class="form-horizontal" id="delete-account">
        <input name="_token" type="hidden" value="{{ csrf_token() }}">

        <div class="row">
            <div class="col-lg-6 col-md-12 col-sm-12">
                <div class="card card-danger card-outline">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.delete_your_account') }}</h3>
                    </div>
                    <div class="card-body">

                        <p class="text-danger">
                            {!! __('firefly.delete_your_account_help') !!}
                        </p>

                        <p class="text-danger">
                            {{ __('firefly.delete_your_account_password') }}

                        </p>
                        @if(count($errors) > 0)
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li class="text-danger">{{ $error }}</li>
                                @endforeach
                            </ul>

                        @endif

                        <div class="form-group">
                            <label for="password" class="col-sm-4 control-label">{{ __('firefly.password') }}</label>

                            <div class="col-sm-8">
                                <input type="password" class="form-control" id="password" placeholder="{{ __('firefly.password') }}" name="password" spellcheck="false">
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-danger delete-profile">
                            {{ __('firefly.delete_account_button') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
    <!-- just a bit of inline code -->
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        var confirmText= '{{ __('firefly.are_you_sure') }}';
        $(function () {
            "use strict";
            $('.delete-profile').click(function () {
                return confirm(confirmText);
            });
        });
    </script>
@endsection
