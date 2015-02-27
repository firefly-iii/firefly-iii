@extends('layouts.guest')
@section('content')
    <div class="row">
        <div class="col-md-4 col-md-offset-4">
            <div class="login-panel panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Firefly III &mdash; Reset Password</h3>
                </div>
				<div class="panel-body">
					@if (count($errors) > 0)
						<div class="alert alert-danger">
							<strong>Whoops!</strong> There were some problems with your input.<br><br>
							<ul>
								@foreach ($errors->all() as $error)
									<li>{{ $error }}</li>
								@endforeach
							</ul>
						</div>
					@endif

					<form role="form" method="POST" action="/password/reset">
						<input type="hidden" name="_token" value="{{ csrf_token() }}">
						<input type="hidden" name="token" value="{{ $token }}">

						<div class="form-group">
							<label id="email" class="control-label">E-Mail</label>
                            <input type="email" class="form-control" placeholder="E-Mail" name="email" value="{{ old('email') }}">
						</div>

						<div class="form-group">
							<label class="control-label">Password</label>
                            <input type="password" placeholder="Password" class="form-control" name="password">
						</div>

						<div class="form-group">
							<label class="control-label">Confirm Password</label>
                            <input type="password" placeholder="Confirm Password" class="form-control" name="password_confirmation">
						</div>

						<div class="form-group">
                            <button type="submit" class="btn btn-success btn-lg btn-block">
                                Reset Password
                            </button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection
