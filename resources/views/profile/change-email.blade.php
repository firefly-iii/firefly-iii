@extends('layout.v3.session')
@section('content')
    <form method="POST" action="{{ route('profile.change-email.post') }}" accept-charset="UTF-8" class="form-horizontal" id="change-password">
        <input name="_token" type="hidden" value="{{ csrf_token() }}">

        <div class="row">
            <div class="col-lg-6 col-md-12 col-sm-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.change_your_email') }}</h3>
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
                            <label for="email" class="col-sm-4 control-label">{{ trans('form.new_email_address') }}</label>
                            <div class="col-sm-8">
                                <input type="email" class="form-control" id="email" placeholder="{{ __('firefly.new_email_address') }}" spellcheck="false" value="{{ old('email') ?? $email }}" name="email">
                            </div>
                        </div>
                        <p>{!! trans('firefly.email_verification') !!}</p>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-success ">{{ __('firefly.change_your_email') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
@endsection
