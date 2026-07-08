@extends('layout.v3.session')
@section('content')
    <div id="webhooks_edit" x-data="edit">
        <form accept-charset="UTF-8" class="form-horizontal" enctype="multipart/form-data">
            <input name="_token" type="hidden" value="xxx">

            <template x-if="error_message !== ''">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <button class="close" data-dismiss="alert" type="button"
                                    v-bind:aria-label="i18next.t('firefly.close')"><span
                                    aria-hidden="true">&times;</span></button>
                            <strong>{{ __("firefly.flash_error") }}</strong> <span x-text="error_message"></span>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="success_message !== ''">
            <div class="row">
                <div class="col-lg-12">
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button class="close" data-dismiss="alert" type="button"
                                v-bind:aria-label="i18next.t('firefly.close')"><span
                                aria-hidden="true">&times;</span></button>
                        <strong>{{ __("firefly.flash_success") }}</strong> <span x-html="success_message"></span>
                    </div>
                </div>
            </div>
            </template>

            <div class="row">
                <div class="col-lg-6 col-md-12 col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <span x-text="i18next.t('firefly.edit_webhook_js', {'title': this.title})"></span>
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-12">

                                    <x-form.alpine.title value="title" />
                                    <x-form.alpine.triggers value="triggers" multiple="multiple" />
                                    <x-form.alpine.responses value="responses" />
                                    <x-form.alpine.deliveries value="deliveries" />
                                    <x-form.alpine.url value="url" />
                                    <x-form.alpine.checkbox value="active" title="{{ __('form.active') }}" id="active" />

                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="btn-group">
                                <button id="submitButton" ref="submitButton" class="btn btn-success" @click="submit">
                                    {{ __('firefly.submit') }}
                                </button>
                            </div>
                            <p class="text-success" x-html="success_message"></p>
                            <p class="text-danger" x-html="error_message"></p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
@section('scripts')
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        var previousUrl = '{{ route('webhooks.index') }}';
    </script>
    @vite(['js/pages/webhooks/edit.js'])
@endsection
