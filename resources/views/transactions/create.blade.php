@extends('layout.v3.session')
@section('content')
<div x-data="create" id="form">
    <x-transaction.messages />
            <x-transaction.tab-list />
            <div class="tab-content" id="splitTabsContent">
                <template x-for="transaction, index in entries">
                    <x-transaction.split />
                </template>
            </div>



    <!-- Modal -->
    <div class="modal modal-lg fade" id="exampleModal" data-bs-backdrop="static" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Transaction relations</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col">
                            <p><em class="text-info">
                                Bla bla bla explanation
                                </em>
                                <em>This transaction has no relations to other transactions (yet)</em>
                            </p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <p>This transaction is or will be...</p>
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th colspan="2">Transaction relations</th>
                                </tr>
                                <tr>
                                    <td>Related to <a href="#">#123: something else</a></td>
                                    <td class="w-20">
                                        switch,change, del
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <strong>Add a new relation</strong><br>
                            This transaction: [ -- relation --]  [ -- transaction -- ] [ OK ]

                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>
    </div>

    </div>



@endsection
@section('scripts')
    @vite(['js/pages/transactions/create.js'])
@endsection
