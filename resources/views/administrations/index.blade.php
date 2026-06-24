@extends('layout.v3.session')
@section('content')
    <div x-data="index">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="box-title">
                            {{ __('firefly.administrations_index_menu') }}
                        </h3>
                    </div>
                    <div class="card-body">
                        {{ __('firefly.temp_administrations_introduction') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="box-title">
                            {{ __('firefly.table') }}
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <template x-if="administrations.length > 0">
                        <table class="table table-responsive table-hover" aria-label="A table.">
                            <thead>
                            <tr>
                                <th>{{ __('list.title') }}</th>
                                <th>{{ __('list.primary_currency') }}</th>
                                <th class="hidden-sm hidden-xs">&nbsp;</th>
                            </tr>
                            </thead>
                            <tbody>
                            <template x-for="administration in administrations" :key="administration.id">
                            <tr>
                                <td>
                                    <span x-text="administration.title"></span>
                                </td>
                                <td>
                                    <span x-text="administration.currency_name"></span> (<span x-text="administration.currency_code"></span>)
                                </td>
                                <td class="hidden-sm hidden-xs">
                                    <button class="btn btn-sm btn-secondary-outline dropdown-toggle" type="button" :id="'card_header_' + administration.id" data-bs-toggle="dropdown" aria-expanded="false">
                                            {{ __('firefly.actions') }} <span class="caret"></span></button>
                                    <ul class="dropdown-menu" :aria-labelledby="'card_header_' + administration.id">
                                            <li><a class="dropdown-item" :href="'./administrations/edit/' + administration.id"><span class="bi bi-pencil"></span>
                                                    {{ __('firefly.edit') }}</a></li>
                                        </ul>
                                </td>
                            </tr>
                            </template>
                            </tbody>
                        </table>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    @vite(['js/pages/administrations/index.js'])
@endsection
