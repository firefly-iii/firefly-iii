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

    @if(session('logoutMessage'))
        <div class="alert alert-primary" role="alert">
            {{ session('logoutMessage') }}
        </div>
    @endif

    <div class="card">
        <div class="card-body login-card-body">
            <p class="login-box-msg">{{ trans('firefly.reset_password') }}</p>

            <form action="{{ url('/password/reset') }}" method="post">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="input-group mb-3">
                    <input type="email" name="email" required autofocus class="form-control" value="{{ old('email') }}"
                           placeholder="{{ trans('form.email') }}"/>
                    <div class="input-group-text"> <em class="fa-solid fa-envelope"></em> </div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" class="form-control" required placeholder="{{ trans('form.password') }}"
                           name="password"/>
                    <div class="input-group-text"> <em class="fa-solid fa-lock"></em> </div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" class="form-control" required placeholder="{{ trans('form.password_confirmation') }}"
                           name="password_confirmation"/>
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
                    <div class="col">
                        <button type="submit" class="btn btn-primary btn-block">{{ trans('firefly.button_reset_password') }}</button>
                    </div>
                    <!-- /.col -->
                </div>
            </form>

            <p class="mt-3 mb-1">
                <a href="{{ route('login') }}">{{ trans('firefly.want_to_login') }}</a>
            </p>
            @if($allowRegistration)
            <p class="mb-0">
                <a href="{{ route('register') }}" class="text-center">{{ trans('firefly.register_new_account') }}</a>
            </p>
            @endif
        </div>
    </div>

    @include('partials.password-modal')

@endsection

