<div class="modal-dialog modal-lg">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
            <h4 class="modal-title" id="myModalLabel">Relate "{{{$journal->description}}}" to other transactions</h4>
        </div>
        <div class="modal-body">

            <h5>Search results</h5>
            <h5>Related transactions</h5>
            @include('list.journals-tiny',['transactions' => $members])

        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
    </div>
</div>
