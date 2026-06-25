@extends('layout.v3.session')


    {{ Breadcrumbs.render(Route.getCurrentRoute.getName, objectGroup) }}
@endsection

@section('content')

    <form method="POST" action="{{ route('object-groups.destroy',objectGroup.id) }}" accept-charset="UTF-8" class="form-horizontal">
        <input name="_token" type="hidden" value="{{ csrf_token() }}">
        <div class="row">
            <div class="col-lg-6 offset-lg-3 col-md-12 col-sm-12">
                <div class="card card-danger card-outline">
                    <div class="card-header">
                        <h3 class="card-title">{{ trans('form.delete_object_group', {'title': objectGroup.title}) }}</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-danger">
                            {{ trans('form.permDeleteWarning') }}
                        </p>

                        <p>
                            {{ trans('form.object_group_areYouSure', {'title': objectGroup.title}) }}
                        </p>

                        {% if piggyBanks > 0 %}
                            <p>
                                {{ Lang::choice('form.not_delete_piggy_banks', piggyBanks, {count: piggyBanks}) }}
                            </p>
                        @endif

                    </div>
                    <div class="card-footer">
                        <input type="submit" name="submit" value="{{ trans('form.deletePermanently') }}" class="btn text-end btn-danger"/>
                        <a href="{{ URL::previous() }}" class="btn-outline-secondary btn">{{ trans('form.cancel') }}</a>
                    </div>
                </div>
            </div>
        </div>

    </form>
@endsection
