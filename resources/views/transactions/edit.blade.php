@extends('layout.v3.session')
@section('scripts')
    @vite(['js/pages/transactions/edit.js'])
@endsection
@section('content')
    <div x-data="transactions" id="form">
        <x-transaction.messages/>
        <x-transaction.tab-list/>
        <div class="tab-content" id="splitTabsContent">
            <template x-for="transaction,index in entries">
                <x-transaction.split/>
            </template>
        </div>
    </div>

@endsection
