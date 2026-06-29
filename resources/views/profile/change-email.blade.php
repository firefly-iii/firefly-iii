@extends('layout.v3.session')


    {{ Breadcrumbs.render(Route.getCurrentRoute.getName) }}
@endsection

@section('content')
    <form method="POST" action="{{ route('profile.change-email.post') }}" accept-charset="UTF-8" class="form-horizontal" id="change-password">
        <input name="_token" type="hidden" value="{{ csrf_token() }}">

        <div class="row">
            <div class="col-lg-6 col-md-12 col-sm-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ 'change_your_email'|_ }}</h3>
                    </div>
                    <div class="card-body">

                        {% if errors|length > 0 %}
                            <ul>
                                {% for error in errors.all %}
                                    <li class="text-danger">{{ error }}</li>
                                @endforeach
                            </ul>

                        @endif


                        <div class="form-group">
                            <label for="email" class="col-sm-4 control-label">{{ trans('form.new_email_address') }}</label>
                            <div class="col-sm-8">
                                <input type="email" class="form-control" id="email" placeholder="{{ 'new_email_address'|_ }}" spellcheck="false"
                                       value="{{ old('email')|default(email) }}"
                                       name="email">
                            </div>
                        </div>
                        {!! ExpandedForm::staticText('verification',trans('firefly.email_verification')) }}

                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success text-end">{{ 'change_your_email'|_ }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
