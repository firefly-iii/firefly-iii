@extends('layout.v3.session')
@section('content')
    <form method="POST" action="{{ route('profile.change-password.post') }}" accept-charset="UTF-8" class="form-horizontal" id="change-password">
        <input name="_token" type="hidden" value="{{ csrf_token() }}">

        <div class="row">
            <div class="col-lg-6 col-md-12 col-sm-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.change_your_password') }}</h3>
                    </div>
                    <div class="card-body">
                        @if(count($errors) > 0)
                            <ul>
                                @foreach($errors->all() as $error)
                                    <li class="text-danger">{{ $error }}</li>
                                @endforeach
                            </ul>
                        @endif


                        <div class="form-group">
                            <label for="inputOldPassword" class="col-sm-4 control-label">{{ __('firefly.current_password') }}</label>

                            <div class="col-sm-8">
                                <input type="password" class="form-control" id="inputOldPassword" placeholder="{{ __('firefly.current_password') }}" spellcheck="false"
                                       name="current_password">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="inputNewPassword1" class="col-sm-4 control-label">{{ __('firefly.new_password') }}</label>

                            <div class="col-sm-8">
                                <input type="password" class="form-control" id="inputNewPassword1" placeholder="{{ __('firefly.new_password') }}" name="new_password" spellcheck="false">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="inputNewPassword2" class="col-sm-4 control-label">{{ __('firefly.new_password_again') }}</label>

                            <div class="col-sm-8">
                                <input type="password" class="form-control" id="inputNewPassword2" placeholder="{{ __('firefly.new_password_again') }}" spellcheck="false"
                                       name="new_password_confirmation">
                            </div>
                        </div>

                        {!! ExpandedForm::checkbox('verify_password','1', true) !!}
                        <p>
                            <a data-bs-toggle="modal" data-target="#passwordModal" href="#passwordModal">{{ __('firefly.what_is_pw_security') }}</a>
                        </p>

                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-success">{{ __('firefly.change_your_password') }}</button>
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
