@extends('layout.v3.session')
@section('content')
    <div class="row">
        <div class="col-lg-8 offset-lg-2 col-md-12 col-sm-12">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card mb-2">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('firefly.oauth_tokens') }}</h3>
                        </div>

                        <div class="card-body">
                            <p>
                                {!! trans('firefly.oauth_tokens_explain', ['link' => $link]) !!}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div x-data="index">
                <div class="row">
                    <div class="col-lg-12">
                        <div>
                            <div class="card mb-2">
                                <div class="card-header">
                                    <div class="row">
                                        <div class="col">
                                            <h3 class="card-title">
                                                {{ __('firefly.profile_oauth_clients') }}
                                            </h3>
                                        </div>
                                        <div class="col text-end">
                                            <a class="btn btn-default" tabindex="-1" @click="showCreateClientForm">
                                                {{ __('firefly.profile_oauth_create_new_client') }}
                                            </a>
                                        </div>
                                    </div>


                                </div>
                                <div class="card-body">
                                    <!-- Current Clients -->
                                    <p>
                                        {{ __('firefly.profile_oauth_clients_explain') }}
                                    </p>
                                    <p>
                                        {{ __('firefly.profile_oauth_clients_external_auth') }}
                                    </p>
                                    <template x-if="clients.length === 0">
                                        <p>
                                            {{ __('firefly.profile_oauth_no_clients') }}
                                        </p>
                                    </template>
                                    <template x-if="clients.length > 0">
                                        <table class="table table-responsive table-borderless mb-0">
                                            <thead>
                                            <tr>
                                                <th class="width-30"
                                                    scope="col">{{ __('firefly.profile_oauth_client_id') }}</th>
                                                <th class="width-30" scope="col">{{ __('firefly.name') }}</th>
                                                <th class="width-40" scope="col"
                                                    style="text-align:right">{{ __('firefly.actions') }}</th>
                                            </tr>
                                            </thead>

                                            <tbody>
                                            <template x-for="client in clients">
                                                <tr>
                                                    <!-- ID -->
                                                    <td style="vertical-align: middle;">
                                                        <span x-text="client.id"></span>
                                                    </td>

                                                    <!-- Name -->
                                                    <td style="vertical-align: middle;">
                                                        <span x-text="client.name"></span>
                                                    </td>

                                                    <!-- Secret -->
                                                    <td style="vertical-align: middle;text-align:right">
                                                        <div class="btn-group">
                                                            <template x-if="client.confidential">
                                                                <a :title="i18next.t('firefly.regenerate_secret')"
                                                                   class="btn btn-default btn-sm"
                                                                   @click="regenerateSecret(client)">
                                                                    <em :title="i18next.t('firefly.regenerate_secret')"
                                                                        class="fa fa-retweet"></em>
                                                                    {{ __('firefly.regenerate_secret') }}
                                                                </a>
                                                            </template>
                                                            <a class="btn btn-sm btn-default"
                                                               :title="i18next.t('firefly.edit')" tabindex="-1"
                                                               @click="edit(client)">
                                                                <em :title="i18next.t('firefly.edit')"
                                                                    class="fa fa-pencil"></em>
                                                                {{ __('firefly.edit') }}
                                                            </a>
                                                            <a :title="i18next.t('firefly.delete')"
                                                               class="btn btn-sm btn-danger" @click="destroy(client)">
                                                                <em :title="i18next.t('firefly.delete')"
                                                                    class="fa fa-trash"></em>
                                                                {{ __('firefly.delete') }}
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </template>
                                            </tbody>
                                        </table>
                                    </template>
                                </div>
                                <div class="card-footer text-end">
                                    <a class="btn btn-default" tabindex="-1" @click="showCreateClientForm">
                                        {{ __('firefly.profile_oauth_create_new_client') }}
                                    </a>
                                </div>
                            </div>

                            <!-- Create Client Modal -->
                            <div id="modal-create-client" class="modal fade" role="dialog" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h4 class="modal-title">
                                                {{ __('firefly.profile_oauth_create_client') }}
                                            </h4>

                                            <button aria-hidden="true" class="close" data-bs-dismiss="modal"
                                                    type="button">&times;
                                            </button>
                                        </div>

                                        <div class="modal-body">
                                            <!-- Form Errors -->
                                            <template x-if="createForm.errors.length > 0">
                                                <div class="alert alert-danger">
                                                    <p class="mb-0">
                                                        <strong>{{ __('firefly.profile_whoops') }}</strong> {{__('firefly.profile_something_wrong')}}
                                                    </p>
                                                    <br>
                                                    <ul>
                                                        <template x-for="error in createForm.errors">
                                                            <li>
                                                                <span x-text="error"></span>
                                                            </li>
                                                        </template>
                                                    </ul>
                                                </div>
                                            </template>

                                            <!-- Create Client Form -->
                                            <form role="form" aria-label="form">
                                                <!-- Name -->
                                                <div class="form-group row">
                                                    <label
                                                        class="col-md-3 col-form-label">{{ __('firefly.name') }}</label>

                                                    <div class="col-md-9">
                                                        <input id="create-client-name" x-model="createForm.name"
                                                               class="form-control"
                                                               spellcheck="false"
                                                               type="text" @keyup.enter="store">

                                                        <span
                                                            class="form-text text-muted">{{ __('firefly.profile_oauth_name_help') }}</span>
                                                    </div>
                                                </div>

                                                <!-- Redirect URIs -->
                                                <div class="form-group row">
                                                    <label class="col-md-3 col-form-label">{{
                                        __('firefly.profile_oauth_redirect_url')
                                    }}</label>

                                                    <div class="col-md-9">
                                                        <input x-model="createForm.redirect_uris" class="form-control"
                                                               name="redirect_uris"
                                                               spellcheck="false"
                                                               type="text" @keyup.enter="store">

                                                        <span
                                                            class="form-text text-muted">{{ __('firefly.profile_oauth_redirect_url_help') }}</span>
                                                    </div>
                                                </div>

                                                <!-- Confidential -->
                                                <div class="form-group row">
                                                    <label class="col-md-3 col-form-label">{{
                                        __('firefly.profile_oauth_confidential')
                                    }}</label>

                                                    <div class="col-md-9">
                                                        <div class="checkbox">
                                                            <label>
                                                                <input x-model="createForm.confidential"
                                                                       type="checkbox">
                                                            </label>
                                                        </div>

                                                        <span class="form-text text-muted">
                    {{ __('firefly.profile_oauth_confidential_help') }}
                  </span>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>

                                        <!-- Modal Actions -->
                                        <div class="modal-footer">
                                            <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">{{
                                __('firefly.close')
                            }}
                                            </button>

                                            <button class="btn btn-primary" type="button" @click="store">
                                                {{ __('firefly.profile_create') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Edit Client Modal -->
                            <div id="modal-edit-client" class="modal fade" role="dialog" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h4 class="modal-title">
                                                {{ __('firefly.profile_oauth_edit_client') }}
                                            </h4>

                                            <button aria-hidden="true" class="close" data-bs-dismiss="modal"
                                                    type="button">&times;
                                            </button>
                                        </div>

                                        <div class="modal-body">
                                            <!-- Form Errors -->
                                            <template x-if="editForm.errors.length > 0">
                                                <div class="alert alert-danger">
                                                    <p class="mb-0"><strong>{{ __('firefly.profile_whoops') }}</strong> {{
                                    __('firefly.profile_something_wrong')
                                }}</p>
                                                    <br>
                                                    <ul>
                                                        <template x-for="error in editForm.errors">
                                                            <li>
                                                                <span x-text="error"></span>
                                                            </li>
                                                        </template>
                                                    </ul>
                                                </div>
                                            </template>

                                            <!-- Edit Client Form -->
                                            <form role="form" aria-label="form">
                                                <!-- Name -->
                                                <div class="form-group row">
                                                    <label
                                                        class="col-md-3 col-form-label">{{ __('firefly.name') }}</label>

                                                    <div class="col-md-9">
                                                        <input id="edit-client-name" x-model="editForm.name"
                                                               class="form-control"
                                                               spellcheck="false"
                                                               type="text" @keyup.enter="update">

                                                        <span class="form-text text-muted">
                                {{ __('firefly.profile_oauth_name_help') }}
                                  </span>
                                                    </div>
                                                </div>

                                                <!-- Redirect URL -->
                                                <div class="form-group row">
                                                    <label class="col-md-3 col-form-label">{{
                                        __('firefly.profile_oauth_redirect_url')
                                    }}</label>

                                                    <div class="col-md-9">
                                                        <input x-model="editForm.redirect_uris" class="form-control"
                                                               name="redirect_uris"
                                                               spellcheck="false"
                                                               type="text" @keyup.enter="update">

                                                        <span class="form-text text-muted">
                                        {{ __('firefly.profile_oauth_redirect_url_help') }}
                                    </span>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>

                                        <!-- Modal Actions -->
                                        <div class="modal-footer">
                                            <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">{{
                                __('firefly.close')
                            }}
                                            </button>

                                            <button class="btn btn-primary" type="button" @click="update">
                                                {{ __('firefly.profile_save_changes') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Client Secret Modal -->
                            <div id="modal-client-secret" class="modal fade" role="dialog" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h4 class="modal-title">
                                                {{ __('firefly.profile_oauth_client_secret_title') }}
                                            </h4>

                                            <button aria-hidden="true" class="close" data-bs-dismiss="modal"
                                                    type="button">&times;
                                            </button>
                                        </div>

                                        <div class="modal-body">
                                            <p>
                                                {{ __('firefly.profile_oauth_client_secret_expl') }}
                                            </p>
                                            <input id="secret_box" x-model="clientSecret" class="form-control"
                                                   type="text" spellcheck="false">
                                        </div>

                                        <!-- Modal Actions -->
                                        <div class="modal-footer">
                                            <button class="btn btn-secondary" data-bs-dismiss="modal"
                                                    type="button">{{__('firefly.close')                           }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- personal access tokens --}}
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card mb-2">
                            <div class="card-header">
                                <div class="row">
                                    <div class="col">
                                        <h3 class="card-title">{{ __('firefly.profile_personal_access_tokens') }}</h3>
                                    </div>
                                    <div class="col text-end">
                                        <a class="btn btn-default " tabindex="-1" @click="showCreateTokenForm">
                                            {{ __('firefly.profile_create_new_token') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <p>
                                    {{ __('firefly.explain_pats') }}
                                </p>

                                <template x-if="tokens.length === 0">
                                    <p class="mb-0">
                                        {{ __('firefly.profile_no_personal_access_token') }}
                                    </p>
                                </template>

                                <!-- Personal Access Tokens -->
                                <template x-if="tokens.length > 0">
                                    <table class="table table-responsive table-borderless mb-0">
                                        <thead>
                                        <tr>
                                            <th scope="col">{{ __('firefly.name') }}</th>
                                            <th scope="col">{{ __('firefly.expires_at') }}</th>
                                            <th scope="col"></th>
                                        </tr>
                                        </thead>

                                        <tbody>
                                        <template x-for="token in tokens">
                                            <tr>
                                                <!-- Client Name -->
                                                <td style="vertical-align: middle;">
                                                    <span x-text="token.name"></span>
                                                </td>
                                                <!-- expires at -->
                                                <td style="vertical-align: middle;">
                                                    <span x-text="new Date(token.expires_at).toLocaleString()"></span>
                                                </td>

                                                <!-- Delete Button -->
                                                <td style="vertical-align: middle;">
                                                    <a class="action-link text-danger" @click="revoke(token)">
                                                        {{ __('firefly.delete') }}
                                                    </a>
                                                </td>
                                            </tr>
                                        </template>
                                        </tbody>
                                    </table>
                                </template>
                            </div>
                            <div class="card-footer text-end">
                                <a class="btn btn-default" tabindex="-1" @click="showCreateTokenForm">
                                    {{ __('firefly.profile_create_new_token') }}
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Create Token Modal -->
                    <div id="modal-create-token" class="modal fade" role="dialog" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h4 class="modal-title">
                                        {{ __('firefly.profile_create_token') }}
                                    </h4>

                                    <button aria-hidden="true" class="close" data-dismiss="modal" type="button">
                                        &times;
                                    </button>
                                </div>

                                <div class="modal-body">
                                    <!-- Form Errors -->
                                    <template x-if="form.errors.length > 0">
                                        <div class="alert alert-danger">
                                            <p class="mb-0"><strong>{{ __('firefly.profile_whoops') }}</strong>
                                                {{ __('firefly.profile_something_wrong') }}</p>
                                            <br>
                                            <ul>
                                                <template x-for="error in form.errors">
                                                    <li>
                                                        <span x-text="error"></span>
                                                    </li>
                                                </template>
                                            </ul>
                                        </div>
                                    </template>

                                    <!-- Create Token Form -->
                                    <form role="form" @submit.prevent="storePat">
                                        <!-- Name -->
                                        <div class="form-group row">
                                            <label class="col-md-4 col-form-label">{{ __('firefly.name') }}</label>

                                            <div class="col-md-6">
                                                <input id="create-token-name" x-model="form.name" class="form-control"
                                                       name="name" type="text" spellcheck="false">
                                            </div>
                                        </div>

                                        <!-- Scopes -->
                                        <template x-if="scopes.length > 0">
                                            <div class="form-group row">
                                                <label
                                                    class="col-md-4 col-form-label">{{ __('firefly.profile_scopes') }}</label>

                                                <div class="col-md-6">
                                                    <template x-for="scope in scopes">
                                                        <div>
                                                            <div class="checkbox">
                                                                <label>
                                                                    <input :checked="scopeIsAssigned(scope.id)"
                                                                           type="checkbox"
                                                                           @click="toggleScope(scope.id)">
                                                                    <span x-text="scope.id"></span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </form>
                                </div>

                                <!-- Modal Actions -->
                                <div class="modal-footer">
                                    <button class="btn btn-secondary" data-dismiss="modal"
                                            type="button">{{__('firefly.close')}}
                                    </button>
                                    <button class="btn btn-primary" type="button" @click="storePat">
                                        Create
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Access Token Modal -->
                    <div id="modal-access-token" class="modal fade" role="dialog" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h4 class="modal-title">
                                        {{ __('firefly.profile_personal_access_token') }}
                                    </h4>

                                    <button aria-hidden="true" class="close" data-dismiss="modal" type="button">
                                        &times;
                                    </button>
                                </div>

                                <div class="modal-body">
                                    <p>
                                        {{ __('firefly.profile_personal_access_token_explanation') }}
                                    </p>
                                    <textarea class="form-control" id="token_box" readonly rows="20"
                                              style="width:100%;" x-text="accessToken"></textarea>
                                </div>

                                <!-- Modal Actions -->
                                <div class="modal-footer">
                                    <button class="btn btn-secondary" data-dismiss="modal" type="button">{{
                                __('firefly.close')
                            }}
                                    </button>
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
    @vite(['js/pages/profile/oauth.js'])
@endsection
