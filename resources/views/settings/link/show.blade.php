@extends('layout.v3.session')


    {{ Breadcrumbs.render(Route.getCurrentRoute.getName, linkType) }}
@endsection
@section('content')
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ trans('firefly.overview_for_link', {name: journalLinkTranslation('name', linkType.name)}) }}</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover sortable">
                        <thead>
                        <tr>
                            <th>&nbsp;</th>
                            <th>{{ trans('firefly.inward_transaction') }}</th>
                            <th>&nbsp;</th>
                            <th>{{ trans('firefly.link_description') }}</th>
                            <th>{{ trans('firefly.outward_transaction') }}</th>
                            <th>&nbsp;</th>
                        </tr>
                        </thead>
                        <tbody>
                        {% for link in links %}
                            <tr>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('transactions.link.delete', [link.id]) }}" class="btn btn-danger delete-link" data-id="{{ link.id }}"><span class="bi bi-trash"></span></a>
                                        <a href="#" class="btn btn-outline-secondary switch-link" data-id="{{ link.id }}"><span
                                                class="fa fa-arrows-h"></span></a>
                                    </div>
                                </td>
                                <td data-value="{{ link.source.description }}">
                                    <a href="{{ route('transactions.show', [link.source.transaction_group_id]) }}">{{ link.source.description }}</a>
                                </td>
                                <td>
                                    {{ journalObjectAmount(link.source) }}
                                </td>
                                <td>{{ journalLinkTranslation('outward', linkType.outward) }}</td>
                                <td data-value="{{ link.destination.description }}">
                                    <a href="{{ route('transactions.show', [link.destination.transaction_group_id]) }}">{{ link.destination.description }}</a>
                                </td>
                                <td>
                                    {{ journalObjectAmount(link.destination) }}
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>


@endsection
@section('scripts')
    <script type="text/javascript" src="v1/js/lib/bootstrap-sortable.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script nonce="{{ $JS_NONCE }}">
        $('.switch-link').on('click', switchLink);
        var switchLinkUrl = '{{ route('transactions.link.switch') }}';

        function switchLink(e) {
            e.preventDefault();
            var obj = $(e.currentTarget);

            $.post(switchLinkUrl, {
                _token: token,
                id: obj.data('id')
            }).done(function () {
                location.reload();
            }).fail(function () {
                console.error('I failed :(');
            });

            //alert(obj.data('id'));

            return false
        }

    </script>
@endsection

@section('styles')
    <link rel="stylesheet" href="v1/css/bootstrap-sortable.css?v={{ $FF_BUILD_TIME }}" type="text/css" media="all" nonce="{{ $JS_NONCE }}">
@endsection
