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
        <div class="card-body login-card-body">
            @if(session('status'))
            <p class="login-box-msg text-success">
                {{ session('status') }}
            </p>
            @else
            <p class="login-box-msg">{{ trans('firefly.reset_password') }}</p>
            <form action="{{ route('password.email') }}" method="post">
                <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
                <div class="input-group mb-3">
                    <input type="email" autofocus required class="form-control" name="email"
                           placeholder="{{ trans('form.email') }}"/>
                    <div class="input-group-text"> <em class="fa-solid fa-envelope"></em> </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-block">{{ trans('firefly.reset_button') }}</button>
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
            @endif
        </div>
    </div>

@endsection

