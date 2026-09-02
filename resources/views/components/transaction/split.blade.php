<div :class="{'tab-pane fade pt-2':true, 'show active': index ===0 }" :id="'split-'+index+'-pane'" role="tabpanel"
     :aria-labelledby="'split-'+index+'-tab'" tabindex="0" x-init="addedSplit()">
    <div class="row mb-2">
        <div class="col-xl-6 col-lg-6 col-md-12 col-xs-12 mb-2">
            <!-- BASIC TRANSACTION INFORMATION -->
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.basic_journal_information') }}</h3>
                </div>
                <div class="card-body">
                    <!-- GROUP TITLE -->
                    @include('partials.form.transaction.group-title')
                    <!-- DESCRIPTION -->
                    @include('partials.form.transaction.description')

                    <!-- SOURCE ACCOUNT -->
                    @include('partials.form.transaction.source-account')

                    <!-- DESTINATION ACCOUNT -->
                    @include('partials.form.transaction.destination-account')

                    <!-- DETECTED TRANSACTION TYPE -->
                    @include('partials.form.transaction.transaction-type')

                    <!-- DATE AND TIME -->
                    @include('partials.form.transaction.date-time')
                </div>
            </div>
        </div>
        <div class="col-xl-6 col-lg-6 col-md-12 col-xs-12 mb-2">

            <!-- AMOUNTS -->
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">
                        {{ __('firefly.transaction_journal_amount') }}
                    </h3>
                </div>
                <div class="card-body">
                    <!-- AMOUNT -->
                    @include('partials.form.transaction.amount')

                    <!-- FOREIGN AMOUNT -->
                    @include('partials.form.transaction.foreign-amount')
                </div>
            </div>
        </div>
        <!-- META DATA -->
        <div class="col-xl-4 col-lg-6 col-md-12 col-xs-12 mb-2">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">
                        {{ __('firefly.transaction_journal_meta') }}
                    </h3>
                </div>
                <div class="card-body">
                    <!-- BUDGET -->
                    @include('partials.form.transaction.budget')

                    <!-- CATEGORY -->
                    @include('partials.form.transaction.category')

                    <!-- PIGGY BANK -->
                    @include('partials.form.transaction.piggy-bank')

                    <!-- SUBSCRIPTION -->
                    @include('partials.form.transaction.subscription')

                    <!-- TAGS -->
                    @include('partials.form.transaction.tags')

                    <!-- NOTES -->
                    @include('partials.form.transaction.notes')
                </div>
            </div>

        </div>
        <!-- EXTRA THINGS -->
        <div class="col-xl-4 col-lg-6 col-md-12 col-xs-12 mb-2">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">
                        {{ __('firefly.transaction_journal_extra') }}
                    </h3>
                </div>
                <div class="card-body">
                    <!-- ATTACHMENTS -->
                    @include('partials.form.transaction.attachments')

                    <!-- INTERNAL REFERENCE -->
                    @include('partials.form.transaction.internal-reference')

                    <!-- EXTERNAL URL -->
                    @include('partials.form.transaction.external-url')

                    <!-- LOCATION -->
                    @include('partials.form.transaction.location')

                    <!-- DATE FIELDS -->
                    @include('partials.form.transaction.date-fields')

                    <!-- TRANSACTION LINKS -->
                    @include('partials.form.transaction.links')
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-6 col-md-12 col-xs-12 mb-2">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">
                        {{ __('firefly.submission_options') }}
                    </h3>
                </div>
                <div class="card-body">
                    @include('partials.form.transaction.submission-options')
                </div>
                <div class="card-footer">
                    <div class="row">
                        <div class="col text-end">
                            <div class="btn-group">
                                <button @click="addSplit()" class="btn btn-secondary"
                                        :disabled="formStates.isSubmitting">{{ __('firefly.add_another_split')  }}</button>
                                <template x-if="1 !== entries.length">
                                    <button :disabled="formStates.isSubmitting" class="btn btn-danger text-white"
                                            @click="removeSplit(index)">{{ __('firefly.transaction_remove_split') }}</button>
                                </template>
                                <button class="btn btn-success text-white" :disabled="formStates.isSubmitting"
                                        @click="save()">{{ __('firefly.submit') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">

        </div>
    </div>
    <!-- Modal for links -->
    <div class="modal modal-xl fade" :id="'links_modal_' + index" data-bs-backdrop="static" tabindex="-1"
         :aria-labelledby="'links_modal_' + index" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5">Transaction relations</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col">
                                <p><em class="text-info">
                                        {{ __('firefly.explain_related') }}
                                    </em>
                                    <template x-if="0 === links[index].length">
                                        <em>{{ __('firefly.no_relations_yet') }}</em>
                                    </template>
                                </p>
                            </div>
                        </div>
                        <template x-if="links[index].length > 0">
                            <div class="row">
                                <div class="col">
                                    <table class="table table-sm table-bordered">
                                        <tr>
                                            <th colspan="2">{{ __('firefly.related_transactions') }}</th>
                                        </tr>
                                        <template x-for="link, key in links[index]" key="link.id">
                                            <tr>
                                                <td>
                                                    <template x-if="!link.editMode">
                                                        <span>
                                                            <template x-if="0 === link.group_id">
                                                                <span><span x-text="link.link_type_label"></span> <a :href="'transactions/show-by-journal/' + link.journal_id" target="_blank"><span x-text="link.journal_description"></span></a>
                                                                </span>
                                                            </template>
                                                            <template x-if="0 !== link.group_id">
                                                                <span><span x-text="link.link_type_label"></span> <a :href="'transactions/show/' + link.group_id" target="_blank"><span x-text="link.journal_description"></span></a>
                                                                </span>
                                                            </template>
                                                        </span>
                                                    </template>
                                                    <template x-if="link.editMode">
                                                        <div class="row">
                                                            <div class="col">
                                                                <div class="input-group">
                                                                <select name="new-link-type" class="form-control">
                                                                    <template x-for="type in formData.linkTypes ">
                                                                        <option :selected="type.id === link.link_type_id && 'inward' === link.link_type_direction"  :value="type.id + '_inward'" :label="type.inward" x-text="type.inward"></option>
                                                                    </template>
                                                                    <template x-for="type in formData.linkTypes">
                                                                        <option :selected="type.id === link.link_type_id && 'outward' === link.link_type_direction" :value="type.id + '_outward'" :label="type.outward" x-text="type.outward"></option>
                                                                    </template>
                                                                </select>
                                                                <button type="button" :data-index="index" :data-row-index="key" @click.prevent="saveEditedLink" class="btn btn-sm btn-outline-success"><em class="bi bi-check"></em></button>
                                                                </div>
                                                            </div>
                                                            <div class="col">
                                                                <input type="text" readonly class="form-control-plaintext" :value="link.journal_description">
                                                            </div>
                                                        </div>
                                                    </template>
                                                </td>
                                                <td class="w-20">
                                                    <template x-if="!link.editMode">
                                                    <div class="btn-group btn-group-sm">
                                                        <button :data-index="index" :data-row-index="key" @click.prevent="switchLink" class="btn btn-outline-secondary" title="{{ __('firefly.switch') }}"><em class="bi bi-arrow-left-right"></em></button>
                                                        <button :data-index="index" :data-row-index="key" @click.prevent="editLink" class="btn btn-outline-secondary" title="{{ __('firefly.edit') }}"><em class="bi bi-pencil"></em></button>
                                                        <button :data-index="index" :data-row-index="key" @click.prevent="removeLink" class="btn btn-outline-danger" title="{{ __('firefly.delete') }}"><em class="bi bi-trash"></em></button>
                                                    </div>
                                                    </template>
                                                </td>
                                            </tr>
                                        </template>

                                    </table>
                                </div>
                            </div>
                        </template>
                        <div class="row">
                            <div class="col">
                                <template x-if="'create' === formBehaviour.formType">
                                    <h5>{{ __('firefly.link_header_create') }}</h5>
                                </template>
                                <template x-if="'edit' === formBehaviour.formType">
                                    <h5>{{ __('firefly.link_header_edit') }}</h5>
                                </template>
                                <div class="row">
                                    <div class="col w-30">
                                        <input type="text" readonly class="form-control-plaintext" value="{{ __('firefly.this_transaction') }}">
                                    </div>
                                    <div class="col">
                                        <select class="form-select" name="link_type_id" :data-index="index" :id="'link_type_id_' + index">
                                            <template x-for="type in formData.linkTypes ">
                                                <option :value="type.id + '_inward'" :label="type.inward" x-text="type.inward"></option>
                                            </template>
                                            <template x-for="type in formData.linkTypes">
                                                <option :value="type.id + '_outward'" :label="type.outward" x-text="type.outward"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div class="col">
                                        <input type="text" name="search" :id="'links_modal_search_' + index" class="form-control linked-transactions-search" placeholder="{{ __('firefly.search_query_here') }}" aria-label="{{ __('firefly.search_query_here') }}">
                                    </div>
                                    <div class="col w-15 text-end">
                                        <input type="submit" name="submit" :data-index="index" @click.prevent="saveNewLink" value="{{ __('firefly.add') }}" class="btn btn-primary">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <div class="row inline" style="width:100%;">
                        <div class="col align-middle d-flex align-items-center">
                            <em><small>
                                    {{ __('firefly.auto_save_active') }}
                                </small></em>
                        </div>
                        <div class="col text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('firefly.close') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
