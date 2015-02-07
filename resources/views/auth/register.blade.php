@extends('layouts.guest')

@section('content')
	<div class="row">
		<div class="col-md-4 col-md-offset-4">
			<div class="login-panel panel panel-default">
				<div class="panel-heading">Firefly III &mdash; Register</div>
				<div class="panel-body">
					<p>
						Registering an account on Firefly requires an e-mail address.
					</p>
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

					<form role="form" id="register" method="POST" action="/auth/register">
						<input type="hidden" name="_token" value="{{ csrf_token() }}">

						<div class="form-group">
							<label class="control-label">E-Mail Address</label>
							<input type="email" class="form-control" name="email" value="{{ old('email') }}">
						</div>

						<div class="form-group">
							<label class="control-label">Password</label>
							<input type="password" class="form-control" name="password">
						</div>

						<div class="form-group">
							<label class="control-label">Confirm Password</label>
							<input type="password" class="form-control" name="password_confirmation">
						</div>

						<div class="form-group">
							<div class="col-md-6 col-md-offset-4">
								<button type="submit" class="btn btn-primary">
									Register
								</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection
