@extends('layout.v3.session')
@section('content')
    <div x-data="rates">
        <div class="row">
            <div class="col-lg-8 offset-lg-2 col-md-12 col-sm-12 col-xs-12">
                <div class="card card-primary card-outline mb-2">
                    <div class="card-header">
                        <h3 class="box-title">{{ __('firefly.header_exchange_rates_rates') }}</h3>
                    </div>
                    <div class="card-body">
                        <p>
                            {{ __('firefly.exchange_rates_intro_rates') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-8 offset-lg-2 col-md-12 col-sm-12 col-xs-12">
                <div class="card card-outline mb-2">
                    <div class="card-header">
                        <h3 class="box-title">{{ __('firefly.header_exchange_rates_table') }}</h3>
                    </div>
                    <div class="box-body p-0">
                        <template x-if="totalPages > 1">
                        <nav>
                            <ul class="pagination">
                                <template x-if="1 === this.page">
                                    <li class="page-item disabled" aria-disabled="true"
                                        :aria-label="i18next.t('pagination.previous')">
                                        <span class="page-link" aria-hidden="true">&lsaquo;</span>
                                    </li>
                                </template>
                                <template x-if="1 !== this.page">
                                    <li class="page-item">
                                        <a class="page-link" :href="'/exchange-rates/'+from_code+'/'+to_code+'?page=' + (this.page-1)" rel="prev" :aria-label="i18next.t('pagination.next')">&lsaquo;</a>
                                    </li>
                                </template>
                                <template x-for="item in this.totalPages">
                                    <li :class="item === page ? 'page-item active' : 'page-item'" aria-current="page">
                                        <template x-if="item === page"></template><span class="page-link" x-text="item"></span>
                                        <template x-if="item !== page"><a class="page-link" :href="'/exchange-rates/'+from_code+'/'+to_code+'?page=' + item" x-text="item"></a></template>
                                    </li>
                                </template>
                                <template x-if="totalPages !== page">
                                    <li class="page-item"><a class="page-link" :href="'/exchange-rates/'+from_code+'/'+to_code+'?page=' + (this.page+1)" rel="next" :aria-label="i18next.t('pagination.next')">&rsaquo;</a></li>
                                </template>
                                <template x-if="totalPages === page">
                                    <li class="page-item disabled" aria-disabled="true" :aria-label="i18next.t('pagination.next')"><span class="page-link" aria-hidden="true">&rsaquo;</span></li>
                                </template>
                            </ul>
                        </nav>
                        </template>

                        <table class="table table-responsive table-hover">
                            <thead>
                            <tr>
                                <th>{{ __('form.date') }}</th>
                                <th x-html="i18next.t('form.from_currency_to_currency', {from: from.code, to: to.code})"></th>
                                <th x-html="i18next.t('form.to_currency_from_currency', {from: from.code, to: to.code})"></th>
                                <th>&nbsp;</th>
                            </tr>
                            </thead>
                            <tbody>
                            <template x-if="loading">
                            <tr>
                                <td colspan="4" class="text-center">
                                    <div class="spinner-border spinner-border-sm" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </td>
                            </tr>
                            </template>
                            <template x-if="0 === rates.length">
                                <tr>
                                    <td colspan="4" class="text-center">
                                        <em class="bi bi-battery-low"></em>
                                    </td>
                                </tr>
                            </template>
                            <template x-for="(rate, index) in rates" :key="rate.key">
                                <tr>
                                    <td>
                                        <input
                                            ref="date"
                                            :value="rate.date_field"
                                            autocomplete="off"
                                            class="form-control"
                                            name="date[]"
                                            type="date"
                                            x-bind:placeholder="i18next.t('firefly.date')"
                                            x-bind:title="i18next.t('firefly.date')"
                                        >
                                    </td>
                                    <td>
                                        <!-- (<span v-text="rate.rate_id"></span>) -->
                                        <input type="number" class="form-control" min="0" step="any" x-model="rate.rate">
                                    </td>
                                    <td>
                                        <!-- (<span v-text="rate.inverse_id"></span>) -->
                                        <input type="number" class="form-control" min="0" step="any" x-model="rate.inverse">
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button
                                                :disabled="saveButtonDisabled(index)"
                                                class="btn btn-default" :title="i18next.t('firefly.submit')"
                                                @click="updateRate(index)">
                                                <em class="bi bi-floppy"></em>
                                            </button>
                                            <button class="btn btn-danger" :title="i18next.t('firefly.delete')"
                                                    @click="deleteRate(index)">
                                                <em class="bi bi-trash"></em>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            </tbody>
                        </table>

                        <template x-if="totalPages > 1">
                            <nav>
                                <ul class="pagination">
                                    <template x-if="1 === this.page">
                                        <li class="page-item disabled" aria-disabled="true"
                                            :aria-label="i18next.t('pagination.previous')">
                                            <span class="page-link" aria-hidden="true">&lsaquo;</span>
                                        </li>
                                    </template>
                                    <template x-if="1 !== this.page">
                                        <li class="page-item">
                                            <a class="page-link" :href="'/exchange-rates/'+from_code+'/'+to_code+'?page=' + (this.page-1)" rel="prev" :aria-label="i18next.t('pagination.next')">&lsaquo;</a>
                                        </li>
                                    </template>
                                    <template x-for="item in this.totalPages">
                                        <li :class="item === page ? 'page-item active' : 'page-item'" aria-current="page">
                                            <template x-if="item === page"></template><span class="page-link" x-text="item"></span>
                                            <template x-if="item !== page"><a class="page-link" :href="'/exchange-rates/'+from_code+'/'+to_code+'?page=' + item" x-text="item"></a></template>
                                        </li>
                                    </template>
                                    <template x-if="totalPages !== page">
                                        <li class="page-item"><a class="page-link" :href="'/exchange-rates/'+from_code+'/'+to_code+'?page=' + (this.page+1)" rel="next" :aria-label="i18next.t('pagination.next')">&rsaquo;</a></li>
                                    </template>
                                    <template x-if="totalPages === page">
                                        <li class="page-item disabled" aria-disabled="true" :aria-label="i18next.t('pagination.next')"><span class="page-link" aria-hidden="true">&rsaquo;</span></li>
                                    </template>
                                </ul>
                            </nav>
                        </template>

                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-8 offset-lg-2 col-md-12 col-sm-12 col-xs-12">
                <form class="form-horizontal nodisablebutton" @submit="submitRate">
                    <div class="card mb-2">
                        <div class="card-header">
                            <h3 class="box-title">{{ __('firefly.add_new_rate') }}</h3>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3" id="name_holder">
                                <div class="input-group has-validation">
                                    <label for="ffInput_date" class="col-sm-3 col-form-label has-validation" x-text="i18next.t('form.date')"></label>
                                <div class="col-sm-9">
                                    <input class="form-control" type="date" name="date" id="ffInput_date" :disabled="posting" autocomplete="off" spellcheck="false" x-model="newDate"></div>
                                </div>
                            </div>
                            <div class="row mb-3" id="name_holder">
                                <div class="input-group has-validation">
                                <label for="ffInput_rate" class="col-sm-3 col-form-label" x-text="i18next.t('form.rate')"></label>
                                    <div class="col-sm-9">
                                        <input class="form-control" type="number" name="rate" id="ffInput_rate" :disabled="posting" autocomplete="off" spellcheck="false" x-model="newRate" step="any">
                                        <p class="form-text" x-text="i18next.t('firefly.help_rate_form', {from: from_code, to: to_code})"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="nodisablebutton btn pull-right btn-success" x-text="i18next.t('firefly.save_new_rate')"></button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>

@endsection
@section('scripts')
    @vite(['js/pages/exchange-rates/rates.js'])
@endsection
