@extends('layout.v2.session')
@section('content')

    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul>
            @foreach($errors->getBags() as $bag)
                @foreach($bag->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            @endforeach
            </ul>
        </div>
    @endif

    @if(session()->has('error'))
        <div class="alert alert-danger" role="alert">
            {{ session('error') }}
        </div>
    @endif


    <div class="card">
        <div class="card-body login-card-body">
            <p class="login-box-msg">{{ trans('firefly.two_factor_welcome', ['user' => auth()->user()->email]) }}</p>
            <p class="login-box-msg">{{ __('firefly.two_factor_enter_code') }}</p>

            <form action="{{ route('two-factor.submit') }}" method="post">
                <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
                <div class="input-group mb-3">
                    <input type="text" autofocus required name="one_time_password" inputmode="numeric" autocomplete="one-time-code" class="form-control" placeholder="{{ __('firefly.two_factor_code_here') }}" autofocus />
                    <div class="input-group-text"> <em class="fa-solid fa-calculator"></em> </div>
                </div>
                <div class="row">
                    <!-- /.col -->
                    <div class="col">
                        <button type="submit" class="btn btn-primary btn-block">{{ __('firefly.authenticate') }}</button>
                    </div>
                    <!-- /.col -->
                </div>
            </form>
            <p class="mb-1 mt-3">
                <a href="{{ route('two-factor.lost') }}">{{ __('firefly.two_factor_forgot') }}</a>
            </p>
        </div>
    </div>

@endsection
