@extends('layout.v3.session')
@section('content')
    <div x-data="edit">
        <form accept-charset="UTF-8" class="form-horizontal" enctype="multipart/form-data">
            <input name="_token" type="hidden" value="xxx">

            <template x-if="error_message !== ''">
            <div class="row">
                <div class="col-lg-12">
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <strong>{{ __("firefly.flash_error") }}</strong> <span x-text="error_message"></span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('firefly.close') }}"></button>
                    </div>
                </div>
            </div>
            </template>

            <template x-if="success_message !== ''">
            <div class="row">
                <div class="col-lg-12">
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <strong>{{ __("firefly.flash_success") }}</strong> <span x-text="success_message"></span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('firefly.close') }}"></button>
                    </div>
                </div>
            </div>
            </template>

            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12">
                    <div class="card mb-2">
                        <div class="card-header">
                            <h3 class="box-title" x-text="i18next.t('firefly.administrations_page_edit_sub_title_js', {title: this.pageTitle})">

                            </h3>
                        </div>
                        <div class="card-body">
                            {{ __('firefly.temp_administrations_introduction') }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-8 offset-lg-2 col-md-12 col-sm-12">
                    <div class="card mb-2">
                        <div class="card-header">
                            <h3 class="card-title" x-text="i18next.t('firefly.administrations_page_edit_sub_title_js', {title: this.pageTitle})"></h3>
                        </div>
                        <div class="card-body">
                            <x-form.alpine.title value="administration.title" />
                            <x-form.alpine.transaction-currency value="administration.currency_id" />

                            TODO currency

                            <UserGroupCurrency :value=administration.currency_id :error="errors.currency_id"
                                               v-on:input="administration.currency_id = $event"></UserGroupCurrency>
                        </div>
                        <div class="card-footer">
                            <div class="text-end">
                                <button id="submitButton" ref="submitButton" class="btn btn-success" @click="submit">
                                    {{ __('firefly.submit') }}
                                </button>
                            </div>
                            <p class="text-success" x-text="success_message"></p>
                            <p class="text-danger" x-text="error_message"></p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
@section('scripts')
    @vite(['js/pages/administrations/edit.js'])
@endsection
