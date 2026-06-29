@extends('layout.v3.session')
@section('content')
    <div class="row">
        <div class="col-lg-8 offset-lg-2 col-md-12 col-sm-12">

            <!-- Nav tabs -->
            <ul class="nav nav-tabs" id="profileTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="options-tab" data-bs-toggle="tab"
                            data-bs-target="#options-tab-pane" type="button" role="tab"
                            aria-controls="options-tab-pane"
                            aria-selected="true">{{ __('firefly.options') }}</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="cmd-tab" data-bs-toggle="tab"
                            data-bs-target="#cmd-tab-pane" type="button" role="tab"
                            aria-controls="cmd-tab-pane"
                            aria-selected="false">{{ __('firefly.command_line_token') }}</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="delete-tab" data-bs-toggle="tab"
                            data-bs-target="#delete-tab-pane" type="button" role="tab"
                            aria-controls="delete-tab-pane"
                            aria-selected="false">{{ __('firefly.delete_stuff_header') }}</button>
                </li>
            </ul>
            <div class="tab-content" id="profileTabContent">
                <div class="tab-pane fade show active" id="options-tab-pane" role="tabpanel" aria-labelledby="options-tab" tabindex="0">
                    <div class="card mb-2 mt-1">
                        <div class="card-body">
                            <p>
                                {!! trans('firefly.user_id_is',['user' => $userId]) !!}
                            </p>
                            <div class="row">
                                <div class="col-lg-6">
                                    <ul>
                                        @if(true === $isInternalAuth)
                                            <li>
                                                <a href="{{ route('profile.change-email') }}">{{ __('firefly.change_your_email')  }}</a>
                                            </li>
                                            <li>
                                                <a href="{{ route('profile.change-password') }}">{{ __('firefly.change_your_password')  }}</a>
                                            </li>
                                            @if(true === $enabled2FA)
                                                <li>
                                                    <a href="{{ route('profile.mfa.index') }}">{{ __('firefly.manage_mfa_settings')  }}</a>
                                                </li>
                                            @endif
                                            @if(false === $enabled2FA)
                                                <li>
                                                    <a href="{{ route('profile.mfa.index') }}">{{ __('firefly.enable_mfa')  }}</a>
                                                </li>
                                            @endif
                                        @endif

                                        <li><a href="{{ route('logout') }}" class="logout-link">{{ __('firefly.logout')  }}</a>
                                        </li>

                                        @if(true === $isInternalAuth)
                                            <li>
                                                <a href="{{ route('profile.logout-others') }}">{{ __('firefly.logout_other_sessions')  }}</a>
                                            </li>
                                            <li><a class="text-danger"
                                                   href="{{ route('profile.delete-account') }}">{{ __('firefly.delete_account')  }}</a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="cmd-tab-pane" role="tabpanel" aria-labelledby="cmd-tab" tabindex="0">
                    <div class="card mb-2 mt-1">
                        <div class="card-body">
                            <p>
                                {{ __('firefly.explain_command_line_token')  }}
                            </p>
                            <p>
                                <input id="token" type="text" class="form-control" name="token" value="{{ $accessToken->data }}" size="32" maxlength="32" readonly/>
                            </p>
                            <form action="{{ route('profile.regenerate') }}" method="post">
                                <p>

                                    <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
                                    <button type="submit" class="btn btn-danger btn-xs"><span
                                            class="bi bi-arrow-repeat"></span> {{ __('firefly.regenerate_command_line_token')  }}
                                    </button>
                                </p>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="delete-tab-pane" role="tabpanel" aria-labelledby="delete-tab" tabindex="0">
                    <div class="card mb-2 mt-1">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('firefly.purge_data_title')  }}</h3>
                        </div>
                        <div class="card-body">
                            <div class="col-lg-12">
                                <p class="text-info">
                                    {{ __('firefly.purge_data_expl')  }}
                                </p>
                                <p>
                                    <button type="button"
                                            data-success="{{ trans('firefly.purged_all_records') }}"
                                            data-type="purge" class="confirm btn btn-warning btn-sm"><span
                                            class="bi bi-trash"></span> {{ __('firefly.purge_all_data')  }}</button>

                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-2">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('firefly.delete_data_title')  }}</h3>
                        </div>
                        <div class="card-body">
                            <div class="col-lg-12">
                                <p class="text-info">
                                    {{ __('firefly.permanent_delete_stuff')  }}
                                </p>
                                <h4>{{ __('firefly.financial_control')  }}</h4>
                                <div class="btn-group">
                                    <button type="button"
                                            data-success="{{ trans('firefly.deleted_all_budgets') }}"
                                            data-type="budgets" class="confirm btn btn-danger btn-sm"><span
                                            class="bi bi-pie-chart"></span> {{ __('firefly.delete_all_budgets')  }}</button>

                                    <button type="button"
                                            data-success="{{ trans('firefly.deleted_all_bills') }}"
                                            data-type="bills" class="confirm btn btn-danger btn-sm"><span
                                            class="bi bi-calendar-o"></span> {{ __('firefly.delete_all_bills')  }}</button>

                                    <button type="button"
                                            data-success="{{ trans('firefly.deleted_all_piggy_banks') }}"
                                            data-type="piggy_banks" class="confirm btn btn-danger btn-sm"><span
                                            class="fa fa-bullseye"></span> {{ __('firefly.delete_all_piggy_banks')  }}</button>
                                </div>
                                <h4>{{ __('firefly.automation')  }}</h4>
                                <div class="btn-group">
                                    <button type="button"
                                            data-success="{{ trans('firefly.deleted_all_rules') }}"
                                            data-type="rules" class="confirm btn btn-danger btn-sm"><span
                                            class="fa fa-random"></span> {{ __('firefly.delete_all_rules')  }}</button>

                                    <button type="button"
                                            data-success="{{ trans('firefly.deleted_all_recurring') }}"
                                            data-type="recurring" class="confirm btn btn-danger btn-sm"><span
                                            class="fa fa-paint-brush"></span> {{ __('firefly.delete_all_recurring')  }}
                                    </button>
                                </div>

                                <h4>{{ __('firefly.classification')  }}</h4>
                                <div class="btn-group">
                                    <button type="button"
                                            data-success="{{ trans('firefly.deleted_all_categories') }}"
                                            data-type="categories" class="confirm btn btn-danger btn-sm"><span
                                            class="bi bi-bookmark"></span> {{ __('firefly.delete_all_categories')  }}</button>

                                    <button type="button"
                                            data-success="{{ trans('firefly.deleted_all_tags') }}"
                                            data-type="tags" class="confirm btn btn-danger btn-sm"><span
                                            class="bi bi-tag"></span> {{ __('firefly.delete_all_tags')  }}</button>

                                    <button type="button"
                                            data-success="{{ trans('firefly.deleted_all_object_groups') }}"
                                            data-type="object_groups" class="confirm btn btn-danger btn-sm"><span
                                            class="fa fa-envelope-o"></span> {{ __('firefly.delete_all_object_groups')  }}
                                    </button>
                                </div>

                                <h4>{{ __('firefly.accounts') }}</h4>
                                <p>
                                    <em class="text-danger">{{ __('firefly.also_delete_transactions')  }}</em>
                                </p>
                                <div class="btn-group">
                                    <button type="button"
                                            data-success="{{ trans('firefly.deleted_all_accounts') }}"
                                            data-type="accounts" class="confirm btn btn-danger btn-sm"><span
                                            class="fa fa-credit-card"></span> {{ __('firefly.delete_all_accounts')  }}</button>

                                    <button type="button"
                                            data-success="{{ trans('firefly.deleted_all_asset_accounts') }}"
                                            data-type="asset_accounts" class="confirm btn btn-danger btn-sm"><span
                                            class="bi bi-cash"></span> {{ __('firefly.delete_all_asset_accounts')  }}</button>

                                    <button type="button"
                                            data-success="{{ trans('firefly.deleted_all_expense_accounts') }}"
                                            data-type="expense_accounts" class="confirm btn btn-danger btn-sm"><span
                                            class="fa fa-shopping-cart"></span> {{ __('firefly.delete_all_expense_accounts')  }}
                                    </button>
                                    <button type="button"
                                            data-success="{{ trans('firefly.deleted_all_revenue_accounts') }}"
                                            data-type="revenue_accounts" class="confirm btn btn-danger btn-sm"><span
                                            class="bi bi-download"></span> {{ __('firefly.delete_all_revenue_accounts')  }}
                                    </button>

                                    <button type="button"
                                            data-success="{{ trans('firefly.deleted_all_liabilities') }}"
                                            data-type="liabilities" class="confirm btn btn-danger btn-sm"><span
                                            class="fa fa-ticket"></span> {{ __('firefly.delete_all_liabilities')  }}</button>
                                </div>
                                <h4>{{ __('firefly.accounts') }}</h4>
                                <p>
                                    <em class="text-info">
                                        {{ __('firefly.delete_unused_accounts')  }}
                                    </em>
                                </p>
                                <div class="btn-group">
                                    <button type="button"
                                            data-success="{{ trans('firefly.deleted_all_unused_accounts') }}"
                                            data-type="unused_accounts" class="confirm btn btn-warning btn-sm"><span
                                            class="fa fa-credit-card"></span> {{ __('firefly.delete_all_unused_accounts')  }}
                                    </button>
                                </div>


                                <h4> {{ __('firefly.transactions') }}</h4>
                                <div class="btn-group">
                                    <button type="button"
                                            data-success="{{ trans('firefly.deleted_all_transactions') }}"
                                            data-type="transactions" class="confirm btn btn-danger btn-sm"><span
                                            class="bi bi-arrow-left-right"></span> {{ __('firefly.delete_all_transactions')  }}
                                    </button>

                                    <button type="button"
                                            data-success="{{ trans('firefly.deleted_all_withdrawals') }}"
                                            data-type="withdrawals" class="confirm btn btn-danger btn-sm"><span
                                            class="bi bi-arrow-left"></span> {{ __('firefly.delete_all_withdrawals')  }}
                                    </button>

                                    <button type="button"
                                            data-success="{{ trans('firefly.deleted_all_deposits') }}"
                                            data-type="deposits" class="confirm btn btn-danger btn-sm"><span
                                            class="bi bi-arrow-right"></span> {{ __('firefly.delete_all_deposits')  }}
                                    </button>
                                    <button type="button"
                                            data-success="{{ trans('firefly.deleted_all_transfers') }}"
                                            data-type="transfers" class="confirm btn btn-danger btn-sm"><span
                                            class="bi bi-arrow-left-right"></span> {{ __('firefly.delete_all_transfers')  }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        var postUrl = "{{ route('preferences.test-notification') }}";

        $(document).ready(function () {
            $('button[data-bs-toggle="tab"]').on('show.bs.tab', function (e) {
                localStorage.setItem('profileActiveTab', $(e.target).attr('data-bs-target'));
            });
            var activeTab = localStorage.getItem('profileActiveTab');
            if (activeTab) {
                $('#profileTab button[data-bs-target="' + activeTab + '"]').click();
            }
        });
        var deleteAPIRoute = '{{ route('api.v1.data.destroy') }}';
        var confirmText = '{{ trans('firefly.are_you_sure') }}';
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
