@extends('layout.v3.auth')
@section('content')
    <div class="card card-default">
        <div class="card-header">
            Authorization Request
        </div>
        <div class="card-body">
            <p>
                Application <strong>"{{ $client->name }}"</strong> is requesting permission to access your account and financial data.
            </p>
            <p>
                Access to the API is not scoped. All data will be accessible. Please proceed with caution and only authorize applications you trust.
            </p>
            @if('' !== $client->redirect_uris[0] ?? '')
            <p>
                You will be redirected to <code>{{ $client->redirect_uris[0] }}</code> after you authorize or cancel this request.
            </p>
            @endif

            <!-- Scope List -->
            @if (count($scopes) > 0)
                <div class="scopes">
                    <p><strong>This application will be able to:</strong></p>

                    <ul>
                        @foreach ($scopes as $scope)
                            <li>{{ $scope->description }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="buttons">
                <div class="row">
                    <div class="col">
                        <!-- Authorize Button -->
                        <form method="post" class="form-inline" action="{{ route('passport.authorizations.approve') }}">
                            @csrf
                            <input type="hidden" name="state" value="{{ $request->state }}">
                            <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                            <input type="hidden" name="auth_token" value="{{ $authToken }}">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-block btn-approve">Authorize</button>
                            </div>
                        </form>
                    </div>
                    <div class="col">
                        <!-- Cancel Button -->
                        <form method="post" action="{{ route('passport.authorizations.deny') }}">
                            @csrf
                            @method('DELETE')

                            <input type="hidden" name="state" value="{{ $request->state }}">
                            <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                            <input type="hidden" name="auth_token" value="{{ $authToken }}">
                            <div class="d-grid gap-2">
                            <button class="btn btn-danger">Cancel</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
