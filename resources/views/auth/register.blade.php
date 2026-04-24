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

    <div id="client-errors" class="alert alert-danger" role="alert" style="display:none;">
        <ul id="client-errors-list"></ul>
    </div>

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
                           placeholder="{{ trans('form.password') }}" minlength="16" name="password"/>
                    <div class="input-group-text"> <em class="fa-solid fa-lock"></em> </div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" autocomplete="new-password" minlength="16" required class="form-control"
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
    <script nonce="{{ $JS_NONCE }}">
    (function () {
        const form        = document.querySelector('form[action="{{ route('register') }}"]');
        const errorBox    = document.getElementById('client-errors');
        const errorList   = document.getElementById('client-errors-list');
        const submitBtn   = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.textContent;

        function showErrors(errors) {
            errorList.innerHTML = errors.map(function(e) { return '<li>' + e + '</li>'; }).join('');
            errorBox.style.display = 'block';
            errorBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        async function sha1Hex(str) {
            const buf = await crypto.subtle.digest('SHA-1', new TextEncoder().encode(str));
            return Array.from(new Uint8Array(buf))
                .map(function(b) { return b.toString(16).padStart(2, '0'); })
                .join('')
                .toUpperCase();
        }

        async function isPwned(password) {
            const hash   = await sha1Hex(password);
            const prefix = hash.slice(0, 5);
            const suffix = hash.slice(5);
            const res    = await fetch('https://api.pwnedpasswords.com/range/' + prefix, {
                headers: { 'Add-Padding': 'true' }
            });
            if (!res.ok) { return false; }
            const text = await res.text();
            return text.toUpperCase().split('\n').some(function(line) {
                return line.split(':')[0] === suffix;
            });
        }

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            errorBox.style.display = 'none';

            const password = form.querySelector('[name="password"]').value;
            const confirm  = form.querySelector('[name="password_confirmation"]').value;
            const verify   = form.querySelector('[name="verify_password"]');
            const errors   = [];

            if (password.length < 16) {
                errors.push('{{ blade_escape_js((string)trans('validation.min.string', ['attribute' => 'password', 'min' => 16])) }}');
            }
            if (password !== confirm) {
                errors.push('{{ blade_escape_js(trans('validation.confirmed', ['attribute' => 'password'])) }}');
            }

            if (errors.length > 0) {
                showErrors(errors);
                return;
            }

            if (verify && verify.checked) {
                submitBtn.disabled    = true;
                submitBtn.textContent = '{{ blade_escape_js(trans('validation.verifying_password')) }}';
                try {
                    if (await isPwned(password)) {
                        errors.push('{{ blade_escape_js(trans('validation.secure_password')) }}');
                    }
                } catch (_) {
                    // network failure — let server validate
                }
                submitBtn.disabled    = false;
                submitBtn.textContent = originalBtnText;
            }

            if (errors.length > 0) {
                showErrors(errors);
                return;
            }

            form.submit();
        });
    })();
    </script>
@endsection
