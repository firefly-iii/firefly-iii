@extends('layout.v2.session')
@section('content')

    @if(true===$IS_DEMO_SITE)
    <div class="card mb-3">
        <div class="card-body">
            <p class="">
                Welcome to the Firefly III demo!<br/>
                <br/>
                To log in, please use email address <strong>{{ $DEMO_USERNAME }}</strong> with password
                <strong>{{ $DEMO_PASSWORD }}</strong>.
            </p>
            </div>
    </div>
    @endif

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

    @if(session('logoutMessage'))
    <div class="alert alert-primary" role="alert">
        {{ session('logoutMessage') }}
    </div>
    @endif



    <div class="card">
        <div class="card-body login-card-body">
            <p class="login-box-msg">{{ __('firefly.sign_in_to_start') }}
            </p>
            <form action="{{ route('login.post') }}" method="post">
                <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
                @if(config('firefly.authentication_guard') === 'web')
                <div class="input-group mb-3"> <input type="email" name="email" autofocus required class="form-control" placeholder="{{ trans('form.email') }}" value="@if(true===$IS_DEMO_SITE){{ $DEMO_USERNAME }}@else{{ $email }}@endif">
                    <div class="input-group-text"> <em class="fa-solid fa-envelope"></em> </div>
                </div>
                @else
                <div class="input-group mb-3"> <input type="text" autocomplete="username" autofocus required name="{{ $usernameField }}" class="form-control" placeholder="{{ trans('form.login_name') }}" value="{{ $email }}">
                    <div class="input-group-text"> <em class="fa-solid fa-user"></em> </div>
                </div>
                @endif
                <div class="input-group mb-3">
                    <input type="password" id="password" name="password" class="form-control" placeholder="{{ trans('form.password') }}" @if(true===$IS_DEMO_SITE)value="{{ $DEMO_PASSWORD }}"@endif autocomplete="current-password">
                    <div class="input-group-text">
                        <em class="fa-solid fa-lock"></em>
                        <i class="fa-solid fa-eye-slash fa-eye" id="togglePassword"></i>
                    </div>
                </div> <!--begin::Row-->
                <div class="row">
                    <div class="col-8">
                        <div class="form-check"> <input class="form-check-input" name="remember" id="remember" type="checkbox" value="1"> <label class="form-check-label" for="remember">
                                {{ trans('form.remember_me') }}
                            </label> </div>
                    </div> <!-- /.col -->
                    <div class="col-4">
                        <div class="d-grid gap-2"> <button type="submit" class="btn btn-primary">{{ trans('firefly.sign_in') }}</button> </div>
                    </div> <!-- /.col -->
                </div> <!--end::Row-->
            </form>
            @if($allowReset)
            <p class="mb-1 mt-3"> <a href="{{ route('password.reset.request') }}">{{ trans('firefly.forgot_my_password') }}</a> </p>
            @endif
            @if($allowRegistration)
            <p class="mb-0"> <a class='text-center' href='{{ route('register') }}'>{{ trans('firefly.register_new_account') }}</a> </p>
            @endif
        </div> <!-- /.login-card-body -->
    </div>

@endsection
@section('scripts')
<script nonce="{{ $JS_NONCE }}">
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');
    togglePassword.addEventListener('click', () => {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        if('text' === type) {
            togglePassword.classList.add('fa-eye');
            togglePassword.classList.remove('fa-eye-slash');
        }
        if('password' === type) {
            togglePassword.classList.add('fa-eye-slash');
            togglePassword.classList.remove('fa-eye');
        }
        password.setAttribute('type', type);
    });
</script>
@endsection
