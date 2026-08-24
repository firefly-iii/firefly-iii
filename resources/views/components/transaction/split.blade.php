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
    <div class="modal modal-xl fade" :id="'linksModal_' + index" data-bs-backdrop="static" tabindex="-1"
         :aria-labelledby="'linksModal_' + index" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" :id="'linksModal_' + index">Transaction relations</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col">
                                <p><em class="text-info">
                                        TODO Bla bla bla explanation.
                                    </em>
                                    <template x-if="0 === links[index].length">
                                        <em>TODO This transaction has no relations to other transactions (yet).</em>
                                    </template>
                                </p>
                            </div>
                        </div>
                        <template x-if="links[index].length > 0">
                            <div class="row">
                                <div class="col">
                                    <table class="table table-sm table-bordered">
                                        <tr>
                                            <th colspan="2">Transaction relations</th>
                                        </tr>
                                        <tr>
                                            <td>Pays for / is paid by <a href="#">#123: something else</a></td>
                                            <td class="w-20">
                                                switch,change, del
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </template>
                        <div class="row">
                            <div class="col">
                                <h5>Link this (TODO NEW?) transaction to another transaction</h5>
                                <div class="row">
                                    <div class="col w-30">
                                        <input type="text" readonly class="form-control-plaintext" value="This transaction">
                                    </div>
                                    <div class="col">
                                        <select class="form-control" name="link_type_id" :data-index="index" :id="'link_type_id_' + index">
                                            <template x-for="type in formData.linkTypes ">
                                                <option :value="type.id + '_inward'" :label="type.inward" x-text="type.inward"></option>
                                            </template>
                                            <template x-for="type in formData.linkTypes">
                                                <option :value="type.id + '_outward'" :label="type.outward" x-text="type.outward"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div class="col">
                                        <input type="text" name="search" :id="'linksModal_search_' + index" class="form-control linked-transactions-search" placeholder="TODO Search here..." aria-label="TODO Search here...">
                                    </div>
                                    <div class="col w-15 text-end">
                                        <input type="submit" name="submit" :data-index="index" @click.prevent="saveNewLink" value="TODO Save" class="btn btn-primary">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <div class="row inline" style="width:100%;">
                        <div class="col align-middle d-flex align-items-center">
                            <em><small>Changes are saved automatically</small></em>
                        </div>
                        <div class="col text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
