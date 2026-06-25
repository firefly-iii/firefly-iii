{% set VUE_SCRIPT_NAME = 'profile' %}
@extends('layout.v3.session')


    {{ Breadcrumbs.render(Route.getCurrentRoute.getName) }}
@endsection

@section('content')

    <div class="row">
        <div class="col-lg-8 offset-lg-2 col-md-12 col-sm-12">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="box-header">
                            <h3 class="card-title">{{ 'oauth_tokens'|_ }}</h3>
                        </div>

                        <div class="card-body">
                            <p>
                                {{ trans('firefly.oauth_tokens_explain', {link: link})|raw }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
                <div role="tabpanel" class="tab-pane" id="oauth">
                    <div id="passport_clients"></div>
                </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        var deleteAPIRoute = '{{ route('api.v1.data.destroy') }}';
        var confirmText = '{{ trans('firefly.are_you_sure')|escape('js') }}';
        $(document).ready(function () {
            $('.confirm').on('click', function (e) {
                if(!confirm(confirmText)) {
                    return false;
                }
                var link = $(e.currentTarget);
                var classes = link.find('i').attr('class');
                var url = deleteAPIRoute + '?objects=' + link.data('type');
                // different URL for purge route:
                if (link.data('type') === 'purge') {
                    url = '{{ route('api.v1.data.purge') }}';
                }
                if (link.data('type') === 'unused_accounts') {
                    url = deleteAPIRoute + '?objects=not_assets_liabilities&unused=true';
                }

                // replace icon with loading thing
                link.prop('disabled', true);
                link.find('i').removeClass().addClass('fa fa-spin fa-spinner');

                // call API:
                $.ajax({
                    method: 'DELETE',
                    url: url,
                }).done(
                    function () {
                        // enable button again:
                        link.prop('disabled', false);
                        link.find('i').removeClass().addClass(classes);
                        alert(link.data('success'));
                    }
                ).fail(function () {
                    link.find('i').removeClass().addClass('fa fa-exclamation-triangle');
                    alert('Could not delete. Sorry.');
                });
                return false;
            });
        });
    </script>
@endsection
