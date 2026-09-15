@extends('layout.v3.session')
@section('content')
    <form method="POST" action="{{ route('profile.logout-others.post') }}" accept-charset="UTF-8" class="form-horizontal">
        <input name="_token" type="hidden" value="{{ csrf_token() }}">

        <div class="row">
            <div class="col-lg-6 col-md-12 col-sm-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.logout_other_sessions') }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="inputOldPassword" class="col-sm-4 control-label">{{ __('firefly.current_password') }}</label>

                            <div class="col-sm-8">
                                <input type="password" class="form-control" id="inputOldPassword" placeholder="{{ __('firefly.current_password') }}" spellcheck="false"
                                       name="password">
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-success ">{{ __('firefly.logout_other_sessions') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    @include('partials.password-modal')
@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
@endsection
