@extends('layout.v2.session')
@section('content')


    {{-- SUCCESS MESSAGE (ALWAYS SINGULAR) --}}
    @if(session()->has('success'))
        <div class="alert alert-success" role="alert">
            <strong>{{ trans('firefly.flash_success') }}</strong> {{ session('success') }}
        </div>
    @endif

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

    <div class="card">
        <div class="card-body register-card-body">
            <p class="login-box-msg">{{ trans('firefly.register_new_account') }}</p>

            <form action="{{ route('register') }}" method="post">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="invite_code" value="{{ $inviteCode ?? '' }}">
                <div class="input-group mb-3">
                    <input type="email" name="email" autofocus required value="{{ $email }}" class="form-control"
                           placeholder="{{ trans('form.email') }}"/>
                    <div class="input-group-text"> <em class="fa-solid fa-envelope"></em> </div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" autocomplete="new-password" required class="form-control"
                           placeholder="{{ trans('form.password') }}" name="password"/>
                    <div class="input-group-text"> <em class="fa-solid fa-lock"></em> </div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" autocomplete="new-password" required class="form-control"
                           placeholder="{{ trans('form.password_confirmation') }}" name="password_confirmation"/>
                    <div class="input-group-text"> <em class="fa-solid fa-lock"></em> </div>
                </div>
                <div class="row">
                    <div class="col-12">
                            <input type="checkbox" id="verify_password" checked name="verify_password" value="1">
                            <label for="verify_password">
                                {{ trans('form.verify_password') }}
                                <a href="#"
                                    data-bs-toggle="modal" data-bs-target="#passwordModal"
                                ><span
                                        class="fa fa-fw fa-question-circle"></span></a>
                            </label>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-4 offset-8">
                        <button type="submit" class="btn btn-primary btn-block">Register</button>
                    </div>
                </div>
            </form>

            <p class="mb-1 mt-3">
                <a href="{{ route('login') }}">{{ trans('firefly.want_to_login') }}</a>
            </p>
            <p class="mb-0">
                <a href="{{ route('password.reset.request') }}">{{ trans('firefly.forgot_my_password') }}</a>
            </p>
        </div>
        <!-- /.form-box -->
    </div><!-- /.card -->

    @include('partials.password-modal')

@endsection
@section('scripts')
    @vite(['src/pages/dashboard/dashboard.js'])
@endsection
